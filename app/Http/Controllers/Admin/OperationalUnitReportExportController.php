<?php

namespace App\Http\Controllers\Admin;

use App\Exports\OperationalUnitReportExport;
use App\Http\Controllers\Controller;
use App\Services\OperationalUnitReportService;
use Gate;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class OperationalUnitReportExportController extends Controller
{
    public function export(Request $request, OperationalUnitReportService $service, OperationalUnitReportExport $exporter)
    {
        abort_if(Gate::denies('account_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $validated = $request->validate([
            'from' => ['nullable', 'date_format:' . config('panel.date_format')],
            'to' => ['nullable', 'date_format:' . config('panel.date_format')],
        ]);

        $report = $service->buildReport($validated['from'] ?? null, $validated['to'] ?? null);
        $units = $report['units'];
        $fromDate = $report['from']->format(config('panel.date_format'));
        $toDate = $report['to']->format(config('panel.date_format'));

        $csv = $exporter->toCsv($units, $fromDate, $toDate);
        $filename = sprintf('relatorio-unidades-%s-%s.csv', $fromDate, $toDate);

        return response()->streamDownload(function () use ($csv) {
            echo $csv;
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
