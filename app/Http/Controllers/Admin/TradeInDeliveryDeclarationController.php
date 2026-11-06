<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\VehicleTradeIn;
use App\Support\WorkshopPrivacy;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class TradeInDeliveryDeclarationController extends Controller
{
    public const FIELDS = ['name' => 'Nome do proprietário', 'address' => 'Morada', 'zip' => 'Código postal',
        'location' => 'Localidade', 'identity' => 'BI / Cartão de Cidadão', 'vat' => 'NIF',
        'license' => 'Matrícula da retoma', 'brand' => 'Marca da retoma', 'model' => 'Modelo da retoma'];

    public static function allowed(): bool
    {
        return !WorkshopPrivacy::restricted() && Gate::allows('vehicle_edit')
            && Gate::allows('vehicle_documents_area_access') && Gate::allows('vehicle_others_area_access');
    }

    private function authorizeDocument(VehicleTradeIn $tradeIn): void
    {
        abort_unless(self::allowed(), 403);
        abort_if($tradeIn->status === VehicleTradeIn::STATUS_REJECTED, 404);
        abort_unless($tradeIn->sold_vehicle_id, 404);
    }

    private function key(VehicleTradeIn $tradeIn): string
    {
        return 'trade_in_delivery_v1.'.auth()->id().'.'.$tradeIn->id;
    }

    public function edit(Request $request, VehicleTradeIn $tradeIn)
    {
        $this->authorizeDocument($tradeIn);
        $tradeIn->load(['sold_vehicle.client', 'created_vehicle.brand']);
        $owner = $tradeIn->sold_vehicle?->client;
        $received = $tradeIn->created_vehicle;
        $data = $request->session()->get($this->key($tradeIn), [
            'name' => $owner?->name, 'vat' => $owner?->vat, 'address' => $owner?->address,
            'zip' => $owner?->zip, 'location' => $owner?->location,
            'license' => $tradeIn->license, 'brand' => $received?->brand?->name,
            'model' => $received?->model, 'kilometers' => $received?->kilometers,
        ]);
        return response()->view('admin.vehicleTradeIns.delivery-form', ['tradeIn' => $tradeIn, 'data' => $data,
            'fields' => self::FIELDS])->header('Cache-Control', 'private, no-store');
    }

    public function prepare(Request $request, VehicleTradeIn $tradeIn)
    {
        $this->authorizeDocument($tradeIn);
        $rules = [];
        foreach (self::FIELDS as $key => $label) {
            $rules[$key] = [in_array($key, ['name', 'license']) ? 'required' : 'nullable', 'string', 'max:150'];
        }
        $rules += ['kilometers' => ['nullable', 'integer', 'min:0', 'max:9999999'],
            'delivery_date' => ['nullable', 'date_format:Y-m-d'], 'faults' => ['nullable', 'string', 'max:1200']];
        $data = $request->validate($rules, ['required' => 'Preencha :attribute.'], self::FIELDS + [
            'kilometers' => 'Quilómetros à entrada', 'delivery_date' => 'Data de entrega', 'faults' => 'Avarias e danos']);
        $data += array_fill_keys(array_keys($rules), null);
        $request->session()->put($this->key($tradeIn), $data);
        return redirect()->route('admin.vehicle-trade-ins.delivery.preview', $tradeIn);
    }

    public function show(Request $request, VehicleTradeIn $tradeIn)
    {
        $this->authorizeDocument($tradeIn);
        $data = $request->session()->get($this->key($tradeIn));
        if (!$data) return redirect()->route('admin.vehicle-trade-ins.delivery.edit', $tradeIn);
        $labels = self::FIELDS + ['kilometers' => 'Quilómetros à entrada', 'delivery_date' => 'Data de entrega'];
        $missingFields = array_filter($labels, fn ($key) => trim((string) ($data[$key] ?? '')) === '', ARRAY_FILTER_USE_KEY);
        return response()->view('admin.vehicleTradeIns.delivery-print', compact('tradeIn', 'data', 'missingFields'))
            ->header('Cache-Control', 'private, no-store');
    }

    public function pdf(Request $request, VehicleTradeIn $tradeIn)
    {
        $this->authorizeDocument($tradeIn);
        $data = $request->session()->get($this->key($tradeIn));
        if (!$data) return redirect()->route('admin.vehicle-trade-ins.delivery.edit', $tradeIn);
        $pdf = new Dompdf(new Options(['isRemoteEnabled' => false, 'defaultFont' => 'DejaVu Serif', 'defaultMediaType' => 'print']));
        $pdf->loadHtml(view('admin.vehicleTradeIns.delivery-print', compact('tradeIn', 'data') + ['pdf' => true])->render(), 'UTF-8');
        $pdf->setPaper('A4');
        $pdf->render();
        return response($pdf->output(), 200, ['Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="declaracao-entrega-retoma-'.$tradeIn->id.'.pdf"',
            'Cache-Control' => 'private, no-store']);
    }
}
