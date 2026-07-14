<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AccountCategory;
use App\Models\AccountDepartment;
use App\Models\AccountItem;
use App\Models\AccountOperation;
use App\Models\CashBox;
use App\Models\CashCategory;
use App\Models\Department;
use App\Models\PaymentMethod;
use App\Models\Vehicle;
use App\Services\CashBalanceService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

class CashController extends Controller
{
    public function index(Request $request)
    {
        $this->authorizeCashAccess();

        $query = $this->filteredMovements($request);

        $movements = (clone $query)
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->paginate(50)
            ->appends($request->query());

        $summary = $this->summaryFor($query, $request);
        $pendingAccounting = $this->pendingAccountingFor($query);

        return view('admin.cash.index', $this->formOptions() + compact(
            'movements',
            'summary',
            'pendingAccounting'
        ));
    }

    public function store(Request $request, CashBalanceService $balances)
    {
        $this->authorizeCashAccess();

        $data = $request->validate([
            'movement_type' => ['required', 'in:income,outcome'],
            'date' => ['required', 'date'],
            'description' => ['required', 'string', 'max:255'],
            'total' => ['required', 'numeric', 'min:0.01'],
            'cash_box_id' => ['nullable', 'integer', 'exists:cash_boxes,id'],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'cash_category_id' => ['nullable', 'integer', Rule::exists('cash_categories', 'id')->whereNull('cash_box_id')],
            'payment_method_id' => ['nullable', 'integer', 'exists:payment_methods,id'],
            'vehicle_id' => ['nullable', 'integer', 'exists:vehicles,id'],
            'notes' => ['nullable', 'string'],
            'is_accounted' => ['nullable', 'boolean'],
        ]);

        $selectedBox = ! empty($data['cash_box_id']) ? CashBox::query()->findOrFail($data['cash_box_id']) : null;
        if ($selectedBox?->slug === 'caixa_oficina') {
            return back()->withErrors(['cash_box_id' => 'Use o ecrã Caixa da Oficina para registar movimentos nesta caixa.'])->withInput();
        }

        $movement = DB::transaction(function () use ($data, $selectedBox, $balances) {
            if ($selectedBox) {
                CashBox::query()->whereKey($selectedBox->id)->lockForUpdate()->firstOrFail();
                if ($data['movement_type'] === AccountOperation::TYPE_OUTCOME) {
                    $balances->ensureSufficientBalance($selectedBox, (float) $data['total']);
                }
            }

            $accountItem = $this->legacyItemFor(
                $data['description'],
                $data['movement_type'],
                $data['department_id'] ?? null,
                $data['cash_category_id'] ?? null
            );

            return AccountOperation::create([
                'description' => $data['description'],
                'movement_type' => $data['movement_type'],
                'total' => $data['total'],
                'account_item_id' => $accountItem?->id,
                'department_id' => $data['department_id'] ?? null,
                'cash_category_id' => $data['cash_category_id'] ?? null,
                'vehicle_id' => $data['vehicle_id'] ?? null,
                'qty' => 1,
                'date' => $data['date'],
                'payment_method_id' => $data['payment_method_id'] ?? null,
                'cash_box_id' => $data['cash_box_id'] ?? null,
                'notes' => $data['notes'] ?? null,
                'is_accounted' => ! empty($data['is_accounted']),
                'accounted_at' => ! empty($data['is_accounted']) ? now() : null,
                'accounted_by' => ! empty($data['is_accounted']) ? auth()->id() : null,
                'created_by_id' => auth()->id(),
            ]);
        });

        return redirect()
            ->route('admin.cash.index', ['highlight' => $movement->id])
            ->with('message', 'Movimento de caixa registado.');
    }

    public function transfer(Request $request, CashBalanceService $balances)
    {
        $this->authorizeCashAccess();

        $data = $request->validate([
            'date' => ['required', 'date'],
            'from_cash_box_id' => ['required', 'integer', 'exists:cash_boxes,id', 'different:to_cash_box_id'],
            'to_cash_box_id' => ['required', 'integer', 'exists:cash_boxes,id'],
            'total' => ['required', 'numeric', 'min:0.01'],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'cash_category_id' => ['nullable', 'integer', Rule::exists('cash_categories', 'id')->whereNull('cash_box_id')],
            'notes' => ['nullable', 'string'],
            'proofs' => ['nullable', 'array'],
            'proofs.*' => ['file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:20480'],
        ]);

        $fromBox = CashBox::findOrFail($data['from_cash_box_id']);
        $toBox = CashBox::findOrFail($data['to_cash_box_id']);
        $transfer = $balances->transfer(
            $fromBox,
            $toBox,
            (float) $data['total'],
            Carbon::parse($data['date']),
            $request->user(),
            $data['notes'] ?? null,
            $data['department_id'] ?? null,
            $data['cash_category_id'] ?? $this->defaultTransferCategoryId(),
        );
        foreach ($request->file('proofs', []) as $proof) {
            $transfer->addMedia($proof)->toMediaCollection('proofs');
        }

        return redirect()
            ->route('admin.cash.index', ['transfer_group_id' => $transfer->group_id])
            ->with('message', 'Transferência registada com rastreabilidade.');
    }

    public function toggleAccounted(Request $request, AccountOperation $operation)
    {
        $this->authorizeCashAccess();

        $data = $request->validate([
            'is_accounted' => ['required', 'boolean'],
        ]);

        $operation->update([
            'is_accounted' => (bool) $data['is_accounted'],
            'accounted_at' => $data['is_accounted'] ? now() : null,
            'accounted_by' => $data['is_accounted'] ? auth()->id() : null,
        ]);

        return back()->with('message', 'Estado de contabilização atualizado.');
    }

    public function storeDepartment(Request $request)
    {
        $this->authorizeCashAccess();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:departments,name'],
        ]);

        $department = Department::create([
            'name' => trim($data['name']),
            'is_active' => true,
        ]);

        AccountDepartment::firstOrCreate(['name' => $department->name]);

        return response()->json($department, Response::HTTP_CREATED);
    }

    public function storeCategory(Request $request)
    {
        $this->authorizeCashAccess();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:cash_categories,name'],
        ]);

        $category = CashCategory::create([
            'name' => trim($data['name']),
            'is_active' => true,
        ]);

        AccountCategory::firstOrCreate(['name' => $category->name]);

        return response()->json($category, Response::HTTP_CREATED);
    }

    public function storeCashBox(Request $request)
    {
        $this->authorizeCashAccess();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:cash_boxes,name'],
        ]);

        $cashBox = CashBox::create([
            'name' => trim($data['name']),
            'is_active' => true,
        ]);

        return response()->json($cashBox, Response::HTTP_CREATED);
    }

    private function filteredMovements(Request $request, bool $includeDateRange = true)
    {
        return AccountOperation::with([
            'account_item.account_category.account_department',
            'department',
            'cash_category',
            'cash_box',
            'payment_method',
            'vehicle.brand',
            'accountant',
        ])
            ->when($request->filled('department_id'), fn ($query) => $query->where('department_id', $request->integer('department_id')))
            ->when($request->filled('cash_category_id'), fn ($query) => $query->where('cash_category_id', $request->integer('cash_category_id')))
            ->when($request->filled('cash_box_id'), fn ($query) => $query->where('cash_box_id', $request->integer('cash_box_id')))
            ->when($request->filled('movement_type'), function ($query) use ($request) {
                $type = $request->input('movement_type');
                $query->where(function ($subQuery) use ($type) {
                    $subQuery->where('movement_type', $type)
                        ->orWhereHas('account_item', fn ($itemQuery) => $itemQuery->where('type', $type));
                });
            })
            ->when($request->filled('accounted'), fn ($query) => $query->where('is_accounted', (bool) $request->integer('accounted')))
            ->when($includeDateRange && $request->filled('date_from'), fn ($query) => $query->whereDate('date', '>=', $request->input('date_from')))
            ->when($includeDateRange && $request->filled('date_to'), fn ($query) => $query->whereDate('date', '<=', $request->input('date_to')))
            ->when($request->filled('amount_min'), fn ($query) => $query->where('total', '>=', (float) $request->input('amount_min')))
            ->when($request->filled('amount_max'), fn ($query) => $query->where('total', '<=', (float) $request->input('amount_max')))
            ->when($request->filled('transfer_group_id'), fn ($query) => $query->where('transfer_group_id', $request->input('transfer_group_id')))
            ->when($request->filled('q'), function ($query) use ($request) {
                $term = '%'.trim((string) $request->input('q')).'%';
                $query->where(function ($subQuery) use ($term) {
                    $subQuery->where('description', 'like', $term)
                        ->orWhere('notes', 'like', $term)
                        ->orWhereHas('account_item', fn ($itemQuery) => $itemQuery->where('name', 'like', $term));
                });
            });
    }

    private function summaryFor($query, Request $request): array
    {
        $rows = $this->totalsByBoxAndType(clone $query);

        $boxes = CashBox::orderBy('id')->get();
        $boxSummaries = $boxes->map(function (CashBox $box) use ($rows) {
            $income = (float) $rows->where('cash_box_id', $box->id)->where('type', AccountOperation::TYPE_INCOME)->sum('total');
            $outcome = (float) $rows->where('cash_box_id', $box->id)->where('type', AccountOperation::TYPE_OUTCOME)->sum('total');

            return [
                'name' => $box->name,
                'income' => $income,
                'outcome' => $outcome,
                'balance' => $income - $outcome,
            ];
        });

        $departmentRows = (clone $query)
            ->selectRaw("COALESCE(departments.name, account_departments.name, 'Sem departamento') as department_name")
            ->selectRaw('COALESCE(account_operations.movement_type, account_items.type) as type')
            ->selectRaw('SUM(account_operations.total) as total')
            ->leftJoin('account_items', 'account_items.id', '=', 'account_operations.account_item_id')
            ->leftJoin('account_categories', 'account_categories.id', '=', 'account_items.account_category_id')
            ->leftJoin('account_departments', 'account_departments.id', '=', 'account_categories.account_department_id')
            ->leftJoin('departments', 'departments.id', '=', 'account_operations.department_id')
            ->groupByRaw("COALESCE(departments.name, account_departments.name, 'Sem departamento')")
            ->groupByRaw('COALESCE(account_operations.movement_type, account_items.type)')
            ->get();

        $departmentSummaries = $departmentRows
            ->groupBy('department_name')
            ->map(function ($rows, string $name) {
                $income = (float) $rows->where('type', AccountOperation::TYPE_INCOME)->sum('total');
                $outcome = (float) $rows->where('type', AccountOperation::TYPE_OUTCOME)->sum('total');

                return [
                    'name' => $name,
                    'income' => $income,
                    'outcome' => $outcome,
                    'balance' => $income - $outcome,
                ];
            })
            ->sortBy('name')
            ->values();

        return [
            'boxes' => $boxSummaries,
            'departments' => $departmentSummaries,
            'history' => $this->cashBoxHistoryFor($request),
        ];
    }

    private function cashBoxHistoryFor(Request $request)
    {
        $baseQuery = $this->filteredMovements($request, false);

        $openingRows = collect();
        if ($request->filled('date_from')) {
            $openingRows = $this->totalsByBoxAndType(
                (clone $baseQuery)->whereDate('date', '<', $request->input('date_from'))
            );
        }

        $periodQuery = clone $baseQuery;
        if ($request->filled('date_from')) {
            $periodQuery->whereDate('date', '>=', $request->input('date_from'));
        }
        if ($request->filled('date_to')) {
            $periodQuery->whereDate('date', '<=', $request->input('date_to'));
        }

        $periodRows = $this->totalsByBoxAndType($periodQuery);

        $boxes = CashBox::orderBy('id')->get()->map(fn (CashBox $box) => [
            'id' => $box->id,
            'name' => $box->name,
        ]);

        if ($openingRows->whereNull('cash_box_id')->isNotEmpty() || $periodRows->whereNull('cash_box_id')->isNotEmpty()) {
            $boxes->push([
                'id' => null,
                'name' => 'Sem caixa',
            ]);
        }

        return $boxes->map(function (array $box) use ($openingRows, $periodRows) {
            $openingIncome = (float) $openingRows->where('cash_box_id', $box['id'])->where('type', AccountOperation::TYPE_INCOME)->sum('total');
            $openingOutcome = (float) $openingRows->where('cash_box_id', $box['id'])->where('type', AccountOperation::TYPE_OUTCOME)->sum('total');
            $periodIncome = (float) $periodRows->where('cash_box_id', $box['id'])->where('type', AccountOperation::TYPE_INCOME)->sum('total');
            $periodOutcome = (float) $periodRows->where('cash_box_id', $box['id'])->where('type', AccountOperation::TYPE_OUTCOME)->sum('total');

            $openingBalance = $openingIncome - $openingOutcome;
            $periodBalance = $periodIncome - $periodOutcome;

            return [
                'name' => $box['name'],
                'opening_balance' => $openingBalance,
                'period_income' => $periodIncome,
                'period_outcome' => $periodOutcome,
                'period_balance' => $periodBalance,
                'closing_balance' => $openingBalance + $periodBalance,
            ];
        });
    }

    private function totalsByBoxAndType($query)
    {
        return $query
            ->selectRaw('COALESCE(account_operations.movement_type, account_items.type) as type')
            ->selectRaw('account_operations.cash_box_id')
            ->selectRaw('SUM(account_operations.total) as total')
            ->selectRaw('COUNT(*) as movements_count')
            ->leftJoin('account_items', 'account_items.id', '=', 'account_operations.account_item_id')
            ->groupBy('account_operations.cash_box_id')
            ->groupByRaw('COALESCE(account_operations.movement_type, account_items.type)')
            ->get();
    }

    private function pendingAccountingFor($query): array
    {
        $pending = (clone $query)->where('is_accounted', false);

        return [
            'count' => (int) $pending->count(),
            'total' => (float) $pending->sum('total'),
        ];
    }

    private function formOptions(): array
    {
        return [
            'departments' => Department::where('is_active', true)->orderBy('name')->get(),
            'cashCategories' => CashCategory::where('is_active', true)->whereNull('cash_box_id')->orderBy('name')->get(),
            'cashBoxes' => CashBox::where('is_active', true)->orderBy('id')->get(),
            'paymentMethods' => PaymentMethod::orderBy('name')->get(),
            'vehicles' => Vehicle::with('brand')->orderByDesc('id')->limit(500)->get(),
        ];
    }

    private function legacyItemFor(string $description, string $type, ?int $departmentId, ?int $categoryId): ?AccountItem
    {
        $department = $departmentId ? Department::find($departmentId) : null;
        $cashCategory = $categoryId ? CashCategory::find($categoryId) : null;

        $accountDepartment = $department
            ? AccountDepartment::firstOrCreate(['name' => $department->name])
            : null;

        $accountCategory = $cashCategory
            ? AccountCategory::firstOrCreate([
                'name' => $cashCategory->name,
                'account_department_id' => $accountDepartment?->id,
            ])
            : null;

        return AccountItem::firstOrCreate([
            'name' => $description,
            'type' => $type,
            'account_category_id' => $accountCategory?->id,
        ], [
            'total' => null,
        ]);
    }

    private function defaultTransferCategoryId(): ?int
    {
        return CashCategory::firstOrCreate([
            'name' => 'Transferência entre caixas',
            'cash_box_id' => null,
        ], [
            'is_active' => true,
        ])->id;
    }

    private function authorizeCashAccess(): void
    {
        $user = auth()->user();
        $allowed = $user && $user->roles->contains(function ($role) {
            return in_array($role->title, ['Admin', 'Adm'], true);
        });

        abort_if(! $allowed, Response::HTTP_FORBIDDEN, '403 Forbidden');
    }
}
