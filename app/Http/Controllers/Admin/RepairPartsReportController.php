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
        \App\Support\AmountRange::validate($request);
        $request->validate(['export' => ['nullable', 'in:csv']]);

        $query = RepairPart::with(['repair.vehicle'])
            ->when($request->filled('amount_min'), fn ($q) => $q->where('amount', '>=', $request->input('amount_min')))
            ->when($request->filled('amount_max'), fn ($q) => $q->where('amount', '<=', $request->input('amount_max')))
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
                $vehicleQuery->searchByLicense($vehicleTerm);
            });
        }

        if ($request->input('export') === 'csv') {
            return response()->streamDownload(function () use ($query) {
                $out = fopen('php://output', 'w'); fwrite($out, "\xEF\xBB\xBF");
                fputcsv($out, ['ID', 'Data', 'Matrícula', 'Matrícula estrangeira', 'Peça', 'Fornecedor', 'N. fatura', 'Valor (€)', 'Intervenção'], ';');
                foreach ($query->lazy(200) as $part) {
                    $row = [$part->id, $part->part_date?->format('Y-m-d'), $part->repair?->vehicle?->license,
                        $part->repair?->vehicle?->foreign_license, $part->part_name, $part->supplier,
                        $part->invoice_number, $part->amount === null ? '' : number_format((float) $part->amount, 2, ',', ''), $part->repair_id];
                    fputcsv($out, array_map(function ($value) {
                        $value = (string) $value;
                        return preg_match('/^\s*[=+@-]/u', $value) ? "'" . $value : $value;
                    }, $row), ';');
                }
                fclose($out);
            }, 'pecas-oficina-' . now('Europe/Lisbon')->format('Y-m-d') . '.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
        }
        $parts = (clone $query)->paginate(50)->appends($request->query());
        $totalAmount = (float) (clone $query)->sum('amount');
        $totalLines = (int) (clone $query)->count();

        $supplierTotals = (clone $query)
            ->reorder()
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
