<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\OperationalUnitReportService;
use Gate;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class OperationalUnitReportController extends Controller
{
    public function index(Request $request, OperationalUnitReportService $service)
    {
        abort_if(Gate::denies('account_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $from = $request->get('from');
        $to = $request->get('to');

        $report = $service->buildReport($from, $to);
        $units = $report['units'];
        $fromDate = $report['from'];
        $toDate = $report['to'];

        $totalCost = (float) $units->sum('total_cost');
        $totalRevenue = (float) $units->sum('total_revenue');
        $totalResult = $totalRevenue - $totalCost;

        return view('admin.reports.operationalUnits', compact(
            'units',
            'fromDate',
            'toDate',
            'totalCost',
            'totalRevenue',
            'totalResult'
        ));
    }
}
