<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\CsvImportTrait;
use App\Http\Controllers\Traits\MediaUploadingTrait;
use App\Http\Requests\MassDestroyVehicleRequest;
use App\Http\Requests\StoreVehicleRequest;
use App\Http\Requests\UpdateVehicleRequest;
use App\Models\Brand;
use App\Models\Carrier;
use App\Models\Client;
use App\Models\PaymentStatus;
use App\Models\PickupState;
use App\Models\Suplier;
use App\Models\Vehicle;
use App\Models\GeneralState;
use App\Services\VehicleCsvSyncService;
use Gate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpFoundation\Response;
use Throwable;
use Yajra\DataTables\Facades\DataTables;

class VehicleController extends Controller
{
    use MediaUploadingTrait, CsvImportTrait;

    public function index(Request $request)
    {
        abort_if(Gate::denies('vehicle_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        if ($request->ajax()) {
            $query = Vehicle::with(['general_state', 'brand', 'suplier', 'payment_status', 'carrier', 'pickup_state', 'client'])->select(sprintf('%s.*', (new Vehicle)->table));
            $table = Datatables::of($query);

            $table->addColumn('placeholder', '&nbsp;');
            $table->addColumn('actions', '&nbsp;');

            $table->editColumn('actions', function ($row) {
                $viewGate = 'vehicle_show';
                $editGate = 'vehicle_edit';
                $deleteGate = 'vehicle_delete';
                $crudRoutePart = 'vehicles';

                return view('partials.datatablesActions', compact(
                    'viewGate',
                    'editGate',
                    'deleteGate',
                    'crudRoutePart',
                    'row'
                ));
            });

            $table->addColumn('general_state_name', function ($row) {
                return $row->general_state ? $row->general_state->name : '';
            });

            $table->editColumn('license', function ($row) {
                return $row->license ? $row->license : '';
            });
            $table->editColumn('foreign_license', function ($row) {
                return $row->foreign_license ? $row->foreign_license : '';
            });
            $table->addColumn('brand_name', function ($row) {
                return $row->brand ? $row->brand->name : '';
            });

            $table->editColumn('model', function ($row) {
                return $row->model ? $row->model : '';
            });
            $table->editColumn('month', function ($row) {
                return $row->month ? $row->month : '';
            });
            $table->editColumn('fuel', function ($row) {
                return $row->fuel ? $row->fuel : '';
            });
            $table->editColumn('inspec_b', function ($row) {
                return $row->inspec_b ? $row->inspec_b : '';
            });
            $table->addColumn('suplier_name', function ($row) {
                return $row->suplier ? $row->suplier->name : '';
            });

            $table->editColumn('pvp', function ($row) {
                return $row->pvp ? $row->pvp : '';
            });
            $table->addColumn('client_name', function ($row) {
                return $row->client ? $row->client->name : '';
            });

            $table->editColumn('chekin_documents', function ($row) {
                return $row->chekin_documents ? $row->chekin_documents : '';
            });

            $table->editColumn('key', function ($row) {
                return $row->key ? $row->key : '';
            });

            $table->rawColumns(['actions', 'placeholder', 'general_state', 'brand', 'suplier', 'client']);

            return $table->make(true);
        }

        $general_states = GeneralState::get();
        $brands = Brand::get();
        $supliers = Suplier::get();
        $payment_statuses = PaymentStatus::get();
        $carriers = Carrier::get();
        $pickup_states = PickupState::get();
        $clients = Client::get();

        return view('admin.vehicles.index', compact('general_states', 'brands', 'supliers', 'payment_statuses', 'carriers', 'pickup_states', 'clients'));
    }

    public function create()
    {
        abort_if(Gate::denies('vehicle_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $general_states = GeneralState::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        $brands = Brand::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        $supliers = Suplier::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        $payment_statuses = PaymentStatus::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        $carriers = Carrier::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        $pickup_states = PickupState::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        $clients = Client::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        return view('admin.vehicles.create', compact('general_states', 'brands', 'carriers', 'clients', 'payment_statuses', 'pickup_states', 'supliers'));
    }

    public function store(StoreVehicleRequest $request)
    {
        $payload = $request->all();

        if (! $this->canViewFinancialSensitive()) {
            foreach ($this->sensitiveVehicleFields() as $field) {
                unset($payload[$field]);
            }
        }

        $vehicle = Vehicle::create($payload);

        return redirect()->route('admin.vehicles.edit', $vehicle->id)->with('message', 'Criado com sucesso');
    }

    public function edit(Vehicle $vehicle)
    {
        abort_if(Gate::denies('vehicle_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $canViewSensitive = $this->canViewFinancialSensitive();

        $general_states = GeneralState::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        $brands = Brand::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        $supliers = Suplier::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        $payment_statuses = PaymentStatus::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        $carriers = Carrier::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        $pickup_states = PickupState::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        $clients = Client::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        $relations = [
            'brand',
            'seller_client',
            'buyer_client',
            'suplier',
            'payment_status',
            'carrier',
            'pickup_state',
            'client',
        ];
        $vehicle->load($relations);

        $financialEntries = collect();
        $financialTotalCost = 0.0;
        $financialTotalRevenue = 0.0;
        $financialBalance = 0.0;
        $showWorkshopSection = $this->isWorkshopState($vehicle);

        $purchase_categories = collect();
        $sale_categories = collect();
        $payment_methods = collect();

        return view('admin.vehicles.edit', compact(
            'purchase_categories',
            'sale_categories',
            'general_states',
            'brands',
            'carriers',
            'clients',
            'payment_statuses',
            'pickup_states',
            'supliers',
            'vehicle',
            'financialEntries',
            'financialTotalCost',
            'financialTotalRevenue',
            'financialBalance',
            'showWorkshopSection'
        ));
    }

    public function update(UpdateVehicleRequest $request, Vehicle $vehicle)
    {
        $payload = $request->all();

        if (! $this->canViewFinancialSensitive()) {
            foreach ($this->sensitiveVehicleFields() as $field) {
                unset($payload[$field]);
            }
        }

        $vehicle->update($payload);

        if (count($vehicle->documents) > 0) {
            foreach ($vehicle->documents as $media) {
                if (!in_array($media->file_name, $request->input('documents', []))) {
                    $media->delete();
                }
            }
        }
        $media = $vehicle->documents->pluck('file_name')->toArray();
        foreach ($request->input('documents', []) as $file) {
            if (count($media) === 0 || !in_array($file, $media)) {
                $vehicle->addMedia(storage_path('tmp/uploads/' . basename($file)))->toMediaCollection('documents');
            }
        }

        if (count($vehicle->photos) > 0) {
            foreach ($vehicle->photos as $media) {
                if (!in_array($media->file_name, $request->input('photos', []))) {
                    $media->delete();
                }
            }
        }
        $media = $vehicle->photos->pluck('file_name')->toArray();
        foreach ($request->input('photos', []) as $file) {
            if (count($media) === 0 || !in_array($file, $media)) {
                $vehicle->addMedia(storage_path('tmp/uploads/' . basename($file)))->toMediaCollection('photos');
            }
        }

        if (count($vehicle->invoice) > 0) {
            foreach ($vehicle->invoice as $media) {
                if (!in_array($media->file_name, $request->input('invoice', []))) {
                    $media->delete();
                }
            }
        }
        $media = $vehicle->invoice->pluck('file_name')->toArray();
        foreach ($request->input('invoice', []) as $file) {
            if (count($media) === 0 || !in_array($file, $media)) {
                $vehicle->addMedia(storage_path('tmp/uploads/' . basename($file)))->toMediaCollection('invoice');
            }
        }

        if (count($vehicle->inicial) > 0) {
            foreach ($vehicle->inicial as $media) {
                if (!in_array($media->file_name, $request->input('inicial', []))) {
                    $media->delete();
                }
            }
        }
        $media = $vehicle->inicial->pluck('file_name')->toArray();
        foreach ($request->input('inicial', []) as $file) {
            if (count($media) === 0 || !in_array($file, $media)) {
                $vehicle->addMedia(storage_path('tmp/uploads/' . basename($file)))->toMediaCollection('inicial');
            }
        }

        if (count($vehicle->pdfs) > 0) {
            foreach ($vehicle->pdfs as $media) {
                if (!in_array($media->file_name, $request->input('pdfs', []))) {
                    $media->delete();
                }
            }
        }
        $media = $vehicle->pdfs->pluck('file_name')->toArray();
        foreach ($request->input('pdfs', []) as $file) {
            if (count($media) === 0 || !in_array($file, $media)) {
                $vehicle->addMedia(storage_path('tmp/uploads/' . basename($file)))->toMediaCollection('pdfs');
            }
        }

        if (count($vehicle->withdrawal_authorization_file) > 0) {
            foreach ($vehicle->withdrawal_authorization_file as $media) {
                if (!in_array($media->file_name, $request->input('withdrawal_authorization_file', []))) {
                    $media->delete();
                }
            }
        }
        $media = $vehicle->withdrawal_authorization_file->pluck('file_name')->toArray();
        foreach ($request->input('withdrawal_authorization_file', []) as $file) {
            if (count($media) === 0 || !in_array($file, $media)) {
                $vehicle->addMedia(storage_path('tmp/uploads/' . basename($file)))->toMediaCollection('withdrawal_authorization_file');
            }
        }

        if (count($vehicle->withdrawal_documents) > 0) {
            foreach ($vehicle->withdrawal_documents as $media) {
                if (!in_array($media->file_name, $request->input('withdrawal_documents', []))) {
                    $media->delete();
                }
            }
        }
        $media = $vehicle->withdrawal_documents->pluck('file_name')->toArray();
        foreach ($request->input('withdrawal_documents', []) as $file) {
            if (count($media) === 0 || !in_array($file, $media)) {
                $vehicle->addMedia(storage_path('tmp/uploads/' . basename($file)))->toMediaCollection('withdrawal_documents');
            }
        }

        if (count($vehicle->payment_comprovant) > 0) {
            foreach ($vehicle->payment_comprovant as $media) {
                if (!in_array($media->file_name, $request->input('payment_comprovant', []))) {
                    $media->delete();
                }
            }
        }
        $media = $vehicle->payment_comprovant->pluck('file_name')->toArray();
        foreach ($request->input('payment_comprovant', []) as $file) {
            if (count($media) === 0 || !in_array($file, $media)) {
                $vehicle->addMedia(storage_path('tmp/uploads/' . basename($file)))->toMediaCollection('payment_comprovant');
            }
        }

        return redirect()->back()->with('message', 'Atualizado com sucesso');
    }

    public function show(Vehicle $vehicle)
    {
        abort_if(Gate::denies('vehicle_show'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $vehicle->load('general_state', 'brand', 'seller_client', 'buyer_client', 'suplier', 'payment_status', 'carrier', 'pickup_state', 'client');

        $financialEntries = collect();
        $financialTotalCost = 0.0;
        $financialTotalRevenue = 0.0;
        $financialBalance = 0.0;
        $showWorkshopSection = $this->isWorkshopState($vehicle);

        return view('admin.vehicles.show', compact(
            'vehicle',
            'financialEntries',
            'financialTotalCost',
            'financialTotalRevenue',
            'financialBalance',
            'showWorkshopSection'
        ));
    }

    public function destroy(Vehicle $vehicle)
    {
        abort_if(Gate::denies('vehicle_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $vehicle->delete();

        return back();
    }

    public function massDestroy(MassDestroyVehicleRequest $request)
    {
        $vehicles = Vehicle::find(request('ids'));

        foreach ($vehicles as $vehicle) {
            $vehicle->delete();
        }

        return response(null, Response::HTTP_NO_CONTENT);
    }

    public function storeCKEditorImages(Request $request)
    {
        abort_if(Gate::denies('vehicle_create') && Gate::denies('vehicle_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $model = new Vehicle();
        $model->id = $request->input('crud_id', 0);
        $model->exists = true;
        $media = $model->addMediaFromRequest('upload')->toMediaCollection('ck-media');

        return response()->json(['id' => $media->id, 'url' => $media->getUrl()], Response::HTTP_CREATED);
    }


    public function parseCsvSync(Request $request)
    {
        abort_if(
            Gate::denies('vehicle_create') || Gate::denies('vehicle_delete'),
            Response::HTTP_FORBIDDEN,
            '403 Forbidden'
        );

        $data = $request->validate([
            'csv_file' => ['required', 'file', 'mimes:csv,txt'],
            'has_header' => ['nullable', 'boolean'],
            'delimiter' => ['nullable', 'string', 'max:2'],
            'general_state_id' => ['nullable', 'integer', 'exists:general_states,id'],
        ]);

        $file = $data['csv_file'];
        $filename = 'vehicles-sync-' . now()->format('Ymd_His') . '-' . Str::random(6) . '.csv';
        $relativePath = $file->storeAs('csv_sync', $filename);

        if (!$relativePath || !Storage::exists($relativePath)) {
            return back()->withErrors(['csv_file' => 'Falha ao guardar o CSV.']);
        }

        $delimiterInput = $data['delimiter'] ?? null;
        $hasHeader = (bool) ($data['has_header'] ?? true);

        session()->put('vehicles.sync_csv.file', $relativePath);
        return redirect()->route('admin.vehicles.syncCsvParseForm', [
            'file' => $relativePath,
            'hasHeader' => $hasHeader ? 1 : 0,
            'delimiter' => $delimiterInput,
            'general_state_id' => $data['general_state_id'] ?? null,
        ]);
    }

    public function showCsvSyncParse(Request $request, VehicleCsvSyncService $service)
    {
        abort_if(
            Gate::denies('vehicle_create') || Gate::denies('vehicle_delete'),
            Response::HTTP_FORBIDDEN,
            '403 Forbidden'
        );

        $file = $this->resolveCsvSyncFile($request->query('file'));
        if ($file === null) {
            return redirect()->route('admin.vehicles.index')->withErrors(['csv_file' => 'CSV nao encontrado.']);
        }

        session()->put('vehicles.sync_csv.file', $file);

        $fullPath = storage_path('app/' . $file);
        if (!is_file($fullPath)) {
            session()->forget('vehicles.sync_csv.file');

            return redirect()->route('admin.vehicles.index')->withErrors(['csv_file' => 'CSV nao encontrado.']);
        }

        $delimiterInput = $request->query('delimiter');
        $delimiter = $delimiterInput;
        if ($delimiter === '\\t') {
            $delimiter = "\t";
        }
        if ($delimiter === '') {
            $delimiter = null;
        }

        $hasHeader = (bool) $request->query('hasHeader', 1);
        $generalStateId = $request->query('general_state_id');
        $generalStateId = $generalStateId !== null && $generalStateId !== '' ? (int) $generalStateId : null;

        try {
            $preview = $service->previewCsv($fullPath, $delimiter, $hasHeader);
        } catch (Throwable $exception) {
            return redirect()->route('admin.vehicles.index')->withErrors(['csv_file' => $exception->getMessage()]);
        }

        $suggested = $hasHeader ? $service->suggestMapping($preview['headers']) : [];
        $suggestedByIndex = [];
        foreach ($suggested as $field => $index) {
            $suggestedByIndex[$index] = $field;
        }

        $generalStates = GeneralState::get();

        return view('admin.vehicles.syncCsvParse', [
            'filename' => $file,
            'headers' => $preview['headers'],
            'lines' => $preview['lines'],
            'delimiter' => $delimiterInput,
            'hasHeader' => $hasHeader ? 1 : 0,
            'general_states' => $generalStates,
            'general_state_id' => $generalStateId,
            'suggestedByIndex' => $suggestedByIndex,
        ]);
    }

    public function syncCsv(Request $request, VehicleCsvSyncService $service)
    {
        abort_if(
            Gate::denies('vehicle_create') || Gate::denies('vehicle_delete'),
            Response::HTTP_FORBIDDEN,
            '403 Forbidden'
        );

        if ($request->hasFile('csv_file')) {
            $data = $request->validate([
                'csv_file' => ['required', 'file', 'mimes:csv,txt'],
                'general_state_id' => ['nullable', 'integer', 'exists:general_states,id'],
                'delimiter' => ['nullable', 'string', 'max:2'],
                'has_header' => ['nullable', 'boolean'],
            ]);

            $file = $data['csv_file'];
            $filename = 'vehicles-sync-' . now()->format('Ymd_His') . '-' . Str::random(6) . '.csv';
            $relativePath = $file->storeAs('csv_sync', $filename);

            if (!$relativePath || !Storage::exists($relativePath)) {
                return back()->withErrors(['csv_file' => 'Falha ao guardar o CSV.']);
            }

            $fullPath = storage_path('app/' . $relativePath);

            $generalStateId = $data['general_state_id'] ?? null;
            if ($generalStateId === '' || $generalStateId === null) {
                $generalStateId = null;
            } else {
                $generalStateId = (int) $generalStateId;
            }

            $delimiter = $data['delimiter'] ?? null;
            if ($delimiter === '\\t') {
                $delimiter = "\t";
            }
            if ($delimiter === '') {
                $delimiter = null;
            }

            $hasHeader = (bool) ($data['has_header'] ?? true);

            try {
                $result = $service->syncFromCsv($fullPath, $generalStateId, $delimiter, null, $hasHeader);
            } catch (Throwable $exception) {
                Storage::delete($relativePath);

                return back()->withErrors(['csv_file' => $exception->getMessage()]);
            }

            Storage::delete($relativePath);
        } else {
            $data = $request->validate([
                'filename' => ['required', 'string'],
                'fields' => ['required', 'array'],
                'general_state_id' => ['nullable', 'integer', 'exists:general_states,id'],
                'delimiter' => ['nullable', 'string', 'max:2'],
                'hasHeader' => ['nullable', 'boolean'],
            ]);

            $relativePath = $this->resolveCsvSyncFile($data['filename'] ?? null);

            $redirectParams = [
                'file' => $relativePath ?? ($data['filename'] ?? null),
                'hasHeader' => $data['hasHeader'] ?? 1,
                'delimiter' => $data['delimiter'] ?? null,
                'general_state_id' => $data['general_state_id'] ?? null,
            ];

            if ($relativePath === null) {
                session()->forget('vehicles.sync_csv.file');

                return redirect()->route('admin.vehicles.syncCsvParseForm', $redirectParams)
                    ->withErrors(['csv_file' => 'CSV nao encontrado para sincronizar.'])
                    ->withInput();
            }

            $fullPath = storage_path('app/' . $relativePath);

            if (!is_file($fullPath)) {
                session()->forget('vehicles.sync_csv.file');

                return redirect()->route('admin.vehicles.syncCsvParseForm', $redirectParams)
                    ->withErrors(['csv_file' => 'CSV nao encontrado para sincronizar.'])
                    ->withInput();
            }

            $mapping = [];
            foreach ($data['fields'] as $index => $field) {
                if ($field === '' || $field === null) {
                    continue;
                }

                $mapping[$field] = (int) $index;
            }

            if (!isset($mapping['license'], $mapping['brand'])) {
                return redirect()->route('admin.vehicles.syncCsvParseForm', $redirectParams)
                    ->withErrors(['fields' => 'Selecione colunas para matricula/license e marca/brand.'])
                    ->withInput();
            }

            $generalStateId = $data['general_state_id'] ?? null;
            if ($generalStateId === '' || $generalStateId === null) {
                $generalStateId = null;
            } else {
                $generalStateId = (int) $generalStateId;
            }

            $delimiter = $data['delimiter'] ?? null;
            if ($delimiter === '\\t') {
                $delimiter = "\t";
            }
            if ($delimiter === '') {
                $delimiter = null;
            }

            $hasHeader = (bool) ($data['hasHeader'] ?? true);

            try {
                $result = $service->syncFromCsv($fullPath, $generalStateId, $delimiter, $mapping, $hasHeader);
            } catch (Throwable $exception) {
                Storage::delete($relativePath);
                session()->forget('vehicles.sync_csv.file');

                return redirect()->route('admin.vehicles.syncCsvParseForm', $redirectParams)
                    ->withErrors(['csv_file' => $exception->getMessage()])
                    ->withInput();
            }

            Storage::delete($relativePath);
            session()->forget('vehicles.sync_csv.file');
        }

        $message = sprintf(
            'Sync concluida. CSV=%d, existentes=%d, criadas=%d, removidas=%d, ignoradas=%d, duplicadas=%d.',
            $result['csv_total'],
            $result['existing'],
            $result['created'],
            $result['deleted'],
            $result['skipped'],
            $result['duplicates']
        );

        return redirect()->route('admin.vehicles.index')->with('message', $message);
    }

    private function resolveCsvSyncFile(?string $file): ?string
    {
        $candidate = is_string($file) ? trim($file) : '';
        if ($candidate !== '' && $this->csvSyncFileExists($candidate)) {
            return $candidate;
        }

        $sessionFile = session('vehicles.sync_csv.file');
        if (is_string($sessionFile) && $sessionFile !== '' && $this->csvSyncFileExists($sessionFile)) {
            return $sessionFile;
        }

        return $this->latestCsvSyncFile();
    }

    private function csvSyncFileExists(string $path): bool
    {
        return Storage::exists($path);
    }

    private function latestCsvSyncFile(): ?string
    {
        $files = Storage::files('csv_sync');
        if ($files === []) {
            return null;
        }

        $latest = null;
        $latestTimestamp = null;

        foreach ($files as $file) {
            $timestamp = Storage::lastModified($file);
            if ($latestTimestamp === null || $timestamp > $latestTimestamp) {
                $latestTimestamp = $timestamp;
                $latest = $file;
            }
        }

        return $latest;
    }

    private function canViewFinancialSensitive(): bool
    {
        return Gate::allows('financial_sensitive_access');
    }

    private function sensitiveVehicleFields(): array
    {
        return [
            'purchase_price',
            'purchase_has_vat',
            'purchase_vat_value',
            'commission',
            'iuc_price',
            'tow_price',
            'acquisition_notes',
        ];
    }

    private function isWorkshopState(Vehicle $vehicle): bool
    {
        $stateName = optional($vehicle->general_state)->name;

        if (! $stateName) {
            return false;
        }

        return strcasecmp($stateName, 'OFICINA') === 0;
    }
}
