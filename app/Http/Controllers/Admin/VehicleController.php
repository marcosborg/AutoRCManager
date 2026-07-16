<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\MediaUploadingTrait;
use App\Http\Requests\MassDestroyVehicleRequest;
use App\Http\Requests\StoreVehicleRequest;
use App\Http\Requests\UpdateVehicleRequest;
use App\Http\Requests\UpdateVehicleWorkshopStateRequest;
use App\Models\AccountOperation;
use App\Models\Brand;
use App\Models\Carrier;
use App\Models\Client;
use App\Models\FinancialInstitution;
use App\Models\GeneralState;
use App\Models\PaymentMethod;
use App\Models\PaymentStatus;
use App\Models\PickupState;
use App\Models\Provenience;
use App\Models\StandCashPaymentApproval;
use App\Models\Suplier;
use App\Models\Vehicle;
use App\Models\VehicleClientPayment;
use App\Models\VehicleGenericPayment;
use App\Models\VehicleSupplierPayment;
use App\Models\VehicleTradeIn;
use App\Models\WorkshopState;
use App\Services\SaleClosureApprovalService;
use App\Services\VehicleImportProcessService;
use App\Services\VehicleLotService;
use App\Services\VehicleProfitabilityService;
use App\Services\VehicleSuspendedSaleService;
use App\Support\RolePreview;
use Gate;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpFoundation\Response;
use Yajra\DataTables\Facades\DataTables;

class VehicleController extends Controller
{
    use MediaUploadingTrait;

    public function index(Request $request)
    {
        abort_if(Gate::denies('vehicle_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        if ($request->ajax()) {
            $query = Vehicle::with(['general_state', 'brand', 'suplier', 'payment_status', 'carrier', 'pickup_state', 'client', 'source_trade_in', 'media'])->select(sprintf('%s.*', (new Vehicle)->table));
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
            $table->filterColumn('license', function ($query, $keyword) {
                $query->searchByLicense((string) $keyword);
            });
            $table->editColumn('foreign_license', function ($row) {
                return $row->foreign_license ? $row->foreign_license : '';
            });
            $table->editColumn('our_registration', function ($row) {
                return $row->our_registration ? $row->our_registration : '';
            });
            $table->addColumn('vehicle_thumb', function ($row) {
                return $this->vehicleThumbnailHtml($row);
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
            $table->editColumn('is_invoiced', function ($row) {
                return $row->is_invoiced ? 'Sim' : 'Nao';
            });
            $table->filterColumn('is_invoiced', function ($query, $keyword) {
                $value = mb_strtolower(trim((string) $keyword, " \t\n\r\0\x0B^$"));
                $truthy = ['sim', '1', 'true', 'yes', 'y'];
                $falsy = ['nao', 'nÃƒÂ£o', 'nÃ£o', '0', 'false', 'no', 'n'];

                if (in_array($value, $truthy, true)) {
                    $query->where('is_invoiced', true);

                    return;
                }

                if (in_array($value, $falsy, true)) {
                    $query->where('is_invoiced', false);

                    return;
                }
            });
            $table->editColumn('source_trade_in', function ($row) {
                return $row->source_trade_in ? 'Sim' : 'Nao';
            });
            $table->filterColumn('source_trade_in', function ($query, $keyword) {
                $value = mb_strtolower(trim((string) $keyword, " \t\n\r\0\x0B^$"));
                $truthy = ['sim', '1', 'true', 'yes', 'y'];
                $falsy = ['nao', 'nÃƒÆ’Ã‚Â£o', 'nÃƒÂ£o', '0', 'false', 'no', 'n'];

                if (in_array($value, $truthy, true)) {
                    $query->whereHas('source_trade_in');

                    return;
                }

                if (in_array($value, $falsy, true)) {
                    $query->whereDoesntHave('source_trade_in');

                    return;
                }
            });
            $table->addColumn('client_name', function ($row) {
                return $row->client ? $row->client->name : '';
            });

            $table->editColumn('chekin_documents', function ($row) {
                return $this->vehicleHasAllDocuments($row) ? 'Sim' : 'Nao';
            });
            $table->filterColumn('chekin_documents', function ($query, $keyword) {
                $value = mb_strtolower(trim((string) $keyword));
                $truthy = ['sim', '1', 'true', 'yes', 'y'];
                $falsy = ['nao', 'nÃ£o', '0', 'false', 'no', 'n'];
                $expression = $this->allDocumentsSqlExpression();

                if (in_array($value, $truthy, true)) {
                    $query->whereRaw("($expression) = 1");

                    return;
                }

                if (in_array($value, $falsy, true)) {
                    $query->whereRaw("($expression) = 0");

                    return;
                }

                $query->where(function ($subQuery) use ($value, $expression) {
                    if (str_starts_with('sim', $value)) {
                        $subQuery->orWhereRaw("($expression) = 1");
                    }

                    if (str_starts_with('nao', $value) || str_starts_with('nÃ£o', $value)) {
                        $subQuery->orWhereRaw("($expression) = 0");
                    }
                });
            });

            $table->editColumn('key', function ($row) {
                return $row->key ? $row->key : '';
            });

            $table->rawColumns(['actions', 'placeholder', 'general_state', 'brand', 'suplier', 'client', 'vehicle_thumb']);

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
        $proveniences = Provenience::where('active', true)->orderBy('name')->pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');
        $financial_institutions = FinancialInstitution::where('active', true)->orderBy('name')->pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');
        $purchasingSuppliers = Suplier::orderBy('name')->pluck('name', 'name');
        $iucMonthRequired = RolePreview::hasAnyEffectiveRole(auth()->user(), ['Stand', 'Stand Adm']);

        return view('admin.vehicles.create', compact('general_states', 'brands', 'carriers', 'clients', 'proveniences', 'financial_institutions', 'payment_statuses', 'pickup_states', 'purchasingSuppliers', 'supliers', 'iucMonthRequired'));
    }

    public function store(StoreVehicleRequest $request)
    {
        $payload = $request->all();

        if (! $this->canViewFinancialSensitive()) {
            foreach ($this->sensitiveVehicleFields() as $field) {
                unset($payload[$field]);
            }
        }

        $payload['is_invoiced'] = $request->boolean('is_invoiced');
        $payload = $this->filterPayloadToExistingVehicleColumns($payload);

        $vehicle = Vehicle::create($payload);

        return redirect()->route('admin.vehicles.edit', $vehicle->id)->with('message', 'Criado com sucesso');
    }

    public function edit(Vehicle $vehicle, VehicleLotService $lotService, VehicleProfitabilityService $profitabilityService)
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
        $proveniences = Provenience::where('active', true)->orderBy('name')->pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');
        $financial_institutions = FinancialInstitution::where('active', true)->orderBy('name')->pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');
        $purchasingSuppliers = Suplier::orderBy('name')->pluck('name', 'name');

        $relations = [
            'brand',
            'seller_client',
            'buyer_client',
            'suplier',
            'payment_status',
            'carrier',
            'pickup_state',
            'client',
            'client_payment_method_info',
            'financial_institution',
            'supplier_payments.payment_method',
            'supplier_payments.media',
            'generic_payments.payment_method',
            'generic_payments.media',
            'client_payments.payment_method',
            'client_payments.media',
            'active_suspended_sale.client',
            'active_suspended_sale.previous_general_state',
            'active_suspended_sale.suspended_by',
            'suspended_sales.client',
            'suspended_sales.previous_general_state',
            'trade_ins.media',
            'trade_ins.created_by',
            'trade_ins.converted_by',
            'trade_ins.created_vehicle',
            'purchase_price_histories.client',
            'purchase_price_histories.changed_by',
            'import_process',
        ];
        $vehicle->load($relations);
        $financialEntries = collect();
        $financialTotalCost = 0.0;
        $financialTotalRevenue = 0.0;
        $financialBalance = 0.0;
        $showWorkshopSection = $this->isWorkshopState($vehicle);
        $vehicleFinancialStatus = $lotService->financialStatusForVehicle($vehicle);
        $rafaelVision = $profitabilityService->build($vehicle);

        $purchase_categories = collect();
        $sale_categories = collect();
        $payment_methods = PaymentMethod::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');
        $supplierPayments = $vehicle->supplier_payments->sortByDesc(function ($payment) {
            return sprintf('%s-%09d', Carbon::createFromFormat(config('panel.date_format'), $payment->paid_at)->format('Ymd'), $payment->id);
        })->values();
        $supplierPaymentsTotal = (float) $vehicle->supplier_payments->sum('amount');
        $purchasePrice = (float) ($vehicle->purchase_price ?? 0);
        $supplierPaymentsOutstanding = $purchasePrice - $supplierPaymentsTotal;
        $genericPayments = $vehicle->generic_payments->sortByDesc(function ($payment) {
            return sprintf('%s-%09d', Carbon::createFromFormat(config('panel.date_format'), $payment->paid_at)->format('Ymd'), $payment->id);
        })->values();
        $genericPaymentsTotal = (float) $vehicle->generic_payments->sum('amount');
        $acquisitionExpensesTotal = (float) $vehicle->acquisition_expenses_total;
        $clientPayments = $vehicle->client_payments->sortByDesc(function ($payment) {
            return sprintf('%s-%09d', Carbon::createFromFormat(config('panel.date_format'), $payment->paid_at)->format('Ymd'), $payment->id);
        })->values();
        $clientPaymentsTotal = (float) $vehicle->client_payments->sum('amount');
        $salesFinalTotal = $this->calculateSalesFinalTotal($vehicle);
        $tradeInsConvertedTotal = (float) $vehicle->trade_ins
            ->where('status', VehicleTradeIn::STATUS_CONVERTED)
            ->sum('amount');
        $clientPaymentsOutstanding = $salesFinalTotal - $clientPaymentsTotal - $tradeInsConvertedTotal;
        $canConvertTradeIns = $this->canConvertTradeIns();
        $canManageSupplierPayments = $this->canManageSupplierPayments();
        $activeSuspendedSale = $vehicle->active_suspended_sale;
        $suspendedSales = $vehicle->suspended_sales->sortByDesc('created_at')->values();
        $importProcess = $vehicle->import_process;
        $showImportProcessTab = Gate::allows('vehicle_import_process_access')
            && ($this->isAdjudicationState($vehicle) || $importProcess);
        $iucMonthRequired = RolePreview::hasAnyEffectiveRole(auth()->user(), ['Stand', 'Stand Adm']);

        return view('admin.vehicles.edit', compact(
            'purchase_categories',
            'sale_categories',
            'payment_methods',
            'general_states',
            'brands',
            'carriers',
            'clients',
            'proveniences',
            'financial_institutions',
            'payment_statuses',
            'pickup_states',
            'purchasingSuppliers',
            'supliers',
            'vehicle',
            'financialEntries',
            'financialTotalCost',
            'financialTotalRevenue',
            'financialBalance',
            'showWorkshopSection',
            'vehicleFinancialStatus',
            'rafaelVision',
            'supplierPayments',
            'supplierPaymentsTotal',
            'supplierPaymentsOutstanding',
            'genericPayments',
            'genericPaymentsTotal',
            'acquisitionExpensesTotal',
            'clientPayments',
            'clientPaymentsTotal',
            'salesFinalTotal',
            'tradeInsConvertedTotal',
            'clientPaymentsOutstanding',
            'canConvertTradeIns',
            'canManageSupplierPayments',
            'activeSuspendedSale',
            'suspendedSales',
            'importProcess',
            'showImportProcessTab',
            'iucMonthRequired',
        ));
    }

    public function sendToWorkshop(Vehicle $vehicle)
    {
        abort_if(! $this->canSendVehicleToWorkshop(), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $workshopGeneralStateId = GeneralState::query()
            ->whereRaw('LOWER(name) = ?', ['oficina'])
            ->value('id');
        $defaultWorkshopState = WorkshopState::default();

        if (! $workshopGeneralStateId || ! $defaultWorkshopState) {
            return back()->withErrors([
                'workshop' => 'Não foi possível localizar o Estado Geral OFICINA ou o Estado da Oficina predefinido.',
            ]);
        }

        DB::transaction(function () use ($vehicle, $workshopGeneralStateId, $defaultWorkshopState): void {
            $vehicle->update([
                'general_state_id' => $workshopGeneralStateId,
                'workshop_state_id' => $defaultWorkshopState->id,
            ]);
        });

        return redirect()
            ->route('admin.vehicles.edit', $vehicle)
            ->with('message', 'Viatura enviada para oficina. A intervenção será criada quando o trabalho for iniciado.');
    }

    public function removeFromWorkshop(Vehicle $vehicle)
    {
        abort_if(Gate::denies('repair_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $isInWorkshop = $vehicle->general_state()
            ->whereRaw('LOWER(name) = ?', ['oficina'])
            ->exists();

        if (! $isInWorkshop) {
            return back()->withErrors([
                'workshop' => 'A viatura já não se encontra na oficina.',
            ]);
        }

        $workshopEntry = $vehicle->state_transfers()
            ->where('to_general_state_id', $vehicle->general_state_id)
            ->whereNotNull('from_general_state_id')
            ->latest('id')
            ->first();

        if (! $workshopEntry?->from_general_state_id) {
            return back()->withErrors([
                'workshop' => 'Não foi possível determinar o estado anterior da viatura. A viatura não foi retirada.',
            ]);
        }

        DB::transaction(function () use ($vehicle, $workshopEntry): void {
            $vehicle->update([
                'general_state_id' => $workshopEntry->from_general_state_id,
                'workshop_state_id' => null,
            ]);
        });

        return back()->with('message', 'Viatura retirada da oficina e reposta no estado anterior.');
    }

    public function updateWorkshopState(UpdateVehicleWorkshopStateRequest $request, Vehicle $vehicle)
    {
        $workshopState = WorkshopState::query()->findOrFail($request->integer('workshop_state_id'));

        if (! $workshopState->is_active && (int) $vehicle->workshop_state_id !== $workshopState->id) {
            return back()->withErrors(['workshop_state_id' => 'O Estado da Oficina selecionado está desativado.']);
        }

        $generalStateName = match (mb_strtolower($workshopState->name)) {
            'vendida', 'vendidos' => 'vendida',
            'entregue', 'entregues' => 'entregue',
            default => null,
        };
        $synchronizedGeneralState = null;
        if ($generalStateName) {
            $synchronizedGeneralState = GeneralState::query()
                ->whereRaw('LOWER(name) = ?', [$generalStateName])
                ->first();
        }

        DB::transaction(function () use ($vehicle, $workshopState, $synchronizedGeneralState): void {
            $vehicle->workshop_state_id = $workshopState->id;
            if ($synchronizedGeneralState) {
                $vehicle->general_state_id = $synchronizedGeneralState->id;
            }
            $vehicle->save();
        });

        return back()->with('message', 'Estado da Oficina atualizado.');
    }

    public function update(UpdateVehicleRequest $request, Vehicle $vehicle, VehicleImportProcessService $importProcessService)
    {
        abort_if(
            $request->boolean('import_process_present') && Gate::denies('vehicle_import_process_edit'),
            Response::HTTP_FORBIDDEN,
            '403 Forbidden'
        );

        $payload = $request->all();

        foreach ([
            'ownership_documents_ready' => 'ownership_documents_ready_at',
            'ownership_payments_completed' => 'ownership_payments_completed_at',
            'ownership_rafael_authorized' => 'ownership_rafael_authorized_at',
        ] as $checkboxField => $timestampField) {
            $payload[$timestampField] = $request->boolean($checkboxField)
                ? ($request->input($timestampField) ?: now())
                : null;
        }

        if (! $this->canViewFinancialSensitive()) {
            foreach ($this->sensitiveVehicleFields() as $field) {
                unset($payload[$field]);
            }
        }

        $payload['is_invoiced'] = $request->boolean('is_invoiced');
        $payload = $this->filterPayloadToExistingVehicleColumns($payload);

        DB::transaction(function () use ($request, $vehicle, &$payload, $importProcessService): void {
            $this->applyWorkshopSalePurchasePrice($request, $vehicle, $payload);
            $vehicle->update($payload);
            $importProcessService->sync($vehicle, $request->all(), $request->user());
            app(VehicleSuspendedSaleService::class)->convertActiveForVehicle($vehicle->fresh(), $request->user());
        });

        if ($this->canViewFinancialSensitive() && $this->canManageSupplierPayments()) {
            $this->createSupplierPaymentLine($request, $vehicle);
        }

        if ($this->canViewFinancialSensitive()) {
            $this->createGenericPaymentLine($request, $vehicle);
        }
        $clientPayment = $this->createClientPaymentLine($request, $vehicle);
        if ($clientPayment) {
            app(SaleClosureApprovalService::class)->createForPayment($vehicle, $request->user(), $clientPayment);
        }

        if (count($vehicle->documents) > 0) {
            foreach ($vehicle->documents as $media) {
                if (! in_array($media->file_name, $request->input('documents', []))) {
                    $media->delete();
                }
            }
        }
        $media = $vehicle->documents->pluck('file_name')->toArray();
        foreach ($request->input('documents', []) as $file) {
            if (count($media) === 0 || ! in_array($file, $media)) {
                $vehicle->addMedia(storage_path('tmp/uploads/'.basename($file)))->toMediaCollection('documents');
            }
        }

        if (count($vehicle->additional_documents) > 0) {
            foreach ($vehicle->additional_documents as $media) {
                if (! in_array($media->file_name, $request->input('additional_documents', []))) {
                    $media->delete();
                }
            }
        }
        $media = $vehicle->additional_documents->pluck('file_name')->toArray();
        foreach ($request->input('additional_documents', []) as $file) {
            if (count($media) === 0 || ! in_array($file, $media)) {
                $vehicle->addMedia(storage_path('tmp/uploads/'.basename($file)))->toMediaCollection('additional_documents');
            }
        }

        if (count($vehicle->photos) > 0) {
            foreach ($vehicle->photos as $media) {
                if (! in_array($media->file_name, $request->input('photos', []))) {
                    $media->delete();
                }
            }
        }
        $media = $vehicle->photos->pluck('file_name')->toArray();
        foreach ($request->input('photos', []) as $file) {
            if (count($media) === 0 || ! in_array($file, $media)) {
                $vehicle->addMedia(storage_path('tmp/uploads/'.basename($file)))->toMediaCollection('photos');
            }
        }
        $this->syncVehiclePhotoOrder($vehicle, $request->input('photos', []));

        if (count($vehicle->invoice) > 0) {
            foreach ($vehicle->invoice as $media) {
                if (! in_array($media->file_name, $request->input('invoice', []))) {
                    $media->delete();
                }
            }
        }
        $media = $vehicle->invoice->pluck('file_name')->toArray();
        foreach ($request->input('invoice', []) as $file) {
            if (count($media) === 0 || ! in_array($file, $media)) {
                $vehicle->addMedia(storage_path('tmp/uploads/'.basename($file)))->toMediaCollection('invoice');
            }
        }

        if (count($vehicle->inicial) > 0) {
            foreach ($vehicle->inicial as $media) {
                if (! in_array($media->file_name, $request->input('inicial', []))) {
                    $media->delete();
                }
            }
        }
        $media = $vehicle->inicial->pluck('file_name')->toArray();
        foreach ($request->input('inicial', []) as $file) {
            if (count($media) === 0 || ! in_array($file, $media)) {
                $vehicle->addMedia(storage_path('tmp/uploads/'.basename($file)))->toMediaCollection('inicial');
            }
        }

        if (count($vehicle->pdfs) > 0) {
            foreach ($vehicle->pdfs as $media) {
                if (! in_array($media->file_name, $request->input('pdfs', []))) {
                    $media->delete();
                }
            }
        }
        $media = $vehicle->pdfs->pluck('file_name')->toArray();
        foreach ($request->input('pdfs', []) as $file) {
            if (count($media) === 0 || ! in_array($file, $media)) {
                $vehicle->addMedia(storage_path('tmp/uploads/'.basename($file)))->toMediaCollection('pdfs');
            }
        }

        if (count($vehicle->withdrawal_authorization_file) > 0) {
            foreach ($vehicle->withdrawal_authorization_file as $media) {
                if (! in_array($media->file_name, $request->input('withdrawal_authorization_file', []))) {
                    $media->delete();
                }
            }
        }
        $media = $vehicle->withdrawal_authorization_file->pluck('file_name')->toArray();
        foreach ($request->input('withdrawal_authorization_file', []) as $file) {
            if (count($media) === 0 || ! in_array($file, $media)) {
                $vehicle->addMedia(storage_path('tmp/uploads/'.basename($file)))->toMediaCollection('withdrawal_authorization_file');
            }
        }

        if (count($vehicle->payment_comprovant) > 0) {
            foreach ($vehicle->payment_comprovant as $media) {
                if (! in_array($media->file_name, $request->input('payment_comprovant', []))) {
                    $media->delete();
                }
            }
        }
        $media = $vehicle->payment_comprovant->pluck('file_name')->toArray();
        foreach ($request->input('payment_comprovant', []) as $file) {
            if (count($media) === 0 || ! in_array($file, $media)) {
                $vehicle->addMedia(storage_path('tmp/uploads/'.basename($file)))->toMediaCollection('payment_comprovant');
            }
        }

        if (count($vehicle->ownership_transfer_proof) > 0) {
            foreach ($vehicle->ownership_transfer_proof as $media) {
                if (! in_array($media->file_name, $request->input('ownership_transfer_proof', []))) {
                    $media->delete();
                }
            }
        }
        $media = $vehicle->ownership_transfer_proof->pluck('file_name')->toArray();
        foreach ($request->input('ownership_transfer_proof', []) as $file) {
            if (count($media) === 0 || ! in_array($file, $media)) {
                $vehicle->addMedia(storage_path('tmp/uploads/'.basename($file)))->toMediaCollection('ownership_transfer_proof');
            }
        }

        if (count($vehicle->ownership_rafael_authorization_proof) > 0) {
            foreach ($vehicle->ownership_rafael_authorization_proof as $media) {
                if (! in_array($media->file_name, $request->input('ownership_rafael_authorization_proof', []))) {
                    $media->delete();
                }
            }
        }
        $media = $vehicle->ownership_rafael_authorization_proof->pluck('file_name')->toArray();
        foreach ($request->input('ownership_rafael_authorization_proof', []) as $file) {
            if (count($media) === 0 || ! in_array($file, $media)) {
                $vehicle->addMedia(storage_path('tmp/uploads/'.basename($file)))->toMediaCollection('ownership_rafael_authorization_proof');
            }
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Atualizado com sucesso',
            ]);
        }

        return redirect()->back()->with('message', 'Atualizado com sucesso');
    }

    public function suspendSale(Request $request, Vehicle $vehicle, VehicleSuspendedSaleService $service)
    {
        abort_if(Gate::denies('vehicle_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $data = $request->validate([
            'client_id' => ['required', 'integer', 'exists:clients,id'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        try {
            $service->suspend($vehicle, (int) $data['client_id'], $request->user(), $data['notes'] ?? null);
        } catch (RuntimeException $exception) {
            return redirect()
                ->route('admin.vehicles.edit', $vehicle)
                ->withErrors(['suspended_sale' => $exception->getMessage()])
                ->withInput();
        }

        return redirect()
            ->route('admin.vehicles.edit', $vehicle)
            ->with('message', 'Venda suspensa criada.');
    }

    public function cancelSuspendedSale(Request $request, Vehicle $vehicle, VehicleSuspendedSaleService $service)
    {
        abort_if(Gate::denies('vehicle_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $suspendedSale = $service->cancel($vehicle, $request->user());
        if (! $suspendedSale) {
            return redirect()
                ->route('admin.vehicles.edit', $vehicle)
                ->withErrors(['suspended_sale' => 'Esta viatura nao tem venda suspensa ativa.']);
        }

        return redirect()
            ->route('admin.vehicles.edit', $vehicle)
            ->with('message', 'Venda suspensa cancelada e viatura libertada.');
    }

    public function show(Vehicle $vehicle, VehicleLotService $lotService)
    {
        abort_if(Gate::denies('vehicle_show'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $vehicle->load('general_state', 'brand', 'seller_client', 'buyer_client', 'suplier', 'payment_status', 'carrier', 'pickup_state', 'client');

        $financialEntries = collect();
        $financialTotalCost = 0.0;
        $financialTotalRevenue = 0.0;
        $financialBalance = 0.0;
        $showWorkshopSection = $this->isWorkshopState($vehicle);
        $vehicleFinancialStatus = $lotService->financialStatusForVehicle($vehicle);

        return view('admin.vehicles.show', compact(
            'vehicle',
            'financialEntries',
            'financialTotalCost',
            'financialTotalRevenue',
            'financialBalance',
            'showWorkshopSection',
            'vehicleFinancialStatus'
        ));
    }

    public function destroy(Vehicle $vehicle)
    {
        abort_if(Gate::denies('vehicle_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $vehicle->delete();

        return back();
    }

    public function deleted()
    {
        abort_if(Gate::denies('vehicle_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $vehicles = Vehicle::onlyTrashed()
            ->with(['general_state', 'brand', 'suplier', 'client'])
            ->orderByDesc('deleted_at')
            ->get();

        return view('admin.vehicles.deleted', compact('vehicles'));
    }

    public function restore(int $vehicle)
    {
        abort_if(Gate::denies('vehicle_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $vehicle = Vehicle::onlyTrashed()->findOrFail($vehicle);
        $vehicle->restore();

        return redirect()
            ->route('admin.vehicles.deleted')
            ->with('message', 'Viatura recuperada com sucesso.');
    }

    public function massDestroy(MassDestroyVehicleRequest $request)
    {
        $vehicles = Vehicle::find(request('ids'));

        foreach ($vehicles as $vehicle) {
            $vehicle->delete();
        }

        return response(null, Response::HTTP_NO_CONTENT);
    }

    public function destroySupplierPayment(Request $request, Vehicle $vehicle, int $payment)
    {
        abort_if(Gate::denies('vehicle_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');
        abort_if(! $this->canViewFinancialSensitive(), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $supplierPayment = VehicleSupplierPayment::where('vehicle_id', $vehicle->id)->findOrFail($payment);
        $supplierPayment->delete();

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Pagamento ao fornecedor removido com sucesso',
            ]);
        }

        return redirect()->back()->with('message', 'Pagamento ao fornecedor removido com sucesso');
    }

    public function destroyGenericPayment(Request $request, Vehicle $vehicle, int $payment)
    {
        abort_if(Gate::denies('vehicle_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');
        abort_if(! $this->canViewFinancialSensitive(), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $genericPayment = VehicleGenericPayment::where('vehicle_id', $vehicle->id)->findOrFail($payment);
        $genericPayment->delete();

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Pagamento generico removido com sucesso',
            ]);
        }

        return redirect()->back()->with('message', 'Pagamento generico removido com sucesso');
    }

    public function destroyClientPayment(Request $request, Vehicle $vehicle, int $payment)
    {
        abort_if(Gate::denies('vehicle_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $clientPayment = VehicleClientPayment::where('vehicle_id', $vehicle->id)->findOrFail($payment);
        $approval = StandCashPaymentApproval::where('vehicle_client_payment_id', $clientPayment->id)->first();

        if ($approval) {
            if ($approval->cash_operation_id) {
                AccountOperation::where('id', $approval->cash_operation_id)->delete();
            }

            $approval->delete();
        }

        $clientPayment->delete();

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Pagamento de cliente removido com sucesso',
            ]);
        }

        return redirect()->back()->with('message', 'Pagamento de cliente removido com sucesso');
    }

    public function storeCKEditorImages(Request $request)
    {
        abort_if(Gate::denies('vehicle_create') && Gate::denies('vehicle_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $model = new Vehicle;
        $model->id = $request->input('crud_id', 0);
        $model->exists = true;
        $media = $model->addMediaFromRequest('upload')->toMediaCollection('ck-media');

        return response()->json(['id' => $media->id, 'url' => $media->getUrl()], Response::HTTP_CREATED);
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
            'iuc_paid_date',
            'iuc_paid_value',
            'tow_paid_date',
            'tow_paid_value',
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

    private function isAdjudicationState(Vehicle $vehicle): bool
    {
        $stateName = optional($vehicle->general_state)->name;

        return $stateName && str($stateName)->lower()->ascii()->squish()->toString() === 'adjudicacao';
    }

    private function filterPayloadToExistingVehicleColumns(array $payload): array
    {
        static $existingColumnsMap = null;

        if ($existingColumnsMap === null) {
            $existingColumnsMap = array_flip(Schema::getColumnListing((new Vehicle)->getTable()));
        }

        return array_intersect_key($payload, $existingColumnsMap);
    }

    private function createSupplierPaymentLine(UpdateVehicleRequest $request, Vehicle $vehicle): void
    {
        $date = $request->input('supplier_payment_date');
        $amount = $request->input('supplier_payment_amount');
        $paymentMethodId = $request->input('supplier_payment_method_id');

        if ($date === null || $date === '' || $amount === null || $amount === '' || $paymentMethodId === null || $paymentMethodId === '') {
            return;
        }

        $payment = $vehicle->supplier_payments()->create([
            'paid_at' => $date,
            'amount' => (float) $amount,
            'payment_method_id' => (int) $paymentMethodId,
        ]);

        if ($request->hasFile('supplier_payment_proof')) {
            $payment->addMediaFromRequest('supplier_payment_proof')->toMediaCollection('proof_file');
        }
    }

    private function syncVehiclePhotoOrder(Vehicle $vehicle, array $orderedFiles): void
    {
        $orderedFiles = collect($orderedFiles)
            ->filter(fn ($file) => is_string($file) && $file !== '')
            ->unique()
            ->values();

        if ($orderedFiles->isEmpty()) {
            return;
        }

        $mediaByFileName = Media::query()
            ->where('model_type', Vehicle::class)
            ->where('model_id', $vehicle->id)
            ->where('collection_name', 'photos')
            ->whereIn('file_name', $orderedFiles->all())
            ->get()
            ->keyBy('file_name');

        foreach ($orderedFiles as $index => $fileName) {
            $media = $mediaByFileName->get($fileName);
            if ($media) {
                $media->order_column = $index + 1;
                $media->save();
            }
        }
    }

    private function createGenericPaymentLine(UpdateVehicleRequest $request, Vehicle $vehicle): void
    {
        $description = $request->input('generic_payment_expense_label');
        $date = $request->input('generic_payment_date');
        $amount = $request->input('generic_payment_amount');
        $paymentMethodId = $request->input('generic_payment_method_id');

        if ($description === null || $description === '' || $date === null || $date === '' || $amount === null || $amount === '' || $paymentMethodId === null || $paymentMethodId === '') {
            return;
        }

        $payment = $vehicle->generic_payments()->create([
            'expense_label' => trim((string) $description),
            'paid_at' => $date,
            'amount' => (float) $amount,
            'payment_method_id' => (int) $paymentMethodId,
        ]);

        if ($request->hasFile('generic_payment_proof')) {
            $payment->addMediaFromRequest('generic_payment_proof')->toMediaCollection('proof_file');
        }
    }

    private function createClientPaymentLine(UpdateVehicleRequest $request, Vehicle $vehicle): ?VehicleClientPayment
    {
        $date = $request->input('client_payment_date');
        $amount = $request->input('client_payment_amount');
        $paymentMethodId = $request->input('client_payment_method_id');

        if ($date === null || $date === '' || $amount === null || $amount === '' || $paymentMethodId === null || $paymentMethodId === '') {
            return null;
        }

        $payment = $vehicle->client_payments()->create([
            'paid_at' => $date,
            'amount' => (float) $amount,
            'payment_method_id' => (int) $paymentMethodId,
        ]);

        if ($request->hasFile('client_payment_proof')) {
            $payment->addMediaFromRequest('client_payment_proof')->toMediaCollection('proof_file');
        }

        if ($this->shouldRequestStandCashValidation($request, (int) $paymentMethodId)) {
            StandCashPaymentApproval::firstOrCreate([
                'vehicle_client_payment_id' => $payment->id,
            ], [
                'vehicle_id' => $vehicle->id,
                'created_by_id' => $request->user()?->id,
                'status' => StandCashPaymentApproval::STATUS_PENDING,
            ]);
        }

        return $payment;
    }

    private function shouldRequestStandCashValidation(UpdateVehicleRequest $request, int $paymentMethodId): bool
    {
        return ! RolePreview::hasAnyEffectiveRole($request->user(), ['Admin', 'Adm', 'Stand Adm']);
    }

    private function calculateSalesFinalTotal(Vehicle $vehicle): float
    {
        return (float) ($vehicle->pvp ?? 0)
            + (float) ($vehicle->sales_iuc ?? 0)
            + (float) ($vehicle->sales_tow ?? 0)
            + (float) ($vehicle->sales_transfer ?? 0)
            + (float) ($vehicle->sales_others ?? 0);
    }

    private function applyWorkshopSalePurchasePrice(UpdateVehicleRequest $request, Vehicle $vehicle, array &$payload): void
    {
        $clientId = isset($payload['client_id']) && $payload['client_id'] !== '' ? (int) $payload['client_id'] : null;
        $saleDate = $payload['sale_date'] ?? null;

        if (! $clientId || ! $saleDate || ! $this->isWorkshopClient($clientId)) {
            return;
        }

        $salePrice = $this->calculateSalesFinalTotalFromPayload($vehicle, $payload);
        if ($salePrice <= 0) {
            return;
        }

        $previousPurchasePrice = $vehicle->purchase_price;
        if (round((float) $previousPurchasePrice, 2) === round($salePrice, 2)) {
            return;
        }

        $vehicle->purchase_price_histories()->create([
            'client_id' => $clientId,
            'changed_by_id' => $request->user()?->id,
            'previous_purchase_price' => $previousPurchasePrice,
            'new_purchase_price' => $salePrice,
            'sale_price' => $salePrice,
            'reason' => 'workshop_sale',
        ]);

        $payload['purchase_price'] = $salePrice;
    }

    private function calculateSalesFinalTotalFromPayload(Vehicle $vehicle, array $payload): float
    {
        return $this->moneyFromPayload($payload, 'pvp', $vehicle->pvp)
            + $this->moneyFromPayload($payload, 'sales_iuc', $vehicle->sales_iuc)
            + $this->moneyFromPayload($payload, 'sales_tow', $vehicle->sales_tow)
            + $this->moneyFromPayload($payload, 'sales_transfer', $vehicle->sales_transfer)
            + $this->moneyFromPayload($payload, 'sales_others', $vehicle->sales_others);
    }

    private function moneyFromPayload(array $payload, string $field, mixed $fallback): float
    {
        $value = array_key_exists($field, $payload) ? $payload[$field] : $fallback;

        return $value === null || $value === '' ? 0.0 : (float) $value;
    }

    private function isWorkshopClient(int $clientId): bool
    {
        return Client::whereKey($clientId)
            ->whereRaw('LOWER(name) = ?', ['oficina'])
            ->exists();
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

    private function canSendVehicleToWorkshop(): bool
    {
        return auth()->check();
    }

    private function canManageSupplierPayments(): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }

        return RolePreview::hasAnyEffectiveRole($user, ['Admin', 'Adm']);
    }

    private function vehicleHasAllDocuments(Vehicle $vehicle): bool
    {
        foreach ($this->documentBooleanFields() as $field) {
            if ((int) ($vehicle->{$field} ?? 0) !== 1) {
                return false;
            }
        }

        return true;
    }

    private function documentBooleanFields(): array
    {
        return [
            'purchase_and_sale_agreement',
            'copy_of_the_citizen_card',
            'tax_identification_card',
            'copy_of_the_stamp_duty_receipt',
            'vehicle_ownership_title',
            'release_of_reservation_or_mortgage',
            'leasing_agreement',
        ];
    }

    private function allDocumentsSqlExpression(): string
    {
        return implode(' AND ', array_map(
            static fn ($field) => sprintf('COALESCE(`vehicles`.`%s`, 0) = 1', $field),
            $this->documentBooleanFields()
        ));
    }

    private function vehicleThumbnailHtml(Vehicle $vehicle): string
    {
        $media = $vehicle->getFirstMedia('photos') ?: $vehicle->getFirstMedia('inicial');
        $placeholder = '<span class="vehicle-list-thumb vehicle-list-thumb-placeholder" style="display:none"><i class="fa fa-car"></i></span>';

        if ($media) {
            $url = e($this->productionMediaUrl($media->getUrl('thumb') ?: $media->getUrl()));
            $alt = e($vehicle->license ?: $vehicle->model ?: 'Viatura');

            return '<img src="'.$url.'" alt="'.$alt.'" class="vehicle-list-thumb" onerror="this.style.display=\'none\';this.nextElementSibling.style.display=\'inline-flex\';">'.$placeholder;
        }

        return '<span class="vehicle-list-thumb vehicle-list-thumb-placeholder"><i class="fa fa-car"></i></span>';
    }

    private function productionMediaUrl(string $url): string
    {
        $mediaBaseUrl = rtrim(env('AUTORC_MEDIA_BASE_URL', 'https://autorcmanager.pt'), '/');
        $host = parse_url($url, PHP_URL_HOST);
        $path = parse_url($url, PHP_URL_PATH) ?: '';
        $query = parse_url($url, PHP_URL_QUERY);

        if (! $host || in_array($host, ['127.0.0.1', 'localhost', '0.0.0.0'], true)) {
            return $mediaBaseUrl.$path.($query ? '?'.$query : '');
        }

        return $url;
    }
}
