<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class LicensePlate
{
    public static function normalize(?string $value): string
    {
        return preg_replace('/[^A-Z0-9]+/', '', Str::upper(trim((string) $value))) ?? '';
    }

    public static function formatNational(?string $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        $normalized = self::normalize($value);

        return strlen($normalized) === 6
            ? substr($normalized, 0, 2).'-'.substr($normalized, 2, 2).'-'.substr($normalized, 4, 2)
            : Str::upper($value);
    }

    public static function applySearch(Builder $query, string $term, array $columns = ['license', 'foreign_license']): Builder
    {
        $normalized = self::normalize($term);

        return $query->where(function (Builder $subQuery) use ($term, $normalized, $columns) {
            foreach ($columns as $index => $column) {
                $method = $index === 0 ? 'where' : 'orWhere';
                $subQuery->{$method}($column, 'like', '%'.$term.'%');

                if ($normalized !== '') {
                    $subQuery->orWhereRaw(
                        "REPLACE(REPLACE(REPLACE(UPPER(COALESCE({$column}, '')), '-', ''), ' ', ''), '.', '') LIKE ?",
                        ['%'.$normalized.'%']
                    );
                }
            }
        });
    }
}
