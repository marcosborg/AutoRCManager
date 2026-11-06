<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use App\Support\WorkshopPrivacy;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class VehicleCirculationDeclarationController extends Controller
{
    public const FIELDS = ['name' => 'Nome do cliente', 'brand' => 'Marca', 'model' => 'Modelo',
        'vin' => 'Número de quadro', 'color' => 'Cor', 'seats' => 'Lotação', 'license' => 'Matrícula',
        'services' => 'Serviços', 'displacement' => 'Cilindrada', 'fuel' => 'Combustível', 'year' => 'Ano'];

    private function authorizeDocument(): void
    {
        abort_unless(!WorkshopPrivacy::restricted() && Gate::allows('vehicle_edit')
            && Gate::allows('vehicle_documents_area_access') && Gate::allows('vehicle_others_area_access'), 403);
    }

    private function key(Vehicle $vehicle): string
    {
        return 'circulation_declaration_v1.'.auth()->id().'.'.$vehicle->id;
    }

    public function edit(Request $request, Vehicle $vehicle)
    {
        $this->authorizeDocument();
        $vehicle->load('client', 'brand');
        $data = $request->session()->get($this->key($vehicle), [
            'name' => $vehicle->client?->name, 'brand' => $vehicle->brand?->name,
            'model' => $vehicle->model, 'license' => $vehicle->license ?: $vehicle->foreign_license,
            'color' => $vehicle->color, 'fuel' => $vehicle->fuel, 'year' => $vehicle->year,
            'displacement' => $vehicle->engine_displacement, 'sold_on' => $vehicle->getRawOriginal('sale_date'),
        ]);
        return response()->view('admin.vehicles.circulation-declaration-form', [
            'vehicle' => $vehicle, 'data' => $data, 'fields' => self::FIELDS,
        ])->header('Cache-Control', 'private, no-store');
    }

    public function prepare(Request $request, Vehicle $vehicle)
    {
        $this->authorizeDocument();
        $rules = [];
        foreach (self::FIELDS as $key => $label) {
            $rules[$key] = [in_array($key, ['name', 'brand', 'model', 'license']) ? 'required' : 'nullable', 'string', 'max:100'];
        }
        $data = $request->validate($rules + [
            'sold_on' => ['required', 'date_format:Y-m-d'], 'confirmed' => ['accepted'],
        ], ['required' => 'Preencha :attribute.',
            'accepted' => 'Confirme os dados e a situação da documentação antes de continuar.',
        ], self::FIELDS + ['sold_on' => 'data da venda', 'confirmed' => 'confirmação']);
        unset($data['confirmed']);
        $data += array_fill_keys(array_keys(self::FIELDS), null);
        $request->session()->put($this->key($vehicle), $data);
        return redirect()->route('admin.vehicles.documents.circulation.preview', $vehicle);
    }

    public function show(Request $request, Vehicle $vehicle)
    {
        $this->authorizeDocument();
        $data = $request->session()->get($this->key($vehicle));
        if (!$data) return redirect()->route('admin.vehicles.documents.circulation.edit', $vehicle);
        $missingFields = array_filter(self::FIELDS, fn ($key) => trim((string) ($data[$key] ?? '')) === '', ARRAY_FILTER_USE_KEY);
        return response()->view('admin.vehicles.circulation-declaration-print', compact('vehicle', 'data', 'missingFields'))->header('Cache-Control', 'private, no-store');
    }

    public function pdf(Request $request, Vehicle $vehicle)
    {
        $this->authorizeDocument();
        $data = $request->session()->get($this->key($vehicle));
        if (!$data) return redirect()->route('admin.vehicles.documents.circulation.edit', $vehicle);
        $pdf = new Dompdf(new Options(['isRemoteEnabled' => false, 'defaultFont' => 'DejaVu Serif', 'defaultMediaType' => 'print']));
        $pdf->loadHtml(view('admin.vehicles.circulation-declaration-print', compact('vehicle', 'data') + ['pdf' => true])->render(), 'UTF-8');
        $pdf->setPaper('A4');
        $pdf->render();
        return response($pdf->output(), 200, ['Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="declaracao-circulacao-'.$vehicle->id.'.pdf"', 'Cache-Control' => 'private, no-store']);
    }
}
