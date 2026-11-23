<?php

namespace App\Services\Imports;

use App\Models\BarangayCase;
use App\Models\CaseNote;
use App\Models\CaseParty;
use App\Models\CaseStatusLog;
use App\Models\CaseType;
use App\Models\FiscalYear;
use App\Models\ImportBatch;
use App\Models\ImportRow;
use App\Models\Party;
use App\Models\Purok;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\SettingsService;
use App\Support\CaseStatus;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;

class LegacyImportService
{
    public function __construct(
        protected SpreadsheetFileService $spreadsheet,
        protected SettingsService $settings,
        protected AuditLogger $audit,
    ) {}

    /**
     * Allowed lookback years for legacy imports (last N record years plus current year).
     *
     * @return array<int, int>
     */
    public function allowedYears(): array
    {
        $lookback = max(1, (int) ($this->settings->get('records.lookback_years', 4) ?: 4));
        $activeFy = FiscalYear::where('status', 'active')->first();
        $baseYear = $activeFy ? (int) $activeFy->year : (int) now()->year;

        $years = [];
        for ($i = 0; $i <= $lookback; $i++) {
            $years[] = $baseYear - $i;
        }

        return $years;
    }

    /**
     * Retrieve saved column mappings keyed by source name.
     *
     * @return array<string, array{columns: array<string, string>, statuses: array<string, string>}>
     */
    public function savedMappings(): array
    {
        $raw = $this->settings->get('imports.saved_mappings', []);
        if (is_string($raw)) {
            $decoded = json_decode($raw, true);

            return is_array($decoded) ? $decoded : [];
        }

        return is_array($raw) ? $raw : [];
    }

    /**
     * Store uploaded Excel/CSV file on private disk, parse rows, and create an ImportBatch.
     */
    public function uploadBatch(
        UploadedFile $file,
        User $by,
        ?int $targetFiscalYear = null,
        ?string $sourceName = null,
    ): ImportBatch {
        $ext = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: 'csv');
        if (! in_array($ext, ['xlsx', 'csv', 'txt'], true)) {
            throw new InvalidArgumentException('Only Excel (.xlsx) and CSV (.csv) files are supported.');
        }

        $storedFilename = Str::uuid()->toString().'.'.$ext;
        $storedPath = $file->storeAs('imports', $storedFilename, 'private');
        $absolutePath = Storage::disk('private')->path($storedPath);

        $parsed = $this->spreadsheet->parseFile($absolutePath, $ext);
        $headers = $parsed['headers'];
        $rows = $parsed['rows'];

        if ($headers === [] || $rows === []) {
            Storage::disk('private')->delete($storedPath);
            throw new InvalidArgumentException('The uploaded file contains no readable header or data rows.');
        }

        $guessedColumns = $this->spreadsheet->guessMapping($headers);
        $savedPresets = $this->savedMappings();
        $initialMapping = [
            'columns' => $guessedColumns,
            'statuses' => $this->defaultStatusMap(),
        ];

        if ($sourceName && isset($savedPresets[$sourceName])) {
            $initialMapping['columns'] = array_merge($guessedColumns, $savedPresets[$sourceName]['columns'] ?? []);
            $initialMapping['statuses'] = array_merge($initialMapping['statuses'], $savedPresets[$sourceName]['statuses'] ?? []);
        }

        return DB::transaction(function () use ($file, $by, $storedPath, $targetFiscalYear, $sourceName, $headers, $rows, $initialMapping) {
            $batch = ImportBatch::create([
                'user_id' => $by->id,
                'filename' => $file->getClientOriginalName(),
                'stored_path' => $storedPath,
                'target_fiscal_year' => $targetFiscalYear,
                'source_name' => $sourceName,
                'status' => ImportBatch::STATUS_UPLOADED,
                'headers' => $headers,
                'mapping' => $initialMapping,
                'stats' => [
                    'total_rows' => count($rows),
                    'valid' => 0,
                    'warnings' => 0,
                    'conflicts' => 0,
                    'errors' => 0,
                    'imported' => 0,
                    'skipped' => 0,
                ],
            ]);

            $now = now();
            $insertBuffer = [];
            foreach (array_values($rows) as $idx => $rawRow) {
                $insertBuffer[] = [
                    'import_batch_id' => $batch->id,
                    'row_number' => $idx + 1,
                    'raw' => json_encode($rawRow, JSON_UNESCAPED_UNICODE),
                    'mapped' => null,
                    'status' => ImportRow::STATUS_PENDING,
                    'errors' => null,
                    'case_id' => null,
                    'created_party_ids' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                if (count($insertBuffer) >= 100) {
                    DB::table('import_rows')->insert($insertBuffer);
                    $insertBuffer = [];
                }
            }

            if ($insertBuffer !== []) {
                DB::table('import_rows')->insert($insertBuffer);
            }

            $this->audit->log($by, 'import_batch.uploaded', $batch, [], [
                'filename' => $batch->filename,
                'total_rows' => count($rows),
                'target_fiscal_year' => $targetFiscalYear,
            ]);

            return $batch->fresh();
        });
    }

    /**
     * Save column & legacy status mapping, validate every row, and flag duplicates/conflicts.
     *
     * @param  array<string, ?string>  $columnMapping
     * @param  array<string, string>  $statusMapping
     */
    public function saveMappingAndValidate(
        ImportBatch $batch,
        array $columnMapping,
        array $statusMapping,
        User $by,
        ?string $saveMappingName = null,
    ): ImportBatch {
        if (in_array($batch->status, [ImportBatch::STATUS_COMMITTED], true)) {
            throw new InvalidArgumentException('Committed import batches cannot be remapped unless rolled back first.');
        }

        $cleanColumns = [];
        foreach ($columnMapping as $targetKey => $sourceHeader) {
            $sourceHeader = is_string($sourceHeader) ? trim($sourceHeader) : '';
            if ($sourceHeader !== '') {
                $cleanColumns[$targetKey] = $sourceHeader;
            }
        }

        $mergedStatusMap = array_merge($this->defaultStatusMap(), array_filter($statusMapping));

        if ($saveMappingName !== null && trim($saveMappingName) !== '') {
            $presetKey = trim($saveMappingName);
            $presets = $this->savedMappings();
            $presets[$presetKey] = [
                'columns' => $cleanColumns,
                'statuses' => $mergedStatusMap,
            ];
            $this->settings->set('imports.saved_mappings', $presets, $by);
            $batch->source_name = $presetKey;
        }

        $allowedYears = $this->allowedYears();
        $minAllowedYear = min($allowedYears);
        $maxAllowedYear = max($allowedYears);

        // Preload existing case numbers and existing parties/cases for fast duplicate detection
        $existingCaseNumbers = BarangayCase::pluck('id', 'case_number')->all();

        $existingParties = Party::select(['id', 'full_name', 'birth_date', 'contact_number', 'address_line', 'residency_status'])
            ->get();

        $partiesByNormalizedName = [];
        foreach ($existingParties as $party) {
            $normName = $this->normalizeName($party->full_name);
            if ($normName !== '') {
                $partiesByNormalizedName[$normName][] = $party;
            }
        }

        $existingCasesForDupCheck = BarangayCase::with(['complainants', 'respondents'])
            ->select(['id', 'case_number', 'filed_on', 'incident_date', 'status'])
            ->get()
            ->map(function (BarangayCase $c): array {
                return [
                    'id' => $c->id,
                    'case_number' => $c->case_number,
                    'filed_on' => $c->filed_on?->toDateString(),
                    'incident_date' => $c->incident_date?->toDateString(),
                    'complainants' => $c->complainants->map(fn (Party $p) => $this->normalizeName($p->full_name))->filter()->values()->all(),
                    'respondents' => $c->respondents->map(fn (Party $p) => $this->normalizeName($p->full_name))->filter()->values()->all(),
                ];
            })
            ->all();

        return DB::transaction(function () use (
            $batch,
            $cleanColumns,
            $mergedStatusMap,
            $by,
            $minAllowedYear,
            $maxAllowedYear,
            $existingCaseNumbers,
            $partiesByNormalizedName,
            $existingCasesForDupCheck,
        ) {
            $seenCaseNumbersInBatch = [];
            $seenBatchCasesForDupCheck = [];

            $stats = [
                'total_rows' => 0,
                'valid' => 0,
                'warnings' => 0,
                'conflicts' => 0,
                'errors' => 0,
                'imported' => 0,
                'skipped' => 0,
            ];

            $rows = $batch->rows()->orderBy('row_number')->get();

            foreach ($rows as $row) {
                $stats['total_rows']++;
                $raw = $row->raw ?? [];
                $errors = [];
                $warnings = [];
                $isConflict = false;

                $extract = function (string $fieldKey) use ($cleanColumns, $raw): string {
                    $header = $cleanColumns[$fieldKey] ?? null;
                    if (! $header || ! array_key_exists($header, $raw)) {
                        return '';
                    }

                    return trim((string) $raw[$header]);
                };

                $caseNumber = $extract('case_number');
                $rawRecordType = strtolower($extract('record_type'));
                $recordType = in_array($rawRecordType, ['blotter', 'blotter-only', 'blotter_only'], true) ? 'blotter' : 'complaint';

                $rawFiledOn = $extract('filed_on');
                $filedOn = $this->parseDate($rawFiledOn);

                $rawIncidentDate = $extract('incident_date');
                $incidentDate = $rawIncidentDate !== '' ? $this->parseDate($rawIncidentDate) : null;

                $incidentLocation = $extract('incident_location');
                $caseTypeName = $extract('case_type');
                $purokName = $extract('purok');
                $complainantName = $extract('complainant_full_name');
                $complainantBirthDate = $this->parseDate($extract('complainant_birth_date'));
                $complainantContact = $extract('complainant_contact');
                $complainantAddress = $extract('complainant_address');
                $complainantResidency = $this->normalizeResidency($extract('complainant_residency'));

                $respondentName = $extract('respondent_full_name');
                $respondentBirthDate = $this->parseDate($extract('respondent_birth_date'));
                $respondentContact = $extract('respondent_contact');
                $respondentAddress = $extract('respondent_address');
                $respondentResidency = $this->normalizeResidency($extract('respondent_residency'));

                $title = $extract('title');
                $narrative = $extract('narrative');
                $rawStatus = $extract('status');
                $settledOn = $this->parseDate($extract('settled_on'));
                $settlementTerms = $extract('settlement_terms');
                $rawConfidential = strtolower($extract('is_confidential'));
                $isConfidential = in_array($rawConfidential, ['1', 'true', 'yes', 'y', 'confidential'], true);

                // Required field checks
                if ($caseNumber === '') {
                    $errors[] = 'Missing required Case / Docket Number.';
                }
                if ($filedOn === null) {
                    $errors[] = $rawFiledOn === ''
                        ? 'Missing required Filed Date.'
                        : "Invalid Filed Date '{$rawFiledOn}' (expected YYYY-MM-DD).";
                }
                if ($complainantName === '') {
                    $errors[] = 'Missing required Complainant Full Name.';
                }
                if ($respondentName === '') {
                    $errors[] = 'Missing required Respondent Full Name.';
                }
                if ($narrative === '') {
                    $errors[] = 'Missing required Complaint / Blotter Narrative.';
                }

                // Rule 1: Keep original case number; existing number is a conflict row, not an overwrite
                if ($caseNumber !== '') {
                    if (isset($existingCaseNumbers[$caseNumber])) {
                        $isConflict = true;
                        $errors[] = "Conflict: Case number '{$caseNumber}' already exists in the system.";
                    } elseif (isset($seenCaseNumbersInBatch[$caseNumber])) {
                        $isConflict = true;
                        $errors[] = "Conflict: Case number '{$caseNumber}' is duplicated within this file (Row #{$seenCaseNumbersInBatch[$caseNumber]}).";
                    } else {
                        $seenCaseNumbersInBatch[$caseNumber] = $row->row_number;
                    }
                }

                // Fiscal year & 4-year lookback check
                $recordYear = $filedOn
                    ? (int) substr($filedOn, 0, 4)
                    : ($batch->target_fiscal_year ?: null);

                if ($recordYear !== null && ($recordYear < $minAllowedYear || $recordYear > $maxAllowedYear)) {
                    $errors[] = "Filed year {$recordYear} is outside the configured lookback window ({$minAllowedYear}–{$maxAllowedYear}).";
                }

                // Rule 6: Legacy status mapping (unknown becomes 'filed' with a note; blotter stays 'filed')
                $mappedStatus = CaseStatus::FILED;
                $legacyStatusNote = null;

                if ($recordType === 'blotter') {
                    $mappedStatus = CaseStatus::FILED;
                    if ($rawStatus !== '' && strtolower($rawStatus) !== 'filed') {
                        $legacyStatusNote = "Imported blotter legacy status: {$rawStatus}";
                    }
                } elseif ($rawStatus !== '') {
                    $normStatusKey = strtolower(trim($rawStatus));
                    if (isset($mergedStatusMap[$normStatusKey]) && in_array($mergedStatusMap[$normStatusKey], CaseStatus::all(), true)) {
                        $mappedStatus = $mergedStatusMap[$normStatusKey];
                    } elseif (in_array($normStatusKey, CaseStatus::all(), true)) {
                        $mappedStatus = $normStatusKey;
                    } else {
                        $mappedStatus = CaseStatus::FILED;
                        $legacyStatusNote = "Imported legacy status: {$rawStatus}";
                        $warnings[] = "Unrecognized legacy status '{$rawStatus}' will default to 'filed' with a recorded note.";
                    }
                }

                // Rule 4: Party exact match (normalized name + birth date or contact) vs likely match review list
                $complainantMatch = $this->matchParty($complainantName, $complainantBirthDate, $complainantContact, $partiesByNormalizedName);
                if ($complainantMatch['warning'] !== null) {
                    $warnings[] = 'Complainant: '.$complainantMatch['warning'];
                }

                $respondentMatch = $this->matchParty($respondentName, $respondentBirthDate, $respondentContact, $partiesByNormalizedName);
                if ($respondentMatch['warning'] !== null) {
                    $warnings[] = 'Respondent: '.$respondentMatch['warning'];
                }

                // Rule 5: Case duplicate check (same complainant & respondent within 30 days)
                $normComp = $this->normalizeName($complainantName);
                $normResp = $this->normalizeName($respondentName);
                $duplicateCaseRef = null;

                if ($normComp !== '' && $normResp !== '' && $filedOn !== null) {
                    $dupMatch = $this->findDuplicateCaseWithin30Days(
                        $normComp,
                        $normResp,
                        $filedOn,
                        $incidentDate,
                        array_merge($existingCasesForDupCheck, $seenBatchCasesForDupCheck)
                    );

                    if ($dupMatch !== null) {
                        $duplicateCaseRef = $dupMatch['case_number'];
                        $warnings[] = "Possible duplicate case: Same complainant and respondent found in Case #{$dupMatch['case_number']} (filed {$dupMatch['filed_on']}) within 30 days.";
                    }

                    $seenBatchCasesForDupCheck[] = [
                        'id' => null,
                        'case_number' => $caseNumber ?: "Row-{$row->row_number}",
                        'filed_on' => $filedOn,
                        'incident_date' => $incidentDate,
                        'complainants' => [$normComp],
                        'respondents' => [$normResp],
                    ];
                }

                if ($title === '' && $complainantName !== '' && $respondentName !== '') {
                    $typePrefix = $caseTypeName !== '' ? "{$caseTypeName}: " : '';
                    $title = Str::limit("{$typePrefix}{$complainantName} vs. {$respondentName}", 150, '');
                }

                $rowStatus = ImportRow::STATUS_VALID;
                if ($errors !== []) {
                    $rowStatus = ImportRow::STATUS_ERROR;
                    $stats['errors']++;
                    if ($isConflict) {
                        $stats['conflicts']++;
                    }
                } elseif ($warnings !== []) {
                    $rowStatus = ImportRow::STATUS_WARNING;
                    $stats['warnings']++;
                } else {
                    $stats['valid']++;
                }

                $mappedPayload = [
                    'case_number' => $caseNumber,
                    'record_type' => $recordType,
                    'fiscal_year' => $recordYear,
                    'filed_on' => $filedOn,
                    'incident_date' => $incidentDate,
                    'incident_location' => $incidentLocation ?: null,
                    'case_type' => $caseTypeName ?: 'Other Lupon Tagapamayapa Matter',
                    'purok' => $purokName ?: null,
                    'title' => $title ?: 'Imported Historical Case',
                    'narrative' => $narrative,
                    'raw_status' => $rawStatus,
                    'status' => $mappedStatus,
                    'legacy_status_note' => $legacyStatusNote,
                    'settled_on' => $settledOn,
                    'settlement_terms' => $settlementTerms ?: null,
                    'is_confidential' => $isConfidential,
                    'is_conflict' => $isConflict,
                    'duplicate_case_number' => $duplicateCaseRef,
                    'warnings' => $warnings,
                    'complainant' => [
                        'full_name' => $complainantName,
                        'birth_date' => $complainantBirthDate,
                        'contact_number' => $complainantContact ?: null,
                        'address_line' => $complainantAddress ?: null,
                        'residency_status' => $complainantResidency,
                        'exact_party_id' => $complainantMatch['exact_party_id'],
                        'likely_matches' => $complainantMatch['likely_matches'],
                    ],
                    'respondent' => [
                        'full_name' => $respondentName,
                        'birth_date' => $respondentBirthDate,
                        'contact_number' => $respondentContact ?: null,
                        'address_line' => $respondentAddress ?: null,
                        'residency_status' => $respondentResidency,
                        'exact_party_id' => $respondentMatch['exact_party_id'],
                        'likely_matches' => $respondentMatch['likely_matches'],
                    ],
                ];

                $row->update([
                    'mapped' => $mappedPayload,
                    'status' => $rowStatus,
                    'errors' => array_values(array_merge($errors, $warnings)),
                ]);
            }

            $batch->update([
                'status' => ImportBatch::STATUS_PREVIEWED,
                'source_name' => $batch->source_name,
                'mapping' => [
                    'columns' => $cleanColumns,
                    'statuses' => $mergedStatusMap,
                ],
                'stats' => $stats,
            ]);

            $this->audit->log($by, 'import_batch.previewed', $batch, [], $stats);

            return $batch->fresh();
        });
    }

    /**
     * Commit validated rows in chunks, preserving original case numbers and locking historical fiscal years.
     *
     * @param  array<int, int>  $skipRowIds
     */
    public function commitBatch(
        ImportBatch $batch,
        User $by,
        bool $includeWarnings = true,
        array $skipRowIds = [],
        bool $deleteSourceFile = true,
    ): ImportBatch {
        if ($batch->status === ImportBatch::STATUS_COMMITTED) {
            throw new InvalidArgumentException('This import batch has already been committed.');
        }

        if (! in_array($batch->status, [ImportBatch::STATUS_PREVIEWED, ImportBatch::STATUS_MAPPED, ImportBatch::STATUS_ROLLED_BACK], true)) {
            throw new InvalidArgumentException('Please map and preview columns before committing the batch.');
        }

        $skipLookup = array_fill_keys(array_map('intval', $skipRowIds), true);

        return DB::transaction(function () use ($batch, $by, $includeWarnings, $skipLookup, $deleteSourceFile) {
            $activeFy = FiscalYear::where('status', 'active')->first();
            $activeYearInt = $activeFy ? (int) $activeFy->year : (int) now()->year;

            $touchedHistoricalFiscalYears = [];
            $fiscalYearCache = [];

            // Cache CaseTypes and Puroks
            $caseTypes = CaseType::all();
            $defaultCaseType = $caseTypes->firstWhere('slug', 'other_lupon')
                ?? $caseTypes->first()
                ?? CaseType::create([
                    'name' => 'Other Lupon Tagapamayapa Matter',
                    'slug' => 'other_lupon',
                    'description' => 'Historical / general dispute category.',
                    'sort_order' => 99,
                    'is_active' => true,
                    'is_confirmed' => false,
                ]);

            $puroks = Purok::all();

            $importedCount = 0;
            $skippedCount = 0;
            $conflictCount = 0;
            $errorCount = 0;
            $createdPartiesCount = 0;
            $linkedPartiesCount = 0;

            $batch->rows()
                ->orderBy('id')
                ->chunkById(50, function ($rows) use (
                    $batch,
                    $by,
                    $includeWarnings,
                    $skipLookup,
                    $activeYearInt,
                    &$touchedHistoricalFiscalYears,
                    &$fiscalYearCache,
                    $caseTypes,
                    $defaultCaseType,
                    $puroks,
                    &$importedCount,
                    &$skippedCount,
                    &$conflictCount,
                    &$errorCount,
                    &$createdPartiesCount,
                    &$linkedPartiesCount,
                ) {
                    foreach ($rows as $row) {
                        $mapped = $row->mapped ?? [];

                        if ($row->status === ImportRow::STATUS_ERROR || ($mapped['is_conflict'] ?? false)) {
                            $errorCount++;
                            if ($mapped['is_conflict'] ?? false) {
                                $conflictCount++;
                            }
                            $skippedCount++;
                            $row->update(['status' => ImportRow::STATUS_SKIPPED]);

                            continue;
                        }

                        if (isset($skipLookup[(int) $row->id]) || ($row->status === ImportRow::STATUS_WARNING && ! $includeWarnings)) {
                            $skippedCount++;
                            $row->update(['status' => ImportRow::STATUS_SKIPPED]);

                            continue;
                        }

                        $caseNumber = trim((string) ($mapped['case_number'] ?? ''));
                        if ($caseNumber === '' || BarangayCase::where('case_number', $caseNumber)->exists()) {
                            $conflictCount++;
                            $errorCount++;
                            $skippedCount++;
                            $mapped['is_conflict'] = true;
                            $errors = $row->errors ?? [];
                            $errors[] = "Conflict at commit: Case number '{$caseNumber}' is already in use.";
                            $row->update([
                                'mapped' => $mapped,
                                'status' => ImportRow::STATUS_ERROR,
                                'errors' => array_values(array_unique($errors)),
                            ]);

                            continue;
                        }

                        $filedOn = (string) ($mapped['filed_on'] ?? now()->toDateString());
                        $year = (int) ($mapped['fiscal_year'] ?? substr($filedOn, 0, 4));

                        // Rule 3: Auto-create fiscal years if missing, keep open during import, close at end of batch
                        if (! isset($fiscalYearCache[$year])) {
                            $fy = FiscalYear::firstOrCreate(
                                ['year' => $year],
                                [
                                    'label' => "FY {$year}",
                                    'starts_on' => "{$year}-01-01",
                                    'ends_on' => "{$year}-12-31",
                                    'status' => 'inactive',
                                    'is_locked' => false,
                                ]
                            );

                            if ($year !== $activeYearInt) {
                                if ($fy->is_locked || $fy->status === 'closed' || $fy->status === 'locked') {
                                    $fy->update(['status' => 'inactive', 'is_locked' => false]);
                                }
                                $touchedHistoricalFiscalYears[$fy->id] = $fy;
                            }

                            $fiscalYearCache[$year] = $fy;
                        }

                        $fy = $fiscalYearCache[$year];

                        // Match CaseType
                        $rawCaseType = strtolower(trim((string) ($mapped['case_type'] ?? '')));
                        $matchedCaseType = $caseTypes->first(function (CaseType $ct) use ($rawCaseType): bool {
                            return $rawCaseType !== '' && (
                                strtolower($ct->name) === $rawCaseType
                                || strtolower($ct->slug) === $rawCaseType
                                || str_contains(strtolower($ct->name), $rawCaseType)
                            );
                        }) ?? $defaultCaseType;

                        // Match Purok
                        $rawPurok = strtolower(trim((string) ($mapped['purok'] ?? '')));
                        $matchedPurok = $rawPurok !== ''
                            ? $puroks->first(fn (Purok $p): bool => strtolower($p->name) === $rawPurok || str_contains(strtolower($p->name), $rawPurok))
                            : null;

                        $createdPartyIds = [];

                        // Resolve Complainant Party (Rule 4)
                        [$complainantParty, $compCreated] = $this->resolveOrCreateParty(
                            $mapped['complainant'] ?? [],
                            $matchedPurok?->id
                        );
                        if ($compCreated) {
                            $createdPartyIds[] = $complainantParty->id;
                            $createdPartiesCount++;
                        } else {
                            $linkedPartiesCount++;
                        }

                        // Resolve Respondent Party (Rule 4)
                        [$respondentParty, $respCreated] = $this->resolveOrCreateParty(
                            $mapped['respondent'] ?? [],
                            $matchedPurok?->id
                        );
                        if ($respCreated) {
                            $createdPartyIds[] = $respondentParty->id;
                            $createdPartiesCount++;
                        } else {
                            $linkedPartiesCount++;
                        }

                        $recordType = ($mapped['record_type'] ?? 'complaint') === 'blotter' ? 'blotter' : 'complaint';
                        $status = $recordType === 'blotter'
                            ? CaseStatus::FILED
                            : (in_array($mapped['status'] ?? '', CaseStatus::all(), true) ? $mapped['status'] : CaseStatus::FILED);

                        $isConfidential = (bool) ($mapped['is_confidential'] ?? false)
                            || (bool) ($matchedCaseType->is_confidential_default ?? false);

                        $legacyNote = $mapped['legacy_status_note'] ?? null;
                        $settlementTerms = $mapped['settlement_terms'] ?? null;
                        $outcomeNote = $legacyNote
                            ? trim(($settlementTerms ? $settlementTerms.' | ' : '').$legacyNote)
                            : $settlementTerms;

                        // Rule 1 & Rule 2: Keep original case_number, never invoke NumberingService::next()
                        $case = new BarangayCase;
                        $case->forceFill([
                            'import_batch_id' => $batch->id,
                            'case_number' => $caseNumber,
                            'fiscal_year_id' => $fy->id,
                            'case_type_id' => $matchedCaseType->id,
                            'record_type' => $recordType,
                            'source' => 'direct',
                            'title' => (string) ($mapped['title'] ?? 'Imported Case'),
                            'narrative' => (string) ($mapped['narrative'] ?? ''),
                            'filed_on' => $filedOn,
                            'complaint_started_on' => $recordType === 'complaint' ? $filedOn : null,
                            'incident_date' => $mapped['incident_date'] ?? null,
                            'incident_location' => $mapped['incident_location'] ?? null,
                            'purok_id' => $matchedPurok?->id,
                            'status' => $status,
                            'encoded_by' => $by->id,
                            'settled_on' => $status === CaseStatus::SETTLED ? ($mapped['settled_on'] ?? $filedOn) : ($mapped['settled_on'] ?? null),
                            'settlement_terms' => $settlementTerms,
                            'outcome_note' => $outcomeNote,
                            'is_confidential' => $isConfidential,
                        ]);
                        $case->save();

                        CaseParty::firstOrCreate(
                            [
                                'case_id' => $case->id,
                                'party_id' => $complainantParty->id,
                                'role' => 'complainant',
                            ],
                            [
                                'residency_at_filing' => $complainantParty->residency_status ?: 'resident',
                            ]
                        );

                        CaseParty::firstOrCreate(
                            [
                                'case_id' => $case->id,
                                'party_id' => $respondentParty->id,
                                'role' => 'respondent',
                            ],
                            [
                                'residency_at_filing' => $respondentParty->residency_status ?: 'resident',
                            ]
                        );

                        CaseStatusLog::create([
                            'case_id' => $case->id,
                            'user_id' => $by->id,
                            'from_status' => null,
                            'to_status' => $status,
                            'note' => "Imported from legacy file '{$batch->filename}' (Batch #{$batch->id}, Row #{$row->row_number}).",
                            'created_at' => now(),
                        ]);

                        if ($legacyNote) {
                            CaseNote::create([
                                'case_id' => $case->id,
                                'user_id' => $by->id,
                                'type' => 'internal',
                                'body' => $legacyNote,
                            ]);
                        }

                        $row->update([
                            'status' => ImportRow::STATUS_IMPORTED,
                            'case_id' => $case->id,
                            'created_party_ids' => $createdPartyIds,
                        ]);

                        $importedCount++;
                    }
                });

            // Rule 3: Close and lock historical fiscal years at the end of the batch
            foreach ($touchedHistoricalFiscalYears as $historicalFy) {
                $historicalFy->update([
                    'status' => 'closed',
                    'is_locked' => true,
                    'closed_at' => now(),
                    'closed_by' => $by->id,
                    'close_note' => "Auto-closed and locked after legacy import batch #{$batch->id} ({$batch->filename}).",
                ]);
            }

            // Rule 7: Source file can be deleted from private disk after commit
            $storedPath = $batch->stored_path;
            if ($deleteSourceFile && $storedPath) {
                Storage::disk('private')->delete($storedPath);
                $storedPath = null;
            }

            $updatedStats = array_merge($batch->stats ?? [], [
                'imported' => $importedCount,
                'skipped' => $skippedCount,
                'conflicts' => $conflictCount,
                'errors' => $errorCount,
                'created_parties' => $createdPartiesCount,
                'linked_parties' => $linkedPartiesCount,
                'historical_years_closed' => array_values(array_map(fn (FiscalYear $f) => $f->year, $touchedHistoricalFiscalYears)),
            ]);

            $batch->update([
                'status' => ImportBatch::STATUS_COMMITTED,
                'stored_path' => $storedPath,
                'stats' => $updatedStats,
                'committed_at' => now(),
            ]);

            $this->audit->log($by, 'import_batch.committed', $batch, [], $updatedStats);

            return $batch->fresh();
        });
    }

    /**
     * Roll back an entire committed import batch cleanly.
     */
    public function rollbackBatch(ImportBatch $batch, User $by, string $note): ImportBatch
    {
        if ($batch->status !== ImportBatch::STATUS_COMMITTED) {
            throw new InvalidArgumentException('Only committed import batches can be rolled back.');
        }

        return DB::transaction(function () use ($batch, $by, $note) {
            $partyIdsToInspect = [];

            $batch->rows()
                ->orderBy('id')
                ->chunkById(100, function ($rows) use (&$partyIdsToInspect) {
                    foreach ($rows as $row) {
                        foreach ($row->created_party_ids ?? [] as $pid) {
                            $partyIdsToInspect[(int) $pid] = (int) $pid;
                        }
                    }
                });

            $deletedCasesCount = BarangayCase::where('import_batch_id', $batch->id)->count();
            BarangayCase::where('import_batch_id', $batch->id)->delete();

            $deletedPartiesCount = 0;
            if ($partyIdsToInspect !== []) {
                $deletedPartiesCount = Party::whereIn('id', array_values($partyIdsToInspect))
                    ->whereDoesntHave('caseParties')
                    ->delete();
            }

            $batch->rows()
                ->whereIn('status', [ImportRow::STATUS_IMPORTED, ImportRow::STATUS_SKIPPED])
                ->chunkById(100, function ($rows) {
                    foreach ($rows as $row) {
                        $mapped = $row->mapped ?? [];
                        $restoredStatus = ($mapped['is_conflict'] ?? false)
                            ? ImportRow::STATUS_ERROR
                            : (! empty($mapped['warnings']) ? ImportRow::STATUS_WARNING : ImportRow::STATUS_VALID);

                        $row->update([
                            'status' => $restoredStatus,
                            'case_id' => null,
                            'created_party_ids' => null,
                        ]);
                    }
                });

            $stats = array_merge($batch->stats ?? [], [
                'imported' => 0,
                'rolled_back_cases' => $deletedCasesCount,
                'rolled_back_parties' => $deletedPartiesCount,
            ]);

            $batch->update([
                'status' => ImportBatch::STATUS_ROLLED_BACK,
                'stats' => $stats,
                'rolled_back_at' => now(),
                'rolled_back_by' => $by->id,
                'rollback_note' => $note,
            ]);

            $this->audit->log($by, 'import_batch.rolled_back', $batch, ['status' => ImportBatch::STATUS_COMMITTED], [
                'status' => ImportBatch::STATUS_ROLLED_BACK,
                'rolled_back_cases' => $deletedCasesCount,
                'rolled_back_parties' => $deletedPartiesCount,
                'note' => $note,
            ]);

            return $batch->fresh();
        });
    }

    /**
     * Default status mapping from common legacy strings to built CaseStatus constants.
     *
     * @return array<string, string>
     */
    public function defaultStatusMap(): array
    {
        return [
            'filed' => CaseStatus::FILED,
            'open' => CaseStatus::FILED,
            'pending' => CaseStatus::FILED,
            'blotter' => CaseStatus::FILED,
            'for mediation' => CaseStatus::IN_MEDIATION,
            'in mediation' => CaseStatus::IN_MEDIATION,
            'mediation' => CaseStatus::IN_MEDIATION,
            'in_mediation' => CaseStatus::IN_MEDIATION,
            'for conciliation' => CaseStatus::IN_CONCILIATION,
            'in conciliation' => CaseStatus::IN_CONCILIATION,
            'conciliation' => CaseStatus::IN_CONCILIATION,
            'pangkat' => CaseStatus::IN_CONCILIATION,
            'in_conciliation' => CaseStatus::IN_CONCILIATION,
            'settled' => CaseStatus::SETTLED,
            'amicably settled' => CaseStatus::SETTLED,
            'resolved' => CaseStatus::SETTLED,
            'agreement reached' => CaseStatus::SETTLED,
            'unsettled' => CaseStatus::UNSETTLED,
            'failed mediation' => CaseStatus::UNSETTLED,
            'impasse' => CaseStatus::UNSETTLED,
            'escalated' => CaseStatus::ESCALATED,
            'cfa issued' => CaseStatus::ESCALATED,
            'endorsed' => CaseStatus::ESCALATED,
            'referred to court' => CaseStatus::ESCALATED,
            'dismissed' => CaseStatus::DISMISSED,
            'closed' => CaseStatus::DISMISSED,
            'withdrawn' => CaseStatus::WITHDRAWN,
        ];
    }

    /**
     * Rule 4: Exact normalized name + birth date or contact number links to existing Party;
     * name-only matches go to a likely-match review list and never auto-merge.
     *
     * @param  array<string, array<int, Party>>  $partiesByNormalizedName
     * @return array{exact_party_id: ?int, likely_matches: array<int, array{id: int, full_name: string, birth_date: ?string, contact_number: ?string}>, warning: ?string}
     */
    protected function matchParty(
        string $fullName,
        ?string $birthDate,
        string $contactNumber,
        array $partiesByNormalizedName,
    ): array {
        $normName = $this->normalizeName($fullName);
        if ($normName === '' || ! isset($partiesByNormalizedName[$normName])) {
            return [
                'exact_party_id' => null,
                'likely_matches' => [],
                'warning' => null,
            ];
        }

        $normPhone = $this->normalizePhone($contactNumber);
        $candidates = $partiesByNormalizedName[$normName];
        $likelyMatches = [];

        foreach ($candidates as $candidate) {
            $candDob = $candidate->birth_date?->toDateString();
            $candPhone = $this->normalizePhone((string) $candidate->contact_number);

            $dobMatches = $birthDate !== null && $candDob !== null && $birthDate === $candDob;
            $phoneMatches = $normPhone !== '' && $candPhone !== '' && $normPhone === $candPhone;

            if ($dobMatches || $phoneMatches) {
                return [
                    'exact_party_id' => $candidate->id,
                    'likely_matches' => [],
                    'warning' => null,
                ];
            }

            $likelyMatches[] = [
                'id' => $candidate->id,
                'full_name' => $candidate->full_name,
                'birth_date' => $candDob,
                'contact_number' => $candidate->contact_number,
            ];
        }

        return [
            'exact_party_id' => null,
            'likely_matches' => $likelyMatches,
            'warning' => "Likely existing person match for '{$fullName}' (Party #".implode(', #', array_column($likelyMatches, 'id')).') without matching birth date or contact number. Will create a separate record for manual merge review.',
        ];
    }

    /**
     * Resolve an exact-matched Party or create a new Party record.
     *
     * @param  array<string, mixed>  $partyData
     * @return array{0: Party, 1: bool}
     */
    protected function resolveOrCreateParty(array $partyData, ?int $purokId): array
    {
        $fullName = trim((string) ($partyData['full_name'] ?? 'Unknown Party'));
        $birthDate = $partyData['birth_date'] ?? null;
        $contact = $partyData['contact_number'] ?? null;
        $normName = $this->normalizeName($fullName);
        $normPhone = $this->normalizePhone((string) $contact);

        if (! empty($partyData['exact_party_id'])) {
            $existing = Party::find((int) $partyData['exact_party_id']);
            if ($existing) {
                return [$existing, false];
            }
        }

        // Re-check exact match in DB (in case an earlier row in the same batch created this exact person!)
        if ($normName !== '' && ($birthDate !== null || $normPhone !== '')) {
            $candidates = Party::whereRaw('LOWER(full_name) = ?', [$normName])->get();
            foreach ($candidates as $cand) {
                $dobMatch = $birthDate !== null && $cand->birth_date?->toDateString() === $birthDate;
                $phoneMatch = $normPhone !== '' && $this->normalizePhone((string) $cand->contact_number) === $normPhone;
                if ($dobMatch || $phoneMatch) {
                    return [$cand, false];
                }
            }
        }

        $created = Party::create([
            'full_name' => $fullName,
            'birth_date' => $birthDate ?: null,
            'contact_number' => $contact ?: null,
            'address_line' => $partyData['address_line'] ?? null,
            'purok_id' => $purokId,
            'residency_status' => $this->normalizeResidency((string) ($partyData['residency_status'] ?? 'unverified')),
        ]);

        return [$created, true];
    }

    /**
     * Rule 5: Check if an existing case has the same complainant and respondent with a filing or incident date within 30 days.
     *
     * @param  array<int, array{id: ?int, case_number: string, filed_on: ?string, incident_date: ?string, complainants: array<int, string>, respondents: array<int, string>}>  $existingCases
     * @return array{case_number: string, filed_on: string}|null
     */
    protected function findDuplicateCaseWithin30Days(
        string $normComp,
        string $normResp,
        string $filedOn,
        ?string $incidentDate,
        array $existingCases,
    ): ?array {
        $rowDate = Carbon::parse($filedOn);
        $rowIncident = $incidentDate ? Carbon::parse($incidentDate) : null;

        foreach ($existingCases as $existing) {
            if (! in_array($normComp, $existing['complainants'], true)) {
                continue;
            }
            if (! in_array($normResp, $existing['respondents'], true)) {
                continue;
            }

            if ($existing['filed_on']) {
                $existingFiled = Carbon::parse($existing['filed_on']);
                if (abs($rowDate->diffInDays($existingFiled)) <= 30) {
                    return [
                        'case_number' => $existing['case_number'],
                        'filed_on' => $existing['filed_on'],
                    ];
                }
            }

            if ($rowIncident && $existing['incident_date']) {
                $existingInc = Carbon::parse($existing['incident_date']);
                if (abs($rowIncident->diffInDays($existingInc)) <= 30) {
                    return [
                        'case_number' => $existing['case_number'],
                        'filed_on' => $existing['filed_on'] ?? $existing['incident_date'],
                    ];
                }
            }
        }

        return null;
    }

    protected function normalizeName(string $name): string
    {
        $clean = mb_strtolower(trim($name));
        $clean = (string) preg_replace('/[^a-z0-9\s]/u', '', $clean);
        $clean = (string) preg_replace('/\s+/', ' ', $clean);

        return trim($clean);
    }

    protected function normalizePhone(string $phone): string
    {
        $digits = (string) preg_replace('/\D+/', '', $phone);
        if (str_starts_with($digits, '63') && strlen($digits) === 12) {
            $digits = '0'.substr($digits, 2);
        }

        return $digits;
    }

    protected function normalizeResidency(string $value): string
    {
        $norm = strtolower(trim($value));
        $norm = str_replace(['-', ' '], '_', $norm);

        return in_array($norm, ['resident', 'non_resident', 'transient', 'unverified'], true)
            ? $norm
            : 'unverified';
    }

    protected function parseDate(string $raw): ?string
    {
        $raw = trim($raw);
        if ($raw === '') {
            return null;
        }

        // Excel serial date numbers (e.g. 45366)
        if (ctype_digit($raw) && (int) $raw > 30000 && (int) $raw < 60000) {
            return Carbon::create(1899, 12, 30)->addDays((int) $raw)->toDateString();
        }

        try {
            return Carbon::parse($raw)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }
}
