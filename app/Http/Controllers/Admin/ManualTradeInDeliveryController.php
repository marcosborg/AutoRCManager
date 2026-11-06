<?php

namespace App\Http\Controllers\Admin;

use App\Models\Vehicle;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\Request;

class ManualTradeInDeliveryController extends TradeInDeliveryDeclarationController
{
    private function context(Vehicle $vehicle): array
    {
        abort_unless(self::allowed(), 403);
        return ['saleVehicleId' => $vehicle->id, 'fields' => self::FIELDS,
            'prepareUrl' => route('admin.vehicles.manual-delivery.prepare', $vehicle),
            'editUrl' => route('admin.vehicles.manual-delivery.edit', $vehicle),
            'pdfUrl' => route('admin.vehicles.manual-delivery.pdf', $vehicle)];
    }

    private function draftKey(Vehicle $vehicle): string
    {
        return 'manual_trade_in_delivery_v1.'.auth()->id().'.'.$vehicle->id;
    }

    public function form(Request $request, Vehicle $vehicle)
    {
        $context = $this->context($vehicle);
        $owner = $vehicle->client;
        $data = $request->session()->get($this->draftKey($vehicle), [
            'name' => $owner?->name, 'vat' => $owner?->vat, 'address' => $owner?->address,
            'zip' => $owner?->zip, 'location' => $owner?->location,
            'license' => null, 'brand' => null, 'model' => null, 'kilometers' => null,
        ]);
        return response()->view('admin.vehicleTradeIns.delivery-form', $context + compact('data'))
            ->header('Cache-Control', 'private, no-store');
    }

    public function saveDraft(Request $request, Vehicle $vehicle)
    {
        $this->context($vehicle);
        $request->session()->put($this->draftKey($vehicle), $this->validatedData($request));
        return redirect()->route('admin.vehicles.manual-delivery.preview', $vehicle);
    }

    public function previewDraft(Request $request, Vehicle $vehicle)
    {
        $context = $this->context($vehicle);
        $data = $request->session()->get($this->draftKey($vehicle));
        if (!$data) return redirect($context['editUrl']);
        $labels = self::FIELDS + ['kilometers' => 'Quilómetros à entrada', 'delivery_date' => 'Data de entrega'];
        $missingFields = array_filter($labels, fn ($key) => trim((string) ($data[$key] ?? '')) === '', ARRAY_FILTER_USE_KEY);
        return response()->view('admin.vehicleTradeIns.delivery-print', $context + compact('data', 'missingFields'))
            ->header('Cache-Control', 'private, no-store');
    }

    public function download(Request $request, Vehicle $vehicle)
    {
        $context = $this->context($vehicle);
        $data = $request->session()->get($this->draftKey($vehicle));
        if (!$data) return redirect($context['editUrl']);
        $pdf = new Dompdf(new Options(['isRemoteEnabled' => false, 'defaultFont' => 'DejaVu Serif', 'defaultMediaType' => 'print']));
        $pdf->loadHtml(view('admin.vehicleTradeIns.delivery-print', $context + compact('data') + ['pdf' => true])->render(), 'UTF-8');
        $pdf->setPaper('A4');
        $pdf->render();
        return response($pdf->output(), 200, ['Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="declaracao-entrega-retoma-venda-'.$vehicle->id.'.pdf"',
            'Cache-Control' => 'private, no-store']);
    }
}
