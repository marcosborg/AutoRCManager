<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AccountOperation;
use App\Models\CashBox;
use App\Models\CashCategory;
use App\Models\Department;
use App\Models\SaleClosureApproval;
use App\Models\StandCashPaymentApproval;
use App\Support\RolePreview;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class StandCashPaymentApprovalController extends Controller
{
    public function index(Request $request)
    {
        $this->authorizeApprovalAccess();
        $request->validate(['export' => ['nullable', 'in:csv']]);

        $query = StandCashPaymentApproval::with([
            'payment.payment_method',
            'vehicle.brand',
            'vehicle.client',
            'created_by',
            'approved_by',
            'cash_operation',
        ])
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->input('status')))
            ->when(! $request->filled('status'), fn ($query) => $query->where('status', StandCashPaymentApproval::STATUS_PENDING))
            ->orderByDesc('created_at')->orderByDesc('id');
        if ($request->input('export') === 'csv') {
            return response()->streamDownload(function () use ($query) {
                $out=fopen('php://output','w');fwrite($out,"\xEF\xBB\xBF");
                fputcsv($out,['ID','Data','Viatura','Cliente','Valor (€)','Criado por','Estado','Movimento','Validado por','Data validação','Data rejeição','Motivo rejeição'],';');
                foreach($query->lazy(200) as $approval){
                    $row=[$approval->id,$approval->payment?->paid_at,$approval->vehicle?->license ?: $approval->vehicle?->foreign_license,
                        $approval->vehicle?->client?->name,$approval->payment?->amount === null ? '' : number_format((float)$approval->payment->amount,2,',',''),
                        $approval->created_by?->name,StandCashPaymentApproval::STATUS_SELECT[$approval->status]??$approval->status,
                        $approval->cash_operation_id,$approval->approved_by?->name,$approval->approved_at?->format('Y-m-d H:i'),$approval->rejected_at?->format('Y-m-d H:i'),$approval->rejection_reason];
                    fputcsv($out,array_map(function($value){$value=(string)$value;return preg_match('/^\s*[=+@-]/u',$value)?"'".$value:$value;},$row),';');
                }fclose($out);
            },'validacao-pagamentos-stand-'.now('Europe/Lisbon')->format('Y-m-d').'.csv',['Content-Type'=>'text/csv; charset=UTF-8']);
        }
        $approvals=$query->paginate(50)->appends($request->query());

        return view('admin.standCashPaymentApprovals.index', compact('approvals'));
    }

    public function approve(StandCashPaymentApproval $approval)
    {
        $this->authorizeApprovalAccess();
        abort_if($approval->status !== StandCashPaymentApproval::STATUS_PENDING, Response::HTTP_UNPROCESSABLE_ENTITY, 'Validacao ja tratada.');

        DB::transaction(function () use ($approval): void {
            $approval->loadMissing(['payment.payment_method', 'vehicle.brand']);
            $payment = $approval->payment;
            abort_if(! $payment || $payment->trashed(), Response::HTTP_UNPROCESSABLE_ENTITY, 'Pagamento ja nao existe na viatura.');

            $operation = AccountOperation::create([
                'description' => 'Recebimento cliente validado - viatura ' . $this->vehicleLabel($approval),
                'movement_type' => AccountOperation::TYPE_INCOME,
                'total' => $payment->amount,
                'department_id' => $this->standDepartmentId(),
                'cash_category_id' => $this->saleCategoryId(),
                'vehicle_id' => $approval->vehicle_id,
                'qty' => 1,
                'date' => $payment->getRawOriginal('paid_at'),
                'payment_method_id' => $payment->payment_method_id,
                'cash_box_id' => $this->standCashBoxId(),
                'notes' => 'Validado pelo Stand Adm. Pagamento cliente #' . $payment->id,
            ]);

            $approval->update([
                'status' => StandCashPaymentApproval::STATUS_APPROVED,
                'approved_by_id' => auth()->id(),
                'approved_at' => now(),
                'cash_operation_id' => $operation->id,
            ]);
        });

        return back()->with('message', 'Pagamento validado e movimento criado na Caixa Stand.');
    }

    public function reject(Request $request, StandCashPaymentApproval $approval)
    {
        $this->authorizeApprovalAccess();
        abort_if($approval->status !== StandCashPaymentApproval::STATUS_PENDING, Response::HTTP_UNPROCESSABLE_ENTITY, 'Validacao ja tratada.');

        $data = $request->validate([
            'rejection_reason' => ['required', 'string', 'max:1000'],
        ]);

        DB::transaction(function () use ($approval, $data): void {
            $approval->loadMissing('payment');

            if ($approval->cash_operation_id) {
                AccountOperation::where('id', $approval->cash_operation_id)->delete();
            }

            $approval->update([
                'status' => StandCashPaymentApproval::STATUS_REJECTED,
                'approved_by_id' => auth()->id(),
                'cash_operation_id' => null,
                'rejected_at' => now(),
                'rejection_reason' => $data['rejection_reason'],
            ]);

            if ($approval->payment && ! $approval->payment->trashed()) {
                SaleClosureApproval::where('vehicle_id', $approval->vehicle_id)
                    ->where('trigger_type', SaleClosureApproval::TRIGGER_PAYMENT)
                    ->where('trigger_id', $approval->payment->id)
                    ->where('status', SaleClosureApproval::STATUS_PENDING)
                    ->update([
                        'status' => SaleClosureApproval::STATUS_REJECTED,
                        'approved_by_id' => auth()->id(),
                        'rejected_at' => now(),
                        'rejection_reason' => 'Pagamento de cliente rejeitado.',
                    ]);

                $approval->payment->delete();
            }
        });

        return back()->with('message', 'Validacao rejeitada. O pagamento foi removido da viatura e da conta do cliente.');
    }

    private function authorizeApprovalAccess(): void
    {
        abort_if(
            ! RolePreview::hasAnyEffectiveRole(auth()->user(), ['Admin', 'Adm', 'Stand Adm']),
            Response::HTTP_FORBIDDEN,
            '403 Forbidden'
        );
    }

    private function standCashBoxId(): ?int
    {
        return CashBox::where('slug', 'caixa_stand')->orWhere('name', 'Caixa Stand')->value('id');
    }

    private function standDepartmentId(): ?int
    {
        return Department::where('name', 'Stand')->value('id');
    }

    private function saleCategoryId(): ?int
    {
        return CashCategory::where('name', 'Venda')->value('id');
    }

    private function vehicleLabel(StandCashPaymentApproval $approval): string
    {
        return $approval->vehicle->license
            ?: $approval->vehicle->foreign_license
            ?: ('#' . $approval->vehicle_id);
    }
}
