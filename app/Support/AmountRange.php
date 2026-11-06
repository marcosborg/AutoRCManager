<?php

namespace App\Support;

use Illuminate\Http\Request;

class AmountRange
{
    public static function validate(Request $request): void
    {
        foreach (['amount_min', 'amount_max'] as $key) {
            if (is_string($request->input($key))) {
                $request->merge([$key => str_replace(',', '.', trim($request->input($key)))]);
            }
        }
        $request->validate([
            'amount_min' => ['nullable', 'numeric', 'min:0'],
            'amount_max' => array_merge(['nullable', 'numeric', 'min:0'], $request->filled('amount_min') ? ['gte:amount_min'] : []),
        ], ['amount_max.gte' => 'O valor máximo deve ser igual ou superior ao mínimo.']);
    }

    public static function apply($query, Request $request, string $column): void
    {
        // Legacy PVP is text in production, including Portuguese thousands separators.
        // Column is supplied only by trusted application code, never from request data.
        $clean = "REPLACE(REPLACE(TRIM($column), '€', ''), ' ', '')";
        $number = "CASE WHEN $clean REGEXP '^[0-9]{1,3}([.][0-9]{3})+(,[0-9]{1,2})?$' "
            ."THEN CAST(REPLACE(REPLACE($clean, '.', ''), ',', '.') AS DECIMAL(18,2)) "
            ."WHEN $clean REGEXP '^[0-9]+([.,][0-9]{1,2})?$' "
            ."THEN CAST(REPLACE($clean, ',', '.') AS DECIMAL(18,2)) ELSE NULL END";
        foreach (['amount_min' => '>=', 'amount_max' => '<='] as $key => $operator) {
            if ($request->filled($key)) $query->whereRaw("($number) $operator ?", [(float) $request->input($key)]);
        }
    }
}
