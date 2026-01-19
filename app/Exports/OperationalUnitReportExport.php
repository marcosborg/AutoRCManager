<?php

namespace App\Exports;

use Illuminate\Support\Collection;

class OperationalUnitReportExport
{
    public function toCsv(Collection $units, string $from, string $to): string
    {
        $handle = fopen('php://temp', 'r+');

        fputcsv($handle, ['Unidade', 'Custos', 'Receitas', 'Resultado', 'Periodo']);

        foreach ($units as $unit) {
            fputcsv($handle, [
                $unit['unit_name'],
                $unit['total_cost'],
                $unit['total_revenue'],
                $unit['result'],
                sprintf('%s - %s', $from, $to),
            ]);
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return $csv;
    }
}
