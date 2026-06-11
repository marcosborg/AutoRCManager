<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\GeneralState;
use App\Models\Vehicle;
use App\Models\VehicleTradeIn;
use App\Services\SaleClosureApprovalService;
use App\Support\RolePreview;
use Gate;
use Illuminate\Support\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class VehicleTradeInController extends Controller
{
    public function index(Request $request)
    {
        abort_if(! $this->canAccessTradeIns(), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $canManageTradeIns = $this->canConvertTradeIns();
        abort_if(
            ! $canManageTradeIns && $request->query('status') !== VehicleTradeIn::STATUS_CONVERTED,
            Response::HTTP_FORBIDDEN,
            '403 Forbidden'
        );

        $dateField = $this->dateFieldForStatus($request->query('status'));
        $dateStart = $this->parseDateFilter($request->query('date_start'));
        $dateEnd = $this->parseDateFilter($request->query('date_end'));

        $tradeIns = VehicleTradeIn::with(['sold_vehicle.brand', 'sold_vehicle.client', 'created_by', 'media'])
            ->with(['converted_by', 'created_vehicle.brand'])
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->status))
            ->when(! $request->filled('status') && $request->boolean('pending'), fn ($query) => $query->where('status', VehicleTradeIn::STATUS_PENDING))
            ->when($dateStart, fn ($query) => $query->whereDate($dateField, '>=', $dateStart))
            ->when($dateEnd, fn ($query) => $query->whereDate($dateField, '<=', $dateEnd))
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = trim((string) $request->search);
                $normalized = VehicleTradeIn::normalizeLicense($search);

                $query->where(function ($subQuery) use ($search, $normalized) {
                    $subQuery->where('license', 'like', '%' . $search . '%')
                        ->orWhere('normalized_license', 'like', '%' . $normalized . '%')
                        ->orWhereHas('sold_vehicle', function ($vehicleQuery) use ($search, $normalized) {
                            $vehicleQuery
                                ->where('license', 'like', '%' . $search . '%')
                                ->orWhere('foreign_license', 'like', '%' . $search . '%')
                                ->orWhereRaw("REPLACE(REPLACE(UPPER(license), '-', ''), ' ', '') LIKE ?", ['%' . $normalized . '%'])
                                ->orWhereRaw("REPLACE(REPLACE(UPPER(foreign_license), '-', ''), ' ', '') LIKE ?", ['%' . $normalized . '%']);
                        })
                        ->orWhereHas('created_vehicle', function ($vehicleQuery) use ($search, $normalized) {
                            $vehicleQuery
                                ->where('license', 'like', '%' . $search . '%')
                                ->orWhere('foreign_license', 'like', '%' . $search . '%')
                                ->orWhereRaw("REPLACE(REPLACE(UPPER(license), '-', ''), ' ', '') LIKE ?", ['%' . $normalized . '%'])
                                ->orWhereRaw("REPLACE(REPLACE(UPPER(foreign_license), '-', ''), ' ', '') LIKE ?", ['%' . $normalized . '%']);
                        });
                });
            })
            ->orderByDesc('created_at')
            ->paginate(50)
            ->appends($request->query());

        return view('admin.vehicleTradeIns.index', compact('tradeIns', 'dateField', 'canManageTradeIns'));
    }

    public function create()
    {
        abort_if(Gate::denies('vehicle_trade_in_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $brands = Brand::orderBy('name')->pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        return view('admin.vehicleTradeIns.create', compact('brands'));
    }

    public function storeStandalone(Request $request)
    {
        abort_if(Gate::denies('vehicle_trade_in_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $data = $this->validatedData($request, true);
        $data['has_vehicle_delivery_declaration'] = true;
        $normalizedLicense = VehicleTradeIn::normalizeLicense($data['trade_in_license']);
        $this->validateLicenseIsAvailable($normalizedLicense, 'trade_in_license');
        $stockStateId = $this->requiredStockStateId();

        DB::transaction(function () use ($request, $data, $normalizedLicense, $stockStateId) {
            $createdVehicle = $this->createStockVehicle($data, $stockStateId, 'Retoma sem venda associada.');
            $tradeIn = VehicleTradeIn::create($this->payload($data, $normalizedLicense) + [
                'created_by_id' => $request->user()?->id,
                'created_vehicle_id' => $createdVehicle->id,
                'status' => VehicleTradeIn::STATUS_PENDING,
            ]);

            $this->attachTradeInFiles($request, $tradeIn, $createdVehicle);
        });

        $redirectStatus = $this->canConvertTradeIns()
            ? VehicleTradeIn::STATUS_PENDING
            : VehicleTradeIn::STATUS_CONVERTED;

        return redirect()
            ->route('admin.vehicle-trade-ins.index', ['status' => $redirectStatus])
            ->with('message', 'Retoma criada sem venda associada. A viatura foi colocada em stock e aguarda verificacao.');
    }

    public function store(Request $request, Vehicle $vehicle)
    {
        abort_if(Gate::denies('vehicle_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        if (! $request->boolean('create_trade_in_confirmed')) {
            return redirect()
                ->route('admin.vehicles.edit', $vehicle)
                ->with('message', 'Edicao da viatura mantida. A retoma so e criada pelo botao proprio de confirmacao.');
        }

        $data = $this->validatedData($request);
        $normalizedLicense = VehicleTradeIn::normalizeLicense($data['trade_in_license']);
        $this->validateLicenseIsAvailable($normalizedLicense, 'trade_in_license');
        $stockStateId = $this->requiredStockStateId();

        DB::transaction(function () use ($request, $vehicle, $data, $normalizedLicense, $stockStateId) {
            $createdVehicle = $this->createStockVehicle(
                $data,
                $stockStateId,
                'Retoma da viatura #' . $vehicle->id . '.',
                $vehicle->client_id
            );

            $tradeIn = $vehicle->trade_ins()->create($this->payload($data, $normalizedLicense) + [
                'created_by_id' => $request->user()?->id,
                'created_vehicle_id' => $createdVehicle->id,
                'status' => VehicleTradeIn::STATUS_PENDING,
            ]);

            $this->attachTradeInFiles($request, $tradeIn, $createdVehicle);
        });

        return redirect()
            ->route('admin.vehicles.edit', $vehicle)
            ->with('message', 'Retoma criada e viatura colocada em stock disponivel. Aguardando verificacao.');
    }

    public function convert(Request $request, VehicleTradeIn $tradeIn)
    {
        abort_if(! $this->canConvertTradeIns(), Response::HTTP_FORBIDDEN, '403 Forbidden');
        abort_if($tradeIn->status !== VehicleTradeIn::STATUS_PENDING, Response::HTTP_UNPROCESSABLE_ENTITY, 'Retoma ja verificada ou rejeitada.');

        if (! $tradeIn->created_vehicle_id) {
            throw ValidationException::withMessages(['created_vehicle_id' => 'Esta retoma ainda nao tem viatura criada.']);
        }

        $tradeIn->update([
            'status' => VehicleTradeIn::STATUS_CONVERTED,
            'converted_by_id' => $request->user()?->id,
            'converted_at' => now(),
        ]);

        if ($tradeIn->sold_vehicle_id) {
            app(SaleClosureApprovalService::class)->createForTradeIn(
                $tradeIn->sold_vehicle()->firstOrFail(),
                $request->user(),
                $tradeIn
            );
        }

        return redirect()
            ->route('admin.vehicle-trade-ins.index', ['status' => VehicleTradeIn::STATUS_PENDING])
            ->with('message', 'Retoma marcada como verificada.');
    }

    public function reject(Request $request, VehicleTradeIn $tradeIn)
    {
        abort_if(! $this->canConvertTradeIns(), Response::HTTP_FORBIDDEN, '403 Forbidden');
        abort_if($tradeIn->status !== VehicleTradeIn::STATUS_PENDING, Response::HTTP_UNPROCESSABLE_ENTITY, 'Retoma ja tratada.');

        $data = $request->validate([
            'rejection_reason' => ['required', 'string', 'max:1000'],
        ]);

        $tradeIn->update([
            'status' => VehicleTradeIn::STATUS_REJECTED,
            'rejection_reason' => $data['rejection_reason'],
            'rejected_at' => now(),
        ]);

        return back()->with('message', 'Retoma rejeitada.');
    }

    private function rules(bool $standalone = false): array
    {
        $rules = [
            'trade_in_license' => ['required', 'string', 'max:50'],
            'trade_in_amount' => ['required', 'numeric', 'min:0.01'],
            'trade_in_brand_id' => ['required', 'integer', 'exists:brands,id'],
            'trade_in_model' => ['required', 'string', 'max:255'],
            'trade_in_year' => ['required', 'integer', 'min:1900', 'max:' . (now()->year + 1)],
            'trade_in_kilometers' => ['required', 'integer', 'min:0'],
            'trade_in_notes' => ['nullable', 'string'],
            'has_registration_title' => ['nullable', 'boolean'],
            'has_purchase_sale_rgpd' => ['nullable', 'boolean'],
            'has_vehicle_delivery_declaration' => ['nullable', 'boolean'],
            'has_seller_identification' => ['nullable', 'boolean'],
            'has_ipo' => ['nullable', 'boolean'],
            'has_two_keys' => ['nullable', 'boolean'],
            'has_charging_cable_mode_2' => ['nullable', 'boolean'],
            'has_charging_cable_mode_3' => ['nullable', 'boolean'],
            'has_manuals' => ['nullable', 'boolean'],
            'has_internal_invoice' => ['nullable', 'boolean'],
            'has_finance_mod_2' => ['nullable', 'boolean'],
            'has_promissory_note' => ['nullable', 'boolean'],
            'has_reservation_extinction_authorization' => ['nullable', 'boolean'],
            'registration_title.*' => ['nullable', 'file', 'max:10240'],
            'purchase_sale_rgpd' => [$standalone ? 'nullable' : 'required', 'array', 'min:1'],
            'purchase_sale_rgpd.*' => [$standalone ? 'nullable' : 'required', 'file', 'max:10240'],
            'vehicle_delivery_declaration' => [$standalone ? 'required' : 'nullable', 'array', 'min:1'],
            'vehicle_delivery_declaration.*' => [$standalone ? 'required' : 'nullable', 'file', 'max:10240'],
            'seller_identification.*' => ['nullable', 'file', 'max:10240'],
            'ipo.*' => ['nullable', 'file', 'max:10240'],
            'keys.*' => ['nullable', 'file', 'max:10240'],
            'charging_kit.*' => ['nullable', 'file', 'max:10240'],
            'manuals.*' => ['nullable', 'file', 'max:10240'],
            'internal_invoice' => ['required', 'array', 'min:1'],
            'internal_invoice.*' => ['required', 'file', 'max:10240'],
            'finance_mod_2.*' => ['nullable', 'file', 'max:10240'],
            'promissory_note.*' => ['nullable', 'file', 'max:10240'],
            'reservation_extinction_authorization.*' => ['nullable', 'file', 'max:10240'],
            'other_documents.*' => ['nullable', 'file', 'max:10240'],
            'inicial' => ['required', 'array', 'min:1'],
            'inicial.*' => ['required', 'file', 'max:10240'],
        ];

        return $rules;
    }

    private function validatedData(Request $request, bool $standalone = false): array
    {
        $validator = Validator::make($request->all(), $this->rules($standalone));
        if ($validator->fails()) {
            throw (new ValidationException($validator))->errorBag('trade_in');
        }

        return $validator->validated();
    }

    private function createStockVehicle(array $data, int $stockStateId, string $source, ?int $clientId = null): Vehicle
    {
        return Vehicle::create([
            'license' => trim($data['trade_in_license']),
            'general_state_id' => $stockStateId,
            'brand_id' => $data['trade_in_brand_id'],
            'model' => $data['trade_in_model'],
            'year' => $data['trade_in_year'],
            'kilometers' => $data['trade_in_kilometers'],
            'purchase_price' => $data['trade_in_amount'],
            'client_id' => $clientId,
            'acquisition_notes' => trim($source . ' ' . ($data['trade_in_notes'] ?? '')),
        ]);
    }

    private function requiredStockStateId(): int
    {
        $stockStateId = $this->stockStateId();
        if ($stockStateId) {
            return $stockStateId;
        }

        $validator = Validator::make([], []);
        $validator->errors()->add('general_state_id', 'Nao foi encontrado estado de stock para criar a viatura.');
        throw (new ValidationException($validator))->errorBag('trade_in');
    }

    private function payload(array $data, string $normalizedLicense): array
    {
        $checkboxes = [
            'has_registration_title',
            'has_purchase_sale_rgpd',
            'has_vehicle_delivery_declaration',
            'has_seller_identification',
            'has_ipo',
            'has_two_keys',
            'has_charging_cable_mode_2',
            'has_charging_cable_mode_3',
            'has_manuals',
            'has_internal_invoice',
            'has_finance_mod_2',
            'has_promissory_note',
            'has_reservation_extinction_authorization',
        ];

        $payload = [
            'license' => trim($data['trade_in_license']),
            'normalized_license' => $normalizedLicense,
            'amount' => $data['trade_in_amount'],
            'notes' => $data['trade_in_notes'] ?? null,
        ];

        foreach ($checkboxes as $checkbox) {
            $payload[$checkbox] = (bool) ($data[$checkbox] ?? false);
        }

        return $payload;
    }

    private function validateLicenseIsAvailable(string $normalizedLicense, string $field, ?int $ignoreTradeInId = null): void
    {
        if ($normalizedLicense === '') {
            $validator = Validator::make([], []);
            $validator->errors()->add($field, 'Matricula invalida.');
            throw (new ValidationException($validator))->errorBag('trade_in');
        }

        $vehicleExists = Vehicle::withTrashed()
            ->whereRaw("REPLACE(REPLACE(UPPER(license), '-', ''), ' ', '') = ?", [$normalizedLicense])
            ->orWhereRaw("REPLACE(REPLACE(UPPER(foreign_license), '-', ''), ' ', '') = ?", [$normalizedLicense])
            ->exists();

        if ($vehicleExists) {
            $validator = Validator::make([], []);
            $validator->errors()->add($field, 'Ja existe uma viatura com esta matricula.');
            throw (new ValidationException($validator))->errorBag('trade_in');
        }

        $tradeInExists = VehicleTradeIn::query()
            ->where('normalized_license', $normalizedLicense)
            ->when($ignoreTradeInId, fn ($query) => $query->where('id', '!=', $ignoreTradeInId))
            ->whereIn('status', [VehicleTradeIn::STATUS_PENDING, VehicleTradeIn::STATUS_CONVERTED])
            ->exists();

        if ($tradeInExists) {
            $validator = Validator::make([], []);
            $validator->errors()->add($field, 'Ja existe uma retoma pendente ou convertida com esta matricula.');
            throw (new ValidationException($validator))->errorBag('trade_in');
        }
    }

    private function attachFiles(Request $request, VehicleTradeIn $tradeIn): void
    {
        $collections = array_unique(array_merge(
            array_keys(VehicleTradeIn::DOCUMENT_COLLECTIONS),
            array_keys(VehicleTradeIn::STANDALONE_DOCUMENT_COLLECTIONS)
        ));

        foreach ($collections as $collection) {
            foreach ((array) $request->file($collection, []) as $file) {
                $tradeIn->addMedia($file)->toMediaCollection($collection);
            }
        }
    }

    private function attachTradeInFiles(Request $request, VehicleTradeIn $tradeIn, Vehicle $vehicle): void
    {
        $this->attachFiles($request, $tradeIn);
        $this->attachInitialPhotosToVehicle($request, $vehicle);
        $this->copyDocumentsToVehicle($tradeIn, $vehicle);
    }

    private function attachInitialPhotosToVehicle(Request $request, Vehicle $vehicle): void
    {
        foreach ((array) $request->file('inicial', []) as $file) {
            $vehicle->addMedia($file)->toMediaCollection('inicial');
        }
    }

    private function copyDocumentsToVehicle(VehicleTradeIn $tradeIn, Vehicle $vehicle): void
    {
        $targets = [
            'purchase_sale_rgpd' => 'additional_documents',
            'vehicle_delivery_declaration' => 'additional_documents',
            'ipo' => 'documents',
            'internal_invoice' => 'invoice',
            'reservation_extinction_authorization' => 'withdrawal_authorization_file',
        ];

        foreach ($targets as $source => $target) {
            foreach ($tradeIn->getMedia($source) as $media) {
                $vehicle->addMedia($media->getPath())
                    ->preservingOriginal()
                    ->usingName($media->name)
                    ->usingFileName($media->file_name)
                    ->toMediaCollection($target);
            }
        }
    }

    private function stockStateId(): ?int
    {
        return GeneralState::query()
            ->whereRaw('LOWER(name) = ?', ['em stock disponÃ­vel'])
            ->orWhereRaw('LOWER(name) = ?', ['em stock disponivel'])
            ->orWhereRaw('LOWER(name) = ?', ['stand'])
            ->orderByRaw("CASE WHEN LOWER(name) IN ('em stock disponÃ­vel', 'em stock disponivel') THEN 0 ELSE 1 END")
            ->value('id');
    }

    private function canConvertTradeIns(): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }

        return Gate::allows('vehicle_trade_in_convert')
            || RolePreview::hasAnyEffectiveRole($user, ['Admin', 'Gestão', 'Gestao', 'Stand Adm']);
    }

    private function canAccessTradeIns(): bool
    {
        return Gate::allows('vehicle_trade_in_access') || $this->canConvertTradeIns();
    }

    public function pending()
    {
        return redirect()->route('admin.vehicle-trade-ins.index', ['status' => VehicleTradeIn::STATUS_PENDING]);
    }

    private function dateFieldForStatus(?string $status): string
    {
        return match ($status) {
            VehicleTradeIn::STATUS_CONVERTED => 'converted_at',
            VehicleTradeIn::STATUS_REJECTED => 'rejected_at',
            default => 'created_at',
        };
    }

    private function parseDateFilter(mixed $value): ?string
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            return Carbon::createFromFormat('Y-m-d', trim($value))->format('Y-m-d');
        } catch (\Throwable $exception) {
            return null;
        }
    }
}
