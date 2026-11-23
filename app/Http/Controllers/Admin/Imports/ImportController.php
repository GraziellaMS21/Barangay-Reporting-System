<?php

namespace App\Http\Controllers\Admin\Imports;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Imports\CommitImportBatchRequest;
use App\Http\Requests\Admin\Imports\RollbackImportBatchRequest;
use App\Http\Requests\Admin\Imports\SaveImportMappingRequest;
use App\Http\Requests\Admin\Imports\UploadImportBatchRequest;
use App\Models\ImportBatch;
use App\Services\Imports\LegacyImportService;
use App\Services\Imports\SpreadsheetFileService;
use App\Support\CaseStatus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ImportController extends Controller
{
    public function __construct(
        protected LegacyImportService $importService,
        protected SpreadsheetFileService $spreadsheetService,
    ) {}

    public function index(): View
    {
        $batches = ImportBatch::with(['user', 'rolledBackBy'])
            ->latest()
            ->paginate(25);

        return view('admin.imports.index', [
            'batches' => $batches,
            'savedMappings' => $this->importService->savedMappings(),
            'allowedYears' => $this->importService->allowedYears(),
        ]);
    }

    public function create(): View
    {
        return view('admin.imports.create', [
            'allowedYears' => $this->importService->allowedYears(),
            'savedMappings' => $this->importService->savedMappings(),
            'targetFields' => $this->spreadsheetService->targetFields(),
        ]);
    }

    public function downloadTemplate(Request $request): BinaryFileResponse|StreamedResponse
    {
        $format = strtolower((string) $request->query('format', 'xlsx'));

        if ($format === 'csv') {
            $csv = $this->spreadsheetService->createCsvTemplateString();

            return response()->streamDownload(function () use ($csv): void {
                echo $csv;
            }, 'calarian-legacy-cases-template.csv', [
                'Content-Type' => 'text/csv; charset=UTF-8',
            ]);
        }

        $tempPath = storage_path('app/private/imports/calarian-legacy-cases-template.xlsx');
        $this->spreadsheetService->createXlsxTemplate($tempPath);

        return response()->download(
            $tempPath,
            'calarian-legacy-cases-template.xlsx',
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
        );
    }

    public function store(UploadImportBatchRequest $request): RedirectResponse
    {
        try {
            $batch = $this->importService->uploadBatch(
                $request->file('file'),
                $request->user(),
                $request->filled('target_fiscal_year') ? (int) $request->input('target_fiscal_year') : null,
                $request->filled('source_name') ? (string) $request->input('source_name') : null,
            );
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('admin.imports.map', $batch)
            ->with('success', "File '{$batch->filename}' uploaded ({$batch->stats['total_rows']} rows detected). Please verify the column mapping.");
    }

    public function map(ImportBatch $batch): View
    {
        $sampleRows = $batch->rows()->orderBy('row_number')->limit(3)->get();
        $targetFields = $this->spreadsheetService->targetFields();

        $currentMapping = $batch->mapping['columns'] ?? [];
        $currentStatusMap = $batch->mapping['statuses'] ?? $this->importService->defaultStatusMap();

        // Collect distinct raw status values from the batch if a status column is mapped or guessed
        $statusHeader = $currentMapping['status'] ?? null;
        $distinctRawStatuses = [];
        if ($statusHeader) {
            $distinctRawStatuses = $batch->rows()
                ->limit(250)
                ->get()
                ->map(fn ($r) => trim((string) ($r->raw[$statusHeader] ?? '')))
                ->filter()
                ->unique()
                ->values()
                ->all();
        }

        return view('admin.imports.map', [
            'batch' => $batch,
            'headers' => $batch->headers ?? [],
            'targetFields' => $targetFields,
            'currentMapping' => $currentMapping,
            'currentStatusMap' => $currentStatusMap,
            'distinctRawStatuses' => $distinctRawStatuses,
            'caseStatuses' => CaseStatus::labels(),
            'sampleRows' => $sampleRows,
        ]);
    }

    public function saveMap(SaveImportMappingRequest $request, ImportBatch $batch): RedirectResponse
    {
        try {
            $this->importService->saveMappingAndValidate(
                $batch,
                $request->input('mapping', []),
                $request->input('status_mapping', []),
                $request->user(),
                $request->filled('save_mapping_name') ? (string) $request->input('save_mapping_name') : null,
            );
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('admin.imports.preview', $batch)
            ->with('success', 'Column mapping saved and rows validated. Review warnings or conflicts below before committing.');
    }

    public function preview(Request $request, ImportBatch $batch): View
    {
        $statusFilter = (string) $request->query('status', 'all');

        $rowsQuery = $batch->rows()->orderBy('row_number');
        if (in_array($statusFilter, ['valid', 'warning', 'error', 'imported', 'skipped'], true)) {
            $rowsQuery->where('status', $statusFilter);
        }

        $rows = $rowsQuery->paginate(25)->withQueryString();

        return view('admin.imports.preview', [
            'batch' => $batch,
            'rows' => $rows,
            'statusFilter' => $statusFilter,
            'caseStatusLabels' => CaseStatus::labels(),
        ]);
    }

    public function commit(CommitImportBatchRequest $request, ImportBatch $batch): RedirectResponse
    {
        try {
            $batch = $this->importService->commitBatch(
                $batch,
                $request->user(),
                $request->boolean('include_warnings', true),
                $request->input('skip_row_ids', []),
                $request->boolean('delete_source_file', true),
            );
        } catch (InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        $imported = (int) ($batch->stats['imported'] ?? 0);
        $skipped = (int) ($batch->stats['skipped'] ?? 0);

        return redirect()
            ->route('admin.imports.preview', $batch)
            ->with('success', "Batch #{$batch->id} committed: {$imported} historical case(s) imported, {$skipped} row(s) skipped.");
    }

    public function rollback(RollbackImportBatchRequest $request, ImportBatch $batch): RedirectResponse
    {
        try {
            $batch = $this->importService->rollbackBatch(
                $batch,
                $request->user(),
                (string) $request->input('rollback_note'),
            );
        } catch (InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        $rolledBackCases = (int) ($batch->stats['rolled_back_cases'] ?? 0);

        return redirect()
            ->route('admin.imports.preview', $batch)
            ->with('success', "Batch #{$batch->id} rolled back: {$rolledBackCases} imported case(s) removed.");
    }
}
