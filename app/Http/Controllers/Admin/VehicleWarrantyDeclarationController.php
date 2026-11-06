<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use App\Support\WorkshopPrivacy;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class VehicleWarrantyDeclarationController extends Controller
{
    public const FIELDS = ['name' => 'Nome do cliente', 'identity' => 'BI/CC', 'vat' => 'NIF',
        'brand' => 'Marca', 'model' => 'Modelo', 'license' => 'Matrícula', 'vin' => 'N.º de chassis'];

    private function authorizeDocument(): void
    {
        abort_unless(!WorkshopPrivacy::restricted() && Gate::allows('vehicle_edit')
            && Gate::allows('vehicle_documents_area_access') && Gate::allows('vehicle_others_area_access'), 403);
    }

    private function key(Vehicle $vehicle): string
    {
        return 'warranty_declaration_v1.'.auth()->id().'.'.$vehicle->id;
    }

    public function edit(Request $request, Vehicle $vehicle)
    {
        $this->authorizeDocument();
        $vehicle->load('client', 'brand');
        $data = $request->session()->get($this->key($vehicle), [
            'name' => $vehicle->client?->name, 'vat' => $vehicle->client?->vat,
            'brand' => $vehicle->brand?->name, 'model' => $vehicle->model,
            'license' => $vehicle->license ?: $vehicle->foreign_license, 'kilometers' => $vehicle->kilometers,
        ]);
        return response()->view('admin.vehicles.warranty-declaration-form', [
            'vehicle' => $vehicle, 'data' => $data, 'fields' => self::FIELDS,
        ])->header('Cache-Control', 'private, no-store');
    }

    public function prepare(Request $request, Vehicle $vehicle)
    {
        $this->authorizeDocument();
        $rules = [];
        foreach (self::FIELDS as $key => $label) {
            $rules[$key] = [in_array($key, ['identity', 'vin']) ? 'nullable' : 'required', 'string', 'max:100'];
        }
        $rules += [
            'starts_on' => ['nullable', 'required_with:ends_on', 'date_format:Y-m-d'],
            'ends_on' => ['nullable', 'required_with:starts_on', 'date_format:Y-m-d', 'after_or_equal:starts_on'],
            'kilometers' => ['nullable', 'integer', 'min:0', 'max:9999999'],
            'covered_kilometers' => ['nullable', 'integer', 'min:1', 'max:9999999'],
            'confirmed' => ['accepted'],
        ];
        $data = $request->validate($rules, [
            'required' => 'Preencha :attribute.', 'required_with' => 'Preencha as duas datas ou deixe ambas em branco.',
            'accepted' => 'Confirme a revisão dos dados e das condições antes de preparar o documento.',
            'after_or_equal' => 'A data de conclusão não pode ser anterior à data de início.',
        ], self::FIELDS + ['starts_on' => 'data de início', 'ends_on' => 'data de conclusão',
            'kilometers' => 'quilómetros da viatura', 'covered_kilometers' => 'quilómetros da garantia', 'confirmed' => 'revisão']);
        unset($data['confirmed']);
        $data += array_fill_keys(array_keys(self::FIELDS) , null);
        $data += ['starts_on' => null, 'ends_on' => null, 'kilometers' => null, 'covered_kilometers' => null];
        $request->session()->put($this->key($vehicle), $data);
        return redirect()->route('admin.vehicles.documents.warranty.preview', $vehicle);
    }

    public function show(Request $request, Vehicle $vehicle)
    {
        $this->authorizeDocument();
        $data = $request->session()->get($this->key($vehicle));
        if (!$data) return redirect()->route('admin.vehicles.documents.warranty.edit', $vehicle);
        return response()->view('admin.vehicles.warranty-declaration-print', compact('vehicle', 'data'))->header('Cache-Control', 'private, no-store');
    }

    public function pdf(Request $request, Vehicle $vehicle)
    {
        $this->authorizeDocument();
        $data = $request->session()->get($this->key($vehicle));
        if (!$data) return redirect()->route('admin.vehicles.documents.warranty.edit', $vehicle);
        $pdf = new Dompdf(new Options(['isRemoteEnabled' => false, 'defaultFont' => 'DejaVu Serif', 'defaultMediaType' => 'print']));
        $pdf->loadHtml(view('admin.vehicles.warranty-declaration-print', compact('vehicle', 'data') + ['pdf' => true])->render(), 'UTF-8');
        $pdf->setPaper('A4');
        $pdf->render();
        return response($pdf->output(), 200, ['Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="declaracao-garantia-'.$vehicle->id.'.pdf"', 'Cache-Control' => 'private, no-store']);
    }
}
