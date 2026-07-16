<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCashTransferRequest;
use App\Http\Requests\StoreWorkshopCashCategoryRequest;
use App\Http\Requests\StoreWorkshopCashExpenseRequest;
use App\Models\AccountOperation;
use App\Models\CashBox;
use App\Models\CashCategory;
use App\Models\CashTransfer;
use App\Models\User;
use App\Services\CashBalanceService;
use Carbon\Carbon;
use Gate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

class WorkshopCashController extends Controller
{
    public function index(Request $request, CashBalanceService $balances)
    {
        abort_if(Gate::denies('workshop_cash_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $workshopBox = $this->workshopBox();
        $movements = AccountOperation::query()
            ->with(['cash_category', 'created_by', 'cash_transfer.from_cash_box', 'cash_transfer.to_cash_box', 'cash_transfer.created_by', 'media'])
            ->where('cash_box_id', $workshopBox->id)
            ->when($request->filled('movement_type'), fn ($query) => $query->where('movement_type', $request->input('movement_type')))
            ->when($request->filled('cash_category_id'), fn ($query) => $query->where('cash_category_id', $request->integer('cash_category_id')))
            ->when($request->filled('created_by_id'), function ($query) use ($request): void {
                $userId = $request->integer('created_by_id');
                $query->where(function ($subQuery) use ($userId): void {
                    $subQuery->where('created_by_id', $userId)
                        ->orWhereHas('cash_transfer', fn ($transferQuery) => $transferQuery->where('created_by_id', $userId));
                });
            })
            ->when($request->filled('date_from'), fn ($query) => $query->whereDate('date', '>=', $request->input('date_from')))
            ->when($request->filled('date_to'), fn ($query) => $query->whereDate('date', '<=', $request->input('date_to')))
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->paginate(50)
            ->appends($request->query());

        $categories = CashCategory::query()->where('cash_box_id', $workshopBox->id)->orderBy('name')->get();
        $cashBoxes = CashBox::query()->where('is_active', true)->where('id', '!=', $workshopBox->id)->orderBy('name')->get();
        $operationUserIds = AccountOperation::query()->where('cash_box_id', $workshopBox->id)->whereNotNull('created_by_id')->pluck('created_by_id');
        $transferUserIds = CashTransfer::query()
            ->where(fn ($query) => $query->where('from_cash_box_id', $workshopBox->id)->orWhere('to_cash_box_id', $workshopBox->id))
            ->whereNotNull('created_by_id')
            ->pluck('created_by_id');
        $users = User::query()->whereIn('id', $operationUserIds->merge($transferUserIds)->unique())->orderBy('name')->get();
        $balance = $balances->balance($workshopBox);

        return view('admin.workshopCash.index', compact('workshopBox', 'movements', 'categories', 'cashBoxes', 'users', 'balance'));
    }

    public function storeExpense(StoreWorkshopCashExpenseRequest $request, CashBalanceService $balances)
    {
        $workshopBox = $this->workshopBox();
        $category = CashCategory::query()
            ->where('cash_box_id', $workshopBox->id)
            ->where('is_active', true)
            ->findOrFail($request->integer('cash_category_id'));

        DB::transaction(function () use ($request, $balances, $workshopBox, $category): void {
            $movement = $balances->expense(
                $workshopBox,
                $category,
                (float) $request->input('total'),
                Carbon::parse($request->input('date')),
                $request->user(),
                $request->input('notes'),
            );
            foreach ($request->file('proofs', []) as $proof) {
                $movement->addMedia($proof)->toMediaCollection('proofs');
            }
        });

        return redirect()->route('admin.workshop-cash.index')->with('message', 'Saída registada com comprovativos.');
    }

    public function storeTransfer(StoreCashTransferRequest $request, CashBalanceService $balances)
    {
        $workshopBox = $this->workshopBox();
        $toBox = CashBox::query()->findOrFail($request->integer('to_cash_box_id'));
        abort_if($toBox->id !== $workshopBox->id, Response::HTTP_UNPROCESSABLE_ENTITY, 'A transferência deve ter como destino a Caixa Oficina.');
        $fromBox = CashBox::query()->findOrFail($request->integer('from_cash_box_id'));

        DB::transaction(function () use ($request, $balances, $fromBox, $toBox): void {
            $transfer = $balances->transfer(
                $fromBox,
                $toBox,
                (float) $request->input('total'),
                Carbon::parse($request->input('occurred_at')),
                $request->user(),
                $request->input('notes'),
            );
            foreach ($request->file('proofs', []) as $proof) {
                $transfer->addMedia($proof)->toMediaCollection('proofs');
            }
        });

        return redirect()->route('admin.workshop-cash.index')->with('message', 'Reforço da Caixa Oficina registado.');
    }

    public function storeCategory(StoreWorkshopCashCategoryRequest $request)
    {
        $workshopBox = $this->workshopBox();
        $name = trim($request->input('name'));
        abort_if(CashCategory::query()->where('cash_box_id', $workshopBox->id)->where('name', $name)->exists(), Response::HTTP_UNPROCESSABLE_ENTITY, 'A categoria já existe.');
        CashCategory::create(['cash_box_id' => $workshopBox->id, 'name' => $name, 'is_active' => true]);

        return back()->with('message', 'Categoria criada.');
    }

    public function updateCategory(Request $request, CashCategory $cashCategory)
    {
        abort_if(Gate::denies('workshop_cash_category_manage'), Response::HTTP_FORBIDDEN, '403 Forbidden');
        $workshopBox = $this->workshopBox();
        abort_if($cashCategory->cash_box_id !== $workshopBox->id, Response::HTTP_NOT_FOUND, '404 Not Found');
        $data = $request->validate([
            'name' => ['required', 'string', 'max:191', Rule::unique('cash_categories')->where('cash_box_id', $workshopBox->id)->ignore($cashCategory)],
            'is_active' => ['required', 'boolean'],
        ]);
        $cashCategory->update($data);

        return back()->with('message', 'Categoria atualizada.');
    }

    private function workshopBox(): CashBox
    {
        return CashBox::query()->where('slug', 'caixa_oficina')->firstOrFail();
    }
}
