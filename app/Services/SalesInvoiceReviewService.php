<?php

namespace App\Services;

use App\Models\Vehicle;
use App\Support\LicensePlate;
use App\Support\VehicleDocuments;
use Illuminate\Database\Eloquent\Builder;

/** Consultation only: declared invoicing and identified files are independent evidence. */
class SalesInvoiceReviewService
{
    public const CATEGORIES = [
        'stand' => 'VENDIDO STAND', 'salvados' => 'VENDIDO SALVADOS',
        'oficina' => 'VENDIDO OFICINA', 'comercio' => 'VENDIDO A COMÉRCIO',
    ];

    public static function invoice(Builder $query): void
    {
        $query->whereIn('collection_name', VehicleDocuments::COLLECTIONS)
            ->where('custom_properties->document_classification->type', 'sale_invoice')
            ->whereNull('custom_properties->document_annulment');
    }

    private static function suspended(Builder $query): void
    {
        $query->whereHas('active_suspended_sale')->orWhereHas('general_state', function ($state) {
            $state->withTrashed()->whereRaw('UPPER(TRIM(name)) = ?', ['VENDA SUSPENSA']);
        });
    }

    public function query(array $filters): Builder
    {
        $query = Vehicle::query()->with(['general_state' => fn ($q) => $q->withTrashed(), 'client'])
            ->withCount(['media as sale_invoice_count' => fn ($q) => self::invoice($q)]);
        if (($filters['scope'] ?? 'sold') === 'suspended') {
            $query->where(fn ($q) => self::suspended($q));
        } else {
            $names = isset($filters['category']) ? [self::CATEGORIES[$filters['category']]] : array_values(self::CATEGORIES);
            $query->whereHas('general_state', fn ($q) => $q->withTrashed()->whereIn(\DB::raw('UPPER(TRIM(name))'), $names))
                ->whereDoesntHave('active_suspended_sale');
        }
        if (!empty($filters['search'])) {
            $term = $filters['search'];
            $query->where(function ($q) use ($term) {
                $q->where(fn ($plate) => LicensePlate::applySearch($plate, $term, ['license', 'foreign_license']))
                    ->orWhereHas('client', fn ($client) => $client->where('name', 'like', '%'.$term.'%'));
            });
        }
        if (!empty($filters['from'])) $query->whereDate('sale_date', '>=', $filters['from']);
        if (!empty($filters['to'])) $query->whereDate('sale_date', '<=', $filters['to']);
        $reason = $filters['reason'] ?? 'pending';
        if ($reason === 'pending') {
            $query->where(fn ($q) => $q->whereNull('is_invoiced')->orWhere('is_invoiced', false)
                ->orWhereDoesntHave('media', fn ($media) => self::invoice($media)));
        } elseif ($reason === 'unmarked') {
            $query->where(fn ($q) => $q->whereNull('is_invoiced')->orWhere('is_invoiced', false));
        } elseif ($reason === 'missing_file') {
            $query->where('is_invoiced', true)->whereDoesntHave('media', fn ($q) => self::invoice($q));
        } elseif ($reason === 'file_unmarked') {
            $query->where(fn ($q) => $q->whereNull('is_invoiced')->orWhere('is_invoiced', false))
                ->whereHas('media', fn ($q) => self::invoice($q));
        }
        return $query->orderByDesc('sale_date')->orderByDesc('id');
    }
}
