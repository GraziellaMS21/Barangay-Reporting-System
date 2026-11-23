<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CaseType;
use App\Models\Category;
use App\Models\FiscalYear;
use App\Models\GeneratedReport;
use App\Models\Office;
use App\Models\Position;
use App\Models\Purok;
use App\Models\Referral;
use App\Models\Report;
use App\Services\FiscalYearContext;
use App\Services\Reporting\ReportGeneratorService;
use App\Support\CaseStatus;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer as XlsxWriter;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportsCenterController extends Controller
{
    public function __construct(
        protected ReportGeneratorService $reportGenerator,
        protected FiscalYearContext $fiscalYearContext,
    ) {}

    public function index(): View
    {
        $templates = $this->reportGenerator->catalog();
        $viewingFiscalYear = $this->fiscalYearContext->viewing();
        $defaultFormat = (string) setting('reports.default_format', 'print');

        return view('admin.reports-center.index', compact(
            'templates',
            'viewingFiscalYear',
            'defaultFormat'
        ));
    }

    public function show(Request $request, string $key): View
    {
        $filters = $request->only([
            'fiscal_year_id',
            'compare_fiscal_year_id',
            'month',
            'date_from',
            'date_to',
            'purok_id',
            'category_id',
            'case_type_id',
            'office_id',
            'position_id',
            'status',
            'service_result',
        ]);

        $reportData = $this->reportGenerator->build($key, $filters, $request->user());

        $filterOptions = [
            'fiscalYears' => FiscalYear::query()->orderByDesc('year')->get(),
            'puroks' => Purok::query()->orderBy('name')->get(),
            'categories' => Category::query()->where('is_active', true)->orderBy('name')->get(),
            'caseTypes' => CaseType::query()->orderBy('sort_order')->orderBy('name')->get(),
            'offices' => Office::query()->orderBy('name')->get(),
            'positions' => Position::query()->orderBy('sort_order')->orderBy('name')->get(),
            'caseStatuses' => CaseStatus::labels(),
            'reportStatuses' => Report::STATUSES,
            'referralStatuses' => Referral::STATUSES,
            'serviceResults' => [
                'pending' => 'Pending',
                'received' => 'Received',
                'refused' => 'Refused',
                'not_found' => 'Not found',
                'other' => 'Other',
            ],
            'months' => [
                1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
                5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
                9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December',
            ],
        ];

        $paperSize = (string) setting('reports.paper_size', 'a4');
        $defaultFormat = (string) setting('reports.default_format', 'print');

        if ($request->boolean('print')) {
            $this->reportGenerator->recordGeneration(
                $reportData['template'],
                $filters,
                'print',
                $reportData['fiscal_year'],
                $request->user()
            );

            return view('admin.reports-center.print', [
                ...$reportData,
                'paperSize' => $paperSize,
            ]);
        }

        return view('admin.reports-center.show', [
            ...$reportData,
            'filters' => $filters,
            'filterOptions' => $filterOptions,
            'paperSize' => $paperSize,
            'defaultFormat' => $defaultFormat,
        ]);
    }

    public function export(Request $request, string $key): Response|StreamedResponse|BinaryFileResponse
    {
        $format = strtolower((string) $request->input('format', 'csv'));
        if (! in_array($format, ['print', 'pdf', 'xlsx', 'excel', 'csv'], true)) {
            $format = 'csv';
        }
        if ($format === 'excel') {
            $format = 'xlsx';
        }

        $filters = $request->only([
            'fiscal_year_id',
            'compare_fiscal_year_id',
            'month',
            'date_from',
            'date_to',
            'purok_id',
            'category_id',
            'case_type_id',
            'office_id',
            'position_id',
            'status',
            'service_result',
        ]);

        $reportData = $this->reportGenerator->build($key, $filters, $request->user());
        $template = $reportData['template'];
        $paperSize = (string) setting('reports.paper_size', 'a4');

        $this->reportGenerator->recordGeneration(
            $template,
            $filters,
            $format,
            $reportData['fiscal_year'],
            $request->user()
        );

        return $this->streamExport($key, $format, $reportData, $paperSize);
    }

    public function history(): View
    {
        $generatedReports = GeneratedReport::query()
            ->with(['reportTemplate', 'fiscalYear', 'generatedBy'])
            ->latest('id')
            ->paginate(25);

        return view('admin.reports-center.history', compact('generatedReports'));
    }

    public function download(Request $request, GeneratedReport $generatedReport): Response|StreamedResponse|BinaryFileResponse
    {
        $key = $generatedReport->report_key ?: ($generatedReport->reportTemplate?->key ?? 'case_summary');
        $params = is_array($generatedReport->params) ? $generatedReport->params : [];
        if ($generatedReport->fiscal_year_id && ! isset($params['fiscal_year_id'])) {
            $params['fiscal_year_id'] = $generatedReport->fiscal_year_id;
        }

        $format = strtolower($generatedReport->format ?: 'csv');
        $reportData = $this->reportGenerator->build($key, $params, $request->user());
        $paperSize = (string) setting('reports.paper_size', 'a4');

        return $this->streamExport($key, $format, $reportData, $paperSize);
    }

    /**
     * @param  array<string, mixed>  $reportData
     */
    protected function streamExport(
        string $key,
        string $format,
        array $reportData,
        string $paperSize
    ): Response|StreamedResponse|BinaryFileResponse {
        $template = $reportData['template'];
        $columns = $reportData['columns'];
        $rows = $reportData['rows'];
        $totals = $reportData['totals'];

        if ($format === 'print') {
            $html = view('admin.reports-center.print', [
                ...$reportData,
                'paperSize' => $paperSize,
            ])->render();

            return response($html, 200, ['Content-Type' => 'text/html']);
        }

        if ($format === 'pdf') {
            $dompdfPaper = match (strtolower($paperSize)) {
                'letter' => 'letter',
                'legal', 'long', 'long_bond' => 'legal',
                default => 'a4',
            };
            $orientation = $template->orientation === 'landscape' ? 'landscape' : 'portrait';

            if (class_exists(Pdf::class)) {
                $pdf = Pdf::loadView('admin.reports-center.print', [
                    ...$reportData,
                    'paperSize' => $paperSize,
                    'isPdf' => true,
                ])->setPaper($dompdfPaper, $orientation);

                return $pdf->download("report_{$key}.pdf");
            }

            $html = view('admin.reports-center.print', [
                ...$reportData,
                'paperSize' => $paperSize,
                'isPdf' => true,
            ])->render();

            return response($html, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => "attachment; filename=\"report_{$key}.pdf\"",
            ]);
        }

        if ($format === 'xlsx') {
            if (class_exists(XlsxWriter::class)) {
                $tempPath = tempnam(sys_get_temp_dir(), 'brg_report_').'.xlsx';
                $writer = new XlsxWriter;
                $writer->openToFile($tempPath);

                $writer->addRow(Row::fromValues([$template->title]));
                $writer->addRow(Row::fromValues([
                    (string) setting('barangay.name', 'Barangay Calarian').' · '.(string) setting('barangay.city', 'Zamboanga City'),
                ]));
                $writer->addRow(Row::fromValues(array_column($columns, 'label')));

                foreach ($rows as $row) {
                    $line = [];
                    foreach ($columns as $col) {
                        $line[] = (string) ($row[$col['key']] ?? '');
                    }
                    $writer->addRow(Row::fromValues($line));
                }

                if (! empty($totals)) {
                    $totalLine = [];
                    foreach ($columns as $col) {
                        $totalLine[] = (string) ($totals[$col['key']] ?? '');
                    }
                    $writer->addRow(Row::fromValues($totalLine));
                }

                $writer->close();

                return response()->download($tempPath, "report_{$key}.xlsx", [
                    'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                ])->deleteFileAfterSend(true);
            }

            $xml = $this->buildSpreadsheetXml($template->title, $columns, $rows, $totals);

            return response($xml, 200, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Content-Disposition' => "attachment; filename=\"report_{$key}.xlsx\"",
            ]);
        }

        // Default CSV export
        $handle = fopen('php://temp', 'r+');
        fputcsv($handle, array_column($columns, 'label'));
        foreach ($rows as $row) {
            $line = [];
            foreach ($columns as $col) {
                $line[] = (string) ($row[$col['key']] ?? '');
            }
            fputcsv($handle, $line);
        }
        if (! empty($totals)) {
            $totalLine = [];
            foreach ($columns as $col) {
                $totalLine[] = (string) ($totals[$col['key']] ?? '');
            }
            fputcsv($handle, $totalLine);
        }
        rewind($handle);
        $csvContent = (string) stream_get_contents($handle);
        fclose($handle);

        return response($csvContent, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"report_{$key}.csv\"",
        ]);
    }

    /**
     * @param  array<int, array<string, string>>  $columns
     * @param  array<int, array<string, mixed>>  $rows
     * @param  array<string, mixed>  $totals
     */
    protected function buildSpreadsheetXml(string $title, array $columns, array $rows, array $totals): string
    {
        $esc = fn (string $v): string => htmlspecialchars($v, ENT_XML1 | ENT_QUOTES, 'UTF-8');
        $out = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        $out .= '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet" xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">';
        $out .= '<Worksheet ss:Name="'.$esc(mb_substr($title, 0, 30)).'"><Table>';

        $out .= '<Row>';
        foreach ($columns as $col) {
            $out .= '<Cell><Data ss:Type="String">'.$esc((string) $col['label']).'</Data></Cell>';
        }
        $out .= '</Row>';

        foreach ($rows as $row) {
            $out .= '<Row>';
            foreach ($columns as $col) {
                $val = (string) ($row[$col['key']] ?? '');
                $out .= '<Cell><Data ss:Type="String">'.$esc($val).'</Data></Cell>';
            }
            $out .= '</Row>';
        }

        if (! empty($totals)) {
            $out .= '<Row>';
            foreach ($columns as $col) {
                $val = (string) ($totals[$col['key']] ?? '');
                $out .= '<Cell><Data ss:Type="String">'.$esc($val).'</Data></Cell>';
            }
            $out .= '</Row>';
        }

        $out .= '</Table></Worksheet></Workbook>';

        return $out;
    }
}
