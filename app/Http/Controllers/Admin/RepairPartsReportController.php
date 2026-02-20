<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RepairPart;
use Gate;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RepairPartsReportController extends Controller
{
    public function index(Request $request)
    {
        abort_if(Gate::denies('repair_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $query = RepairPart::with(['repair.vehicle'])
            ->orderByDesc('part_date')
            ->orderByDesc('id');

        if ($request->filled('supplier')) {
            $query->where('supplier', 'like', '%' . trim((string) $request->input('supplier')) . '%');
        }

        if ($request->filled('invoice_number')) {
            $query->where('invoice_number', 'like', '%' . trim((string) $request->input('invoice_number')) . '%');
        }

        if ($request->filled('name')) {
            $query->where('part_name', 'like', '%' . trim((string) $request->input('name')) . '%');
        }

        if ($request->filled('date_from')) {
            $query->whereDate('part_date', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('part_date', '<=', $request->input('date_to'));
        }

        if ($request->filled('vehicle')) {
            $vehicleTerm = trim((string) $request->input('vehicle'));
            $query->whereHas('repair.vehicle', function ($vehicleQuery) use ($vehicleTerm) {
                $vehicleQuery->where('license', 'like', '%' . $vehicleTerm . '%')
                    ->orWhere('foreign_license', 'like', '%' . $vehicleTerm . '%');
            });
        }

        $parts = (clone $query)->paginate(50)->appends($request->query());
        $totalAmount = (float) (clone $query)->sum('amount');
        $totalLines = (int) (clone $query)->count();

        $supplierTotals = (clone $query)
            ->selectRaw("COALESCE(NULLIF(TRIM(supplier), ''), 'Sem fornecedor') as supplier_label")
            ->selectRaw('SUM(COALESCE(amount,0)) as total_amount')
            ->groupBy('supplier_label')
            ->orderByDesc('total_amount')
            ->limit(20)
            ->get();

        return view('admin.repairPartsReport.index', compact(
            'parts',
            'totalAmount',
            'totalLines',
            'supplierTotals'
        ));
    }
}

