<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Suplier;
use App\Models\Vehicle;
use App\Models\VehicleDocumentFollowup;
use App\Support\VehicleDocuments;
use Illuminate\Support\Facades\DB;
use App\Support\WorkshopPrivacy;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class VehiclePurchaseFormController extends Controller
{
    private function authorizeDocument(): void
    {
        abort_unless(!WorkshopPrivacy::restricted() && Gate::allows('vehicle_edit') && Gate::allows('vehicle_documents_area_access') && Gate::allows('aquisition_of_the_vehicle'), 403);
    }

    private function key(Vehicle $vehicle): string
    {
        return 'purchase_form_v2.'.auth()->id().'.'.$vehicle->id;
    }

    public function buyers()
    {
        $this->authorizeDocument();
        return response()->json(collect(Vehicle::DESTINATION_COMPANIES)->map(fn ($name, $id) => ['id' => $id, 'name' => $name])->values());
    }

    public function sellers()
    {
        $this->authorizeDocument();
        return response()->json(Suplier::orderBy('name')->get(['id', 'name', 'nif', 'address']));
    }

    public function edit(Request $request, Vehicle $vehicle)
    {
        $this->authorizeDocument();
        $vehicle->load('suplier', 'brand');
        $followup = $vehicle->documentFollowups()->where('document', 'signed_purchase_form')->first();
        $users = VehicleDocumentFollowup::responsibleOptions();
        $ritas = $users->filter(fn ($user) => preg_match('/^rita(?:\s|$)/iu', trim($user->name)));
        $defaultResponsible = $ritas->count() === 1 ? $ritas->first()->id : null;
        $data = $followup?->purchase_form_data ?? $request->session()->get($this->key($vehicle), [
            'seller_id' => $vehicle->suplier_id,
            'seller_name' => $vehicle->suplier?->name,
            'seller_nif' => $vehicle->suplier?->nif,
            'seller_address' => $vehicle->suplier?->address,
            'amount' => $vehicle->purchase_price,
            'purchase_date' => $vehicle->date,
        ]);
        $sellers = Suplier::orderBy('name')->get(['id', 'name', 'nif', 'address']);
        $buyers = Vehicle::DESTINATION_COMPANIES;
        return view('admin.vehicles.purchase-form', compact('vehicle', 'data', 'buyers', 'sellers', 'followup', 'users', 'defaultResponsible'));
    }

    public function preview(Request $request, Vehicle $vehicle)
    {
        $this->authorizeDocument();
        $fields = ['seller_name', 'seller_nif', 'seller_address', 'buyer_nif', 'buyer_address', 'place', 'vin'];
        foreach ($fields as $field) {
            if (is_string($request->input($field))) $request->merge([$field => trim($request->input($field))]);
        }
        // Accept a Portuguese decimal comma, without guessing ambiguous thousands separators.
        if (is_string($request->input('amount'))) $request->merge(['amount' => str_replace(',', '.', trim($request->input('amount')))]);
        $rules = [
            'intent' => ['nullable', Rule::in(['preview', 'save'])],
            'buyer_id' => ['required', 'string', Rule::in(array_keys(Vehicle::DESTINATION_COMPANIES))],
            'seller_id' => ['required', 'integer', Rule::exists('supliers', 'id')->whereNull('deleted_at')],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:999999999.99', 'decimal:0,2'],
            'purchase_date' => ['required', 'date_format:Y-m-d'],
            'confirmed' => ['accepted'],
        ];
        foreach ($fields as $field) $rules[$field] = [$field === 'vin' ? 'nullable' : 'required', 'string', 'max:'.(str_contains($field, 'address') ? 300 : 150)];
        $labels = ['seller_id' => 'vendedor', 'buyer_id' => 'empresa compradora', 'amount' => 'valor acordado', 'purchase_date' => 'data da compra', 'confirmed' => 'confirmação dos dados', 'seller_name' => 'nome do vendedor', 'seller_nif' => 'NIF do vendedor', 'seller_address' => 'morada do vendedor', 'buyer_name' => 'nome da empresa compradora', 'buyer_nif' => 'NIF da empresa compradora', 'buyer_address' => 'morada da empresa compradora', 'place' => 'local', 'vin' => 'número de quadro'];
        $data = $request->validate($rules, ['required' => 'Preencha :attribute.', 'accepted' => 'Confirme os dados e que o vendedor é um particular.'], $labels);
        $data += ['vin' => null];
        $data['buyer_name'] = Vehicle::DESTINATION_COMPANIES[$data['buyer_id']];
        unset($data['intent'], $data['confirmed']);
        $vehicle->load('brand');
        $data['vehicle_license'] = $vehicle->license ?: $vehicle->foreign_license;
        $data['vehicle_description'] = trim(($vehicle->brand?->name ?? '').' '.$vehicle->model);
        if ($request->input('intent') === 'save') {
            $tracking = $request->validate([
                'followup_version' => ['required', 'integer', 'min:0'],
                'assigned_to_id' => ['required', 'integer'],
                'next_contact_on' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            ]);
            abort_unless(VehicleDocumentFollowup::responsibleOptions()->contains('id', (int) $tracking['assigned_to_id']), 422, 'Escolha um responsável com acesso à documentação.');
            DB::transaction(function () use ($vehicle, $data, $tracking) {
                $locked = Vehicle::whereKey($vehicle->id)->lockForUpdate()->firstOrFail();
                abort_if(VehicleDocuments::notApplicable($locked, 'signed_purchase_form'), 422, 'Reveja primeiro a opção Não se aplica.');
                $followup = $locked->documentFollowups()->where('document', 'signed_purchase_form')->lockForUpdate()->firstOrNew();
                // A retry or reprint must not reset a completed followup or create another reminder.
                if ($followup->purchase_form_data == $data) return;
                abort_unless((int) ($followup->version ?? 0) === (int) $tracking['followup_version'], 409, 'O acompanhamento mudou. Atualize a página antes de gravar.');
                abort_if($followup->exists && $followup->status !== 'open', 409, 'Reveja o acompanhamento antes de alterar o impresso guardado.');
                $followup->fill(['document' => 'signed_purchase_form', 'purchase_form_data' => $data,
                    'assigned_to_id' => $followup->assigned_to_id ?? $tracking['assigned_to_id'],
                    'contact' => $followup->contact ?? $data['seller_name'],
                    'requested_on' => $followup->requested_on ?? now()->toDateString(),
                    'next_contact_on' => $followup->next_contact_on ?? $tracking['next_contact_on'],
                    'status' => 'open']);
                $followup->record('prepare_purchase', 'Impresso guardado. À espera de assinatura.');
            });
            $request->session()->forget($this->key($vehicle));
            return redirect()->route('admin.vehicles.documents.followup', [$vehicle, 'signed_purchase_form'])
                ->with('message', 'Impresso guardado. Pode imprimir e anexar aqui a cópia assinada.');
        }
        $request->session()->put($this->key($vehicle), $data);
        return redirect()->route('admin.vehicles.documents.purchase.preview', $vehicle);
    }

    public function show(Request $request, Vehicle $vehicle)
    {
        $this->authorizeDocument();
        $data = ($request->boolean('saved') ? null : $request->session()->get($this->key($vehicle))) ?? $vehicle->documentFollowups()->where('document', 'signed_purchase_form')->first()?->purchase_form_data;
        if (!$data) return redirect()->route('admin.vehicles.documents.purchase.edit', $vehicle);
        abort_unless(Suplier::whereKey($data['seller_id'])->exists(), 409, 'O vendedor escolhido deixou de estar disponível. Volte à preparação do impresso.');
        $vehicle->load('brand');
        return response()->view('admin.vehicles.purchase-print', compact('vehicle', 'data'))->header('Cache-Control', 'private, no-store');
    }

    public function pdf(Request $request, Vehicle $vehicle)
    {
        $this->authorizeDocument();
        $data = ($request->boolean('saved') ? null : $request->session()->get($this->key($vehicle))) ?? $vehicle->documentFollowups()->where('document', 'signed_purchase_form')->first()?->purchase_form_data;
        if (!$data) return redirect()->route('admin.vehicles.documents.purchase.edit', $vehicle);
        abort_unless(Suplier::whereKey($data['seller_id'])->exists(), 409);
        $vehicle->load('brand');
        $pdf = new Dompdf(new Options(['isRemoteEnabled' => false, 'defaultFont' => 'DejaVu Sans', 'defaultMediaType' => 'print']));
        $pdf->loadHtml(view('admin.vehicles.purchase-print', ['vehicle' => $vehicle, 'data' => $data, 'pdf' => true])->render(), 'UTF-8');
        $pdf->setPaper('A4');
        $pdf->render();
        return response($pdf->output(), 200, ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'attachment; filename="compra-particular-'.$vehicle->id.'.pdf"', 'Cache-Control' => 'private, no-store']);
    }
}
