<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Suplier;
use App\Models\Vehicle;
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
        return 'purchase_form.'.auth()->id().'.'.$vehicle->id;
    }

    public function buyers()
    {
        $this->authorizeDocument();
        return response()->json(Suplier::orderBy('name')->get(['id', 'name', 'nif', 'address']));
    }

    public function edit(Request $request, Vehicle $vehicle)
    {
        $this->authorizeDocument();
        $vehicle->load('suplier', 'brand');
        $data = $request->session()->get($this->key($vehicle), [
            'seller_name' => $vehicle->suplier?->name,
            'seller_nif' => $vehicle->suplier?->nif,
            'seller_address' => $vehicle->suplier?->address,
            'amount' => $vehicle->purchase_price,
            'purchase_date' => $vehicle->date,
        ]);
        $buyers = Suplier::orderBy('name')->get(['id', 'name', 'nif', 'address']);
        return view('admin.vehicles.purchase-form', compact('vehicle', 'data', 'buyers'));
    }

    public function preview(Request $request, Vehicle $vehicle)
    {
        $this->authorizeDocument();
        $fields = ['seller_name', 'seller_nif', 'seller_address', 'buyer_name', 'buyer_nif', 'buyer_address', 'place', 'vin'];
        foreach ($fields as $field) {
            if (is_string($request->input($field))) $request->merge([$field => trim($request->input($field))]);
        }
        // Accept a Portuguese decimal comma, without guessing ambiguous thousands separators.
        if (is_string($request->input('amount'))) $request->merge(['amount' => str_replace(',', '.', trim($request->input('amount')))]);
        $rules = [
            'buyer_id' => ['required', 'integer', Rule::exists('supliers', 'id')->whereNull('deleted_at')],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:999999999.99', 'decimal:0,2'],
            'purchase_date' => ['required', 'date_format:Y-m-d'],
            'confirmed' => ['accepted'],
        ];
        foreach ($fields as $field) $rules[$field] = [$field === 'vin' ? 'nullable' : 'required', 'string', 'max:'.(str_contains($field, 'address') ? 300 : 150)];
        $labels = ['buyer_id' => 'empresa compradora', 'amount' => 'valor acordado', 'purchase_date' => 'data da compra', 'confirmed' => 'confirmação dos dados', 'seller_name' => 'nome do vendedor', 'seller_nif' => 'NIF do vendedor', 'seller_address' => 'morada do vendedor', 'buyer_name' => 'nome da empresa compradora', 'buyer_nif' => 'NIF da empresa compradora', 'buyer_address' => 'morada da empresa compradora', 'place' => 'local', 'vin' => 'número de quadro'];
        $data = $request->validate($rules, ['required' => 'Preencha :attribute.', 'accepted' => 'Confirme os dados e que o vendedor é um particular.'], $labels);
        $data += ['vin' => null];
        $request->session()->put($this->key($vehicle), $data);
        return redirect()->route('admin.vehicles.documents.purchase.preview', $vehicle);
    }

    public function show(Request $request, Vehicle $vehicle)
    {
        $this->authorizeDocument();
        $data = $request->session()->get($this->key($vehicle));
        if (!$data) return redirect()->route('admin.vehicles.documents.purchase.edit', $vehicle);
        abort_unless(Suplier::whereKey($data['buyer_id'])->exists(), 409, 'A empresa escolhida deixou de estar disponível. Volte à preparação do impresso.');
        $vehicle->load('brand');
        return response()->view('admin.vehicles.purchase-print', compact('vehicle', 'data'))->header('Cache-Control', 'private, no-store');
    }

    public function pdf(Request $request, Vehicle $vehicle)
    {
        $this->authorizeDocument();
        $data = $request->session()->get($this->key($vehicle));
        if (!$data) return redirect()->route('admin.vehicles.documents.purchase.edit', $vehicle);
        abort_unless(Suplier::whereKey($data['buyer_id'])->exists(), 409);
        $vehicle->load('brand');
        $pdf = new Dompdf(new Options(['isRemoteEnabled' => false, 'defaultFont' => 'DejaVu Sans', 'defaultMediaType' => 'print']));
        $pdf->loadHtml(view('admin.vehicles.purchase-print', ['vehicle' => $vehicle, 'data' => $data, 'pdf' => true])->render(), 'UTF-8');
        $pdf->setPaper('A4');
        $pdf->render();
        return response($pdf->output(), 200, ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'attachment; filename="compra-particular-'.$vehicle->id.'.pdf"', 'Cache-Control' => 'private, no-store']);
    }
}
