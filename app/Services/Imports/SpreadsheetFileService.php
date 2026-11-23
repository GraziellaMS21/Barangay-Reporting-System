<?php

namespace App\Services\Imports;

use InvalidArgumentException;
use OpenSpout\Common\Entity\Row as OpenSpoutRow;
use OpenSpout\Reader\XLSX\Reader as OpenSpoutXlsxReader;
use OpenSpout\Writer\XLSX\Writer as OpenSpoutXlsxWriter;
use SimpleXMLElement;
use ZipArchive;

class SpreadsheetFileService
{
    /**
     * Canonical target fields for legacy case, blotter, and party import.
     *
     * @return array<string, array{label: string, required: bool, sample: string, aliases: array<int, string>}>
     */
    public function targetFields(): array
    {
        return [
            'case_number' => [
                'label' => 'Case / Docket Number',
                'required' => true,
                'sample' => '03-014-2024',
                'aliases' => ['case_number', 'case no', 'case no.', 'case #', 'docket no', 'docket number', 'blotter no', 'case_no'],
            ],
            'record_type' => [
                'label' => 'Record Type (complaint / blotter)',
                'required' => false,
                'sample' => 'complaint',
                'aliases' => ['record_type', 'record type', 'type of record', 'entry type', 'kind'],
            ],
            'filed_on' => [
                'label' => 'Filed Date (YYYY-MM-DD)',
                'required' => true,
                'sample' => '2024-03-15',
                'aliases' => ['filed_on', 'filed date', 'date filed', 'filing date', 'date_filed', 'date recorded'],
            ],
            'incident_date' => [
                'label' => 'Incident Date (YYYY-MM-DD)',
                'required' => false,
                'sample' => '2024-03-12',
                'aliases' => ['incident_date', 'incident date', 'date of incident', 'occurrence date'],
            ],
            'incident_location' => [
                'label' => 'Incident Location',
                'required' => false,
                'sample' => 'Sitio San Roque, Purok 2, Calarian',
                'aliases' => ['incident_location', 'incident location', 'place of incident', 'location'],
            ],
            'case_type' => [
                'label' => 'Case Type / Nature of Dispute',
                'required' => false,
                'sample' => 'Boundary and Property Line Dispute',
                'aliases' => ['case_type', 'case type', 'nature of case', 'dispute type', 'category', 'offense'],
            ],
            'purok' => [
                'label' => 'Purok / Zone',
                'required' => false,
                'sample' => 'Purok 2',
                'aliases' => ['purok', 'zone', 'purok_name', 'purok / zone'],
            ],
            'title' => [
                'label' => 'Case Title',
                'required' => false,
                'sample' => 'Dela Cruz vs. Santos - Boundary Dispute',
                'aliases' => ['title', 'case title', 'subject', 'caption'],
            ],
            'narrative' => [
                'label' => 'Complaint / Blotter Narrative',
                'required' => true,
                'sample' => 'Complainant reported boundary fence encroachment by respondent along the eastern lot marker.',
                'aliases' => ['narrative', 'complaint narrative', 'details', 'description', 'summary', 'facts', 'blotter narrative'],
            ],
            'status' => [
                'label' => 'Case Status',
                'required' => false,
                'sample' => 'Settled',
                'aliases' => ['status', 'case status', 'legacy status', 'disposition', 'current status'],
            ],
            'complainant_full_name' => [
                'label' => 'Complainant Full Name',
                'required' => true,
                'sample' => 'Juan M. Dela Cruz',
                'aliases' => ['complainant_full_name', 'complainant', 'complainant name', 'complainant full name'],
            ],
            'complainant_birth_date' => [
                'label' => 'Complainant Birth Date (YYYY-MM-DD)',
                'required' => false,
                'sample' => '1982-05-14',
                'aliases' => ['complainant_birth_date', 'complainant birth date', 'complainant dob', 'complainant birthdate'],
            ],
            'complainant_contact' => [
                'label' => 'Complainant Contact Number',
                'required' => false,
                'sample' => '09171234567',
                'aliases' => ['complainant_contact', 'complainant contact', 'complainant phone', 'complainant mobile', 'complainant contact number'],
            ],
            'complainant_address' => [
                'label' => 'Complainant Address',
                'required' => false,
                'sample' => 'Purok 2, Calarian, Zamboanga City',
                'aliases' => ['complainant_address', 'complainant address', 'address'],
            ],
            'complainant_residency' => [
                'label' => 'Complainant Residency Status',
                'required' => false,
                'sample' => 'resident',
                'aliases' => ['complainant_residency', 'complainant residency', 'complainant residency status'],
            ],
            'respondent_full_name' => [
                'label' => 'Respondent Full Name',
                'required' => true,
                'sample' => 'Pedro R. Santos',
                'aliases' => ['respondent_full_name', 'respondent', 'respondent name', 'respondent full name'],
            ],
            'respondent_birth_date' => [
                'label' => 'Respondent Birth Date (YYYY-MM-DD)',
                'required' => false,
                'sample' => '1979-11-03',
                'aliases' => ['respondent_birth_date', 'respondent birth date', 'respondent dob', 'respondent birthdate'],
            ],
            'respondent_contact' => [
                'label' => 'Respondent Contact Number',
                'required' => false,
                'sample' => '09187654321',
                'aliases' => ['respondent_contact', 'respondent contact', 'respondent phone', 'respondent mobile', 'respondent contact number'],
            ],
            'respondent_address' => [
                'label' => 'Respondent Address',
                'required' => false,
                'sample' => 'Purok 2, Calarian, Zamboanga City',
                'aliases' => ['respondent_address', 'respondent address'],
            ],
            'respondent_residency' => [
                'label' => 'Respondent Residency Status',
                'required' => false,
                'sample' => 'resident',
                'aliases' => ['respondent_residency', 'respondent residency', 'respondent residency status'],
            ],
            'settled_on' => [
                'label' => 'Settled Date (YYYY-MM-DD)',
                'required' => false,
                'sample' => '2024-03-28',
                'aliases' => ['settled_on', 'settled date', 'date settled', 'resolution date'],
            ],
            'settlement_terms' => [
                'label' => 'Settlement Terms / Outcome Note',
                'required' => false,
                'sample' => 'Both parties agreed to relocate the perimeter fence by 0.5 meters.',
                'aliases' => ['settlement_terms', 'settlement terms', 'outcome note', 'remarks', 'agreement terms'],
            ],
            'is_confidential' => [
                'label' => 'Confidential Case (yes / no)',
                'required' => false,
                'sample' => 'no',
                'aliases' => ['is_confidential', 'confidential', 'sensitive'],
            ],
        ];
    }

    /**
     * Automatically guess column mapping from source headers.
     *
     * @param  array<int, string>  $headers
     * @return array<string, string>
     */
    public function guessMapping(array $headers): array
    {
        $mapping = [];
        $normalizedHeaders = [];

        foreach ($headers as $header) {
            $norm = $this->normalizeHeader($header);
            if ($norm !== '') {
                $normalizedHeaders[$norm] = $header;
            }
        }

        foreach ($this->targetFields() as $fieldKey => $config) {
            $candidates = array_merge([$fieldKey, $config['label']], $config['aliases']);
            foreach ($candidates as $candidate) {
                $normCandidate = $this->normalizeHeader($candidate);
                if (isset($normalizedHeaders[$normCandidate])) {
                    $mapping[$fieldKey] = $normalizedHeaders[$normCandidate];
                    break;
                }
            }
        }

        return $mapping;
    }

    /**
     * Parse an uploaded .xlsx or .csv file into headers and associative rows.
     *
     * @return array{headers: array<int, string>, rows: array<int, array<string, string>>}
     */
    public function parseFile(string $absolutePath, ?string $extension = null): array
    {
        if (! is_file($absolutePath)) {
            throw new InvalidArgumentException('Uploaded import file could not be found on disk.');
        }

        $ext = strtolower($extension ?: pathinfo($absolutePath, PATHINFO_EXTENSION));

        // Detect ZIP/XLSX magic header PK\x03\x04 even if temporary extension differs
        $firstBytes = (string) file_get_contents($absolutePath, false, null, 0, 4);
        if ($ext === 'xlsx' || str_starts_with($firstBytes, "PK\x03\x04")) {
            $rawGrid = $this->readXlsxGrid($absolutePath);
        } else {
            $rawGrid = $this->readCsvGrid($absolutePath);
        }

        if ($rawGrid === []) {
            return ['headers' => [], 'rows' => []];
        }

        $headerRow = array_shift($rawGrid);
        $headers = [];
        foreach ($headerRow as $idx => $cell) {
            $clean = trim((string) preg_replace('/^\xEF\xBB\xBF/', '', (string) $cell));
            $headers[$idx] = $clean !== '' ? $clean : 'Column_'.($idx + 1);
        }

        $rows = [];
        foreach ($rawGrid as $cells) {
            $assoc = [];
            $hasNonEmpty = false;

            foreach ($headers as $idx => $headerName) {
                $val = isset($cells[$idx]) ? trim((string) $cells[$idx]) : '';
                if ($val !== '') {
                    $hasNonEmpty = true;
                }
                $assoc[$headerName] = $val;
            }

            if ($hasNonEmpty) {
                $rows[] = $assoc;
            }
        }

        return [
            'headers' => array_values($headers),
            'rows' => $rows,
        ];
    }

    /**
     * Write a blank downloadable Excel (.xlsx) template file and return its path.
     *
     * @param  array<int, array<int, string>>|null  $customRows
     */
    public function createXlsxTemplate(string $targetPath, ?array $customRows = null): string
    {
        $fields = $this->targetFields();
        $headers = array_keys($fields);
        $sampleRow1 = array_values(array_map(fn (array $f): string => $f['sample'], $fields));
        $sampleRow2 = [
            '06-002-2023',
            'blotter',
            '2023-06-10',
            '2023-06-09',
            'Near Basketball Court, Purok 4, Calarian',
            'Neighborhood Noise and Disturbance',
            'Purok 4',
            'Reyes vs. Gomez - Late Night Videoke Noise',
            'For record purposes only: complainant requested blotter entry regarding loud videoke past midnight.',
            'Filed',
            'Maria L. Reyes',
            '1990-02-20',
            '09191112233',
            'Purok 4, Calarian, Zamboanga City',
            'resident',
            'Roberto D. Gomez',
            '1985-08-19',
            '09194445566',
            'Purok 4, Calarian, Zamboanga City',
            'resident',
            '',
            '',
            'no',
        ];

        $grid = $customRows ?? [$headers, $sampleRow1, $sampleRow2];
        $this->writeXlsxGrid($targetPath, $grid);

        return $targetPath;
    }

    /**
     * Write a grid of rows to an .xlsx file using OpenSpout if available or native ZipArchive OpenXML.
     *
     * @param  array<int, array<int, scalar|null>>  $grid
     */
    public function writeXlsxGrid(string $targetPath, array $grid): void
    {
        $dir = dirname($targetPath);
        if (! is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        if (class_exists(OpenSpoutXlsxWriter::class)) {
            $writer = new OpenSpoutXlsxWriter;
            $writer->openToFile($targetPath);
            foreach ($grid as $row) {
                $writer->addRow(OpenSpoutRow::fromValues(array_map(fn ($v) => (string) ($v ?? ''), $row)));
            }
            $writer->close();

            return;
        }

        $zip = new ZipArchive;
        if ($zip->open($targetPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new InvalidArgumentException('Unable to create XLSX archive.');
        }

        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            .'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            .'<Default Extension="xml" ContentType="application/xml"/>'
            .'<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            .'<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            .'</Types>');

        $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            .'</Relationships>');

        $zip->addFromString('xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            .'<sheets><sheet name="Legacy Cases" sheetId="1" r:id="rId1"/></sheets>'
            .'</workbook>');

        $zip->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            .'</Relationships>');

        $sheetXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>';

        foreach (array_values($grid) as $rIdx => $row) {
            $rowNum = $rIdx + 1;
            $sheetXml .= '<row r="'.$rowNum.'">';
            foreach (array_values($row) as $cIdx => $val) {
                $colLetter = $this->columnIndexToLetter($cIdx);
                $escaped = htmlspecialchars((string) ($val ?? ''), ENT_XML1 | ENT_QUOTES, 'UTF-8');
                $sheetXml .= '<c r="'.$colLetter.$rowNum.'" t="inlineStr"><is><t>'.$escaped.'</t></is></c>';
            }
            $sheetXml .= '</row>';
        }

        $sheetXml .= '</sheetData></worksheet>';
        $zip->addFromString('xl/worksheets/sheet1.xml', $sheetXml);
        $zip->close();
    }

    /**
     * Build CSV content for the blank template.
     */
    public function createCsvTemplateString(): string
    {
        $fields = $this->targetFields();
        $headers = array_keys($fields);
        $sampleRow = array_values(array_map(fn (array $f): string => $f['sample'], $fields));

        $fh = fopen('php://temp', 'r+');
        fputcsv($fh, $headers);
        fputcsv($fh, $sampleRow);
        rewind($fh);
        $csv = (string) stream_get_contents($fh);
        fclose($fh);

        return $csv;
    }

    /**
     * @return array<int, array<int, string>>
     */
    protected function readCsvGrid(string $absolutePath): array
    {
        $fh = fopen($absolutePath, 'r');
        if ($fh === false) {
            return [];
        }

        $rows = [];
        while (($data = fgetcsv($fh)) !== false) {
            if ($data === [null]) {
                continue;
            }
            $rows[] = array_map(fn ($v) => trim((string) ($v ?? '')), $data);
        }
        fclose($fh);

        return $rows;
    }

    /**
     * @return array<int, array<int, string>>
     */
    protected function readXlsxGrid(string $absolutePath): array
    {
        if (class_exists(OpenSpoutXlsxReader::class)) {
            $reader = new OpenSpoutXlsxReader;
            $reader->open($absolutePath);
            $rows = [];
            foreach ($reader->getSheetIterator() as $sheet) {
                foreach ($sheet->getRowIterator() as $row) {
                    $cells = [];
                    foreach ($row->getCells() as $cell) {
                        $val = $cell->getValue();
                        if ($val instanceof \DateTimeInterface) {
                            $cells[] = $val->format('Y-m-d');
                        } else {
                            $cells[] = trim((string) ($val ?? ''));
                        }
                    }
                    $rows[] = $cells;
                }
                break;
            }
            $reader->close();

            return $rows;
        }

        $zip = new ZipArchive;
        if ($zip->open($absolutePath) !== true) {
            throw new InvalidArgumentException('Unable to open XLSX archive.');
        }

        $sharedStrings = [];
        $sharedXml = $zip->getFromName('xl/sharedStrings.xml');
        if (is_string($sharedXml) && $sharedXml !== '') {
            $sx = @simplexml_load_string($sharedXml);
            if ($sx instanceof SimpleXMLElement) {
                foreach ($sx->si as $si) {
                    if (isset($si->t)) {
                        $sharedStrings[] = (string) $si->t;
                    } else {
                        $parts = [];
                        foreach ($si->r as $run) {
                            $parts[] = (string) $run->t;
                        }
                        $sharedStrings[] = implode('', $parts);
                    }
                }
            }
        }

        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();

        if (! is_string($sheetXml) || $sheetXml === '') {
            return [];
        }

        $wx = @simplexml_load_string($sheetXml);
        if (! $wx instanceof SimpleXMLElement || ! isset($wx->sheetData)) {
            return [];
        }

        $grid = [];
        $maxCol = 0;

        foreach ($wx->sheetData->row as $rowNode) {
            $rowCells = [];
            $nextColIndex = 0;

            foreach ($rowNode->c as $cNode) {
                $ref = (string) ($cNode['r'] ?? '');
                $colIdx = $ref !== '' ? $this->cellReferenceToColumnIndex($ref) : $nextColIndex;
                $nextColIndex = $colIdx + 1;
                $maxCol = max($maxCol, $colIdx);

                $type = (string) ($cNode['t'] ?? '');
                $value = '';

                if ($type === 's') {
                    $sIdx = (int) ($cNode->v ?? 0);
                    $value = $sharedStrings[$sIdx] ?? '';
                } elseif ($type === 'inlineStr') {
                    if (isset($cNode->is->t)) {
                        $value = (string) $cNode->is->t;
                    } elseif (isset($cNode->is->r)) {
                        $parts = [];
                        foreach ($cNode->is->r as $run) {
                            $parts[] = (string) $run->t;
                        }
                        $value = implode('', $parts);
                    }
                } else {
                    $value = (string) ($cNode->v ?? '');
                }

                $rowCells[$colIdx] = trim($value);
            }

            $grid[] = $rowCells;
        }

        $normalizedGrid = [];
        foreach ($grid as $rowCells) {
            $filled = [];
            for ($i = 0; $i <= $maxCol; $i++) {
                $filled[$i] = $rowCells[$i] ?? '';
            }
            $normalizedGrid[] = $filled;
        }

        return $normalizedGrid;
    }

    protected function normalizeHeader(string $value): string
    {
        $clean = strtolower(trim($value));
        $clean = (string) preg_replace('/[^a-z0-9]+/', '_', $clean);

        return trim($clean, '_');
    }

    protected function columnIndexToLetter(int $index): string
    {
        $letter = '';
        $n = $index + 1;
        while ($n > 0) {
            $rem = ($n - 1) % 26;
            $letter = chr(65 + $rem).$letter;
            $n = intdiv($n - 1, 26);
        }

        return $letter;
    }

    protected function cellReferenceToColumnIndex(string $cellRef): int
    {
        if (! preg_match('/^([A-Z]+)/i', $cellRef, $m)) {
            return 0;
        }

        $letters = strtoupper($m[1]);
        $index = 0;
        $len = strlen($letters);
        for ($i = 0; $i < $len; $i++) {
            $index = ($index * 26) + (ord($letters[$i]) - 64);
        }

        return max(0, $index - 1);
    }
}
