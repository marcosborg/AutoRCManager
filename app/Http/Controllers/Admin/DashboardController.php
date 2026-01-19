<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\DashboardKpiService;
use App\Services\OperationalUnitReportService;
use Gate;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DashboardController extends Controller
{
    public function index(Request $request, DashboardKpiService $service, OperationalUnitReportService $reportService)
    {
        abort_if(Gate::denies('account_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $validated = $request->validate([
            'from' => ['nullable', 'date_format:' . config('panel.date_format')],
            'to' => ['nullable', 'date_format:' . config('panel.date_format')],
        ]);

        $data = $service->build($validated['from'] ?? null, $validated['to'] ?? null, $reportService);

        return view('admin.dashboard', [
            'fromDate' => $data['from'],
            'toDate' => $data['to'],
            'totals' => $data['totals'],
            'units' => $data['units'],
        ]);
    }
}
