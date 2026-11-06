<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use App\Support\WorkshopPrivacy;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class VehicleSaleDeclarationController extends Controller
{
    public const FIELDS = [
        'name' => 'Nome do cliente', 'identity' => 'BI/CC', 'vat' => 'NIF',
        'address' => 'Morada', 'zip' => 'Código postal', 'location' => 'Localidade',
        'phone' => 'Contacto', 'email' => 'Email', 'brand' => 'Marca', 'model' => 'Modelo',
        'year' => 'Ano', 'license' => 'Matrícula', 'vin' => 'N.º de chassis',
        'trade_brand' => 'Marca da retoma', 'trade_license' => 'Matrícula da retoma',
    ];
    public const AMOUNTS = ['total' => 'Valor total da viatura', 'entry' => 'Entrada',
        'deposit' => 'Sinal', 'finance' => 'Valor do financiamento', 'debt' => 'Valor em dívida', 'trade_value' => 'Valor da retoma'];

    private function authorizeDocument(): void
    {
        abort_unless(!WorkshopPrivacy::restricted() && Gate::allows('vehicle_edit')
            && Gate::allows('vehicle_documents_area_access') && Gate::allows('vehicle_others_area_access'), 403);
    }

    private function key(Vehicle $vehicle): string
    {
        return 'sale_declaration_v1.'.auth()->id().'.'.$vehicle->id;
    }

    public function edit(Request $request, Vehicle $vehicle)
    {
        $this->authorizeDocument();
        $vehicle->load('client', 'brand');
        // Use the same buyer as the existing sale screen, not the acquisition supplier.
        $client = $vehicle->client;
        $data = $request->session()->get($this->key($vehicle), [
            'name' => $client?->name, 'vat' => $client?->vat, 'address' => $client?->address,
            'zip' => $client?->zip, 'location' => $client?->location, 'phone' => $client?->phone,
            'email' => $client?->email, 'brand' => $vehicle->brand?->name, 'model' => $vehicle->model,
            'year' => $vehicle->year, 'license' => $vehicle->license ?: $vehicle->foreign_license,
            'document_date' => now()->toDateString(),
        ]);
        // Financial fields deliberately remain blank: asking price is not agreed price,
        // and recorded payments do not distinguish entry from deposit.
        return response()->view('admin.vehicles.sale-declaration-form', [
            'vehicle' => $vehicle, 'data' => $data, 'fields' => self::FIELDS, 'amounts' => self::AMOUNTS,
        ])->header('Cache-Control', 'private, no-store');
    }

    public function prepare(Request $request, Vehicle $vehicle)
    {
        $this->authorizeDocument();
        $rules = [];
        foreach (self::FIELDS as $key => $label) {
            $rules[$key] = [in_array($key, ['name', 'vat', 'address', 'brand', 'model', 'license']) ? 'required' : 'nullable', 'string', 'max:'.($key === 'address' ? 250 : 100)];
        }
        $rules['email'][] = 'email';
        $rules['year'] = ['nullable', 'integer', 'between:1900,2100'];
        foreach (self::AMOUNTS as $key => $label) {
            if (is_string($request->input($key))) {
                $amount = trim($request->input($key));
                $request->merge([$key => $amount === '' ? null : str_replace(',', '.', $amount)]);
            }
            $rules[$key] = ['nullable', 'numeric', 'min:0', 'max:99999999.99', 'decimal:0,2'];
        }
        $rules += [
            'guarantee' => ['required', Rule::in(['yes', 'no'])],
            'months' => ['nullable', 'required_if:guarantee,yes', 'prohibited_if:guarantee,no', 'integer', 'between:1,120'],
            'document_date' => ['required', 'date_format:Y-m-d'],
            'confirmed' => ['accepted'], 'company_confirmed' => ['accepted'],
        ];
        $data = $request->validate($rules, [
            'required' => 'Preencha :attribute.',
            'accepted' => 'Confirme :attribute antes de preparar o documento.',
        ], self::FIELDS + self::AMOUNTS + ['confirmed' => 'a revisão dos dados', 'company_confirmed' => 'a empresa Geração Determinada', 'guarantee' => 'a garantia', 'months' => 'os meses de garantia']);
        unset($data['confirmed'], $data['company_confirmed']);
        $data += array_fill_keys(array_keys(self::FIELDS + self::AMOUNTS), null);
        $request->session()->put($this->key($vehicle), $data);
        return redirect()->route('admin.vehicles.documents.sale.preview', $vehicle);
    }

    public function show(Request $request, Vehicle $vehicle)
    {
        $this->authorizeDocument();
        $data = $request->session()->get($this->key($vehicle));
        if (!$data) return redirect()->route('admin.vehicles.documents.sale.edit', $vehicle);
        return response()->view('admin.vehicles.sale-declaration-print', compact('vehicle', 'data'))->header('Cache-Control', 'private, no-store');
    }

    public function pdf(Request $request, Vehicle $vehicle)
    {
        $this->authorizeDocument();
        $data = $request->session()->get($this->key($vehicle));
        if (!$data) return redirect()->route('admin.vehicles.documents.sale.edit', $vehicle);
        $pdf = new Dompdf(new Options(['isRemoteEnabled' => false, 'defaultFont' => 'DejaVu Serif', 'defaultMediaType' => 'print']));
        $pdf->loadHtml(view('admin.vehicles.sale-declaration-print', compact('vehicle', 'data') + ['pdf' => true])->render(), 'UTF-8');
        $pdf->setPaper('A4');
        $pdf->render();
        return response($pdf->output(), 200, ['Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="declaracao-venda-'.$vehicle->id.'.pdf"', 'Cache-Control' => 'private, no-store']);
    }
}
