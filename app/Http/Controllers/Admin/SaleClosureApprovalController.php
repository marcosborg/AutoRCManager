<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SaleClosureApproval;
use App\Models\User;
use App\Support\RolePreview;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SaleClosureApprovalController extends Controller
{
    public function index(Request $request)
    {
        $this->authorizeApprovalAccess();

        $approvals = $this->filteredQuery($request)
            ->orderByDesc('closed_at')
            ->paginate(50)
            ->appends($request->query());

        $users = User::whereHas('sale_closure_approvals')
            ->orderBy('name')
            ->pluck('name', 'id');

        return view('admin.saleClosureApprovals.index', compact('approvals', 'users'));
    }

    public function export(Request $request)
    {
        $this->authorizeApprovalAccess();

        $approvals = $this->filteredQuery($request)
            ->orderByDesc('closed_at')
            ->get();

        $filename = 'fechos-venda-' . now()->format('Ymd-His') . '.csv';

        return response()->streamDownload(function () use ($approvals): void {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, [
                'Data fecho',
                'Viatura',
                'Cliente',
                'Utilizador',
                'Origem',
                'Total venda',
                'Pagamentos cliente',
                'Retomas',
                'Em divida',
                'Estado',
                'Validado por',
                'Data validacao',
            ], ';');

            foreach ($approvals as $approval) {
                fputcsv($handle, [
                    optional($approval->closed_at)->format('Y-m-d H:i'),
                    $this->vehicleLabel($approval),
                    $approval->vehicle->client->name ?? '',
                    $approval->closed_by->name ?? '',
                    SaleClosureApproval::TRIGGER_SELECT[$approval->trigger_type] ?? $approval->trigger_type,
                    number_format((float) $approval->sales_total, 2, ',', ''),
                    number_format((float) $approval->client_payments_total, 2, ',', ''),
                    number_format((float) $approval->trade_ins_total, 2, ',', ''),
                    number_format((float) $approval->outstanding_amount, 2, ',', ''),
                    SaleClosureApproval::STATUS_SELECT[$approval->status] ?? $approval->status,
                    $approval->approved_by->name ?? '',
                    optional($approval->approved_at)->format('Y-m-d H:i'),
                ], ';');
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function approve(SaleClosureApproval $approval)
    {
        $this->authorizeApprovalAccess();
        abort_if($approval->status !== SaleClosureApproval::STATUS_PENDING, Response::HTTP_UNPROCESSABLE_ENTITY, 'Validacao ja tratada.');

        $approval->update([
            'status' => SaleClosureApproval::STATUS_APPROVED,
            'approved_by_id' => auth()->id(),
            'approved_at' => now(),
        ]);

        return back()->with('message', 'Fecho de venda validado.');
    }

    public function reject(Request $request, SaleClosureApproval $approval)
    {
        $this->authorizeApprovalAccess();
        abort_if($approval->status !== SaleClosureApproval::STATUS_PENDING, Response::HTTP_UNPROCESSABLE_ENTITY, 'Validacao ja tratada.');

        $data = $request->validate([
            'rejection_reason' => ['required', 'string', 'max:1000'],
        ]);

        $approval->update([
            'status' => SaleClosureApproval::STATUS_REJECTED,
            'approved_by_id' => auth()->id(),
            'rejected_at' => now(),
            'rejection_reason' => $data['rejection_reason'],
        ]);

        return back()->with('message', 'Fecho de venda rejeitado.');
    }

    private function filteredQuery(Request $request)
    {
        return SaleClosureApproval::with([
            'vehicle.brand',
            'vehicle.client',
            'closed_by',
            'approved_by',
        ])
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->input('status')))
            ->when(! $request->filled('status'), fn ($query) => $query->where('status', SaleClosureApproval::STATUS_PENDING))
            ->when($request->filled('closed_by_id'), fn ($query) => $query->where('closed_by_id', $request->input('closed_by_id')))
            ->when($request->filled('date_from'), fn ($query) => $query->whereDate('closed_at', '>=', $request->input('date_from')))
            ->when($request->filled('date_to'), fn ($query) => $query->whereDate('closed_at', '<=', $request->input('date_to')));
    }

    private function authorizeApprovalAccess(): void
    {
        abort_if(
            ! RolePreview::hasAnyEffectiveRole(auth()->user(), ['Admin', 'Adm', 'Stand Adm']),
            Response::HTTP_FORBIDDEN,
            '403 Forbidden'
        );
    }

    private function vehicleLabel(SaleClosureApproval $approval): string
    {
        $vehicle = $approval->vehicle;

        return $vehicle->license
            ?: $vehicle->foreign_license
            ?: ('#' . $approval->vehicle_id);
    }
}
