<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use App\Support\WorkshopPrivacy;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class VehicleResponsibilityDeclarationController extends Controller
{
    public const FIELDS = ['name' => 'Nome do cliente', 'identity' => 'BI / Cartão de Cidadão',
        'vat' => 'NIF', 'license' => 'Matrícula', 'year' => 'Ano', 'month' => 'Mês', 'vin' => 'Número de chassis'];
    public const DATES = ['pickup_date' => 'Dia de levantamento', 'pickup_time' => 'Hora de levantamento',
        'return_date' => 'Dia de entrega', 'return_time' => 'Hora de entrega'];

    private function authorizeDocument(): void
    {
        abort_unless(!WorkshopPrivacy::restricted() && Gate::allows('vehicle_edit')
            && Gate::allows('vehicle_documents_area_access') && Gate::allows('vehicle_others_area_access'), 403);
    }

    private function key(Vehicle $vehicle): string
    {
        return 'responsibility_declaration_v1.'.auth()->id().'.'.$vehicle->id;
    }

    public function edit(Request $request, Vehicle $vehicle)
    {
        $this->authorizeDocument();
        $vehicle->load('client');
        $data = $request->session()->get($this->key($vehicle), [
            'name' => $vehicle->client?->name, 'vat' => $vehicle->client?->vat,
            'license' => $vehicle->license ?: $vehicle->foreign_license,
            'year' => $vehicle->year, 'month' => $vehicle->month,
        ]);
        return response()->view('admin.vehicles.responsibility-declaration-form', [
            'vehicle' => $vehicle, 'data' => $data, 'fields' => self::FIELDS, 'dates' => self::DATES,
        ])->header('Cache-Control', 'private, no-store');
    }

    public function prepare(Request $request, Vehicle $vehicle)
    {
        $this->authorizeDocument();
        $rules = [];
        foreach (self::FIELDS as $key => $label) {
            $rules[$key] = [in_array($key, ['name', 'license']) ? 'required' : 'nullable', 'string', 'max:100'];
        }
        foreach (self::DATES as $key => $label) {
            $rules[$key] = ['nullable', str_ends_with($key, '_time') ? 'date_format:H:i' : 'date_format:Y-m-d'];
        }
        if ($request->filled('pickup_date')) $rules['return_date'][] = 'after_or_equal:pickup_date';
        $data = $request->validate($rules, [
            'required' => 'Preencha :attribute.', 'date_format' => 'Indique uma data ou hora válida em :attribute.',
            'after_or_equal' => 'O dia de entrega não pode ser anterior ao levantamento.',
        ], self::FIELDS + self::DATES);
        if (!empty($data['pickup_date']) && ($data['return_date'] ?? null) === $data['pickup_date']
            && !empty($data['pickup_time']) && !empty($data['return_time']) && $data['return_time'] < $data['pickup_time']) {
            throw \Illuminate\Validation\ValidationException::withMessages(['return_time' => 'A hora de entrega não pode ser anterior ao levantamento.']);
        }
        $data += array_fill_keys(array_keys(self::FIELDS + self::DATES), null);
        $request->session()->put($this->key($vehicle), $data);
        return redirect()->route('admin.vehicles.documents.responsibility.preview', $vehicle);
    }

    public function show(Request $request, Vehicle $vehicle)
    {
        $this->authorizeDocument();
        $data = $request->session()->get($this->key($vehicle));
        if (!$data) return redirect()->route('admin.vehicles.documents.responsibility.edit', $vehicle);
        $missingFields = array_filter(self::FIELDS + self::DATES, fn ($key) => trim((string) ($data[$key] ?? '')) === '', ARRAY_FILTER_USE_KEY);
        return response()->view('admin.vehicles.responsibility-declaration-print', compact('vehicle', 'data', 'missingFields'))->header('Cache-Control', 'private, no-store');
    }

    public function pdf(Request $request, Vehicle $vehicle)
    {
        $this->authorizeDocument();
        $data = $request->session()->get($this->key($vehicle));
        if (!$data) return redirect()->route('admin.vehicles.documents.responsibility.edit', $vehicle);
        $pdf = new Dompdf(new Options(['isRemoteEnabled' => false, 'defaultFont' => 'DejaVu Serif', 'defaultMediaType' => 'print']));
        $pdf->loadHtml(view('admin.vehicles.responsibility-declaration-print', compact('vehicle', 'data') + ['pdf' => true])->render(), 'UTF-8');
        $pdf->setPaper('A4');
        $pdf->render();
        return response($pdf->output(), 200, ['Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="termo-responsabilidade-'.$vehicle->id.'.pdf"', 'Cache-Control' => 'private, no-store']);
    }
}
