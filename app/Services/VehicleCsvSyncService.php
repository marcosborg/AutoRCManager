<?php

namespace App\Services;

use App\Models\Brand;
use App\Models\GeneralState;
use App\Models\Vehicle;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use RuntimeException;

class VehicleCsvSyncService
{
    private ?array $vehicleColumns = null;

    private const HEADER_ALIASES = [
        'license' => ['license', 'licence', 'matricula', 'matricula_veiculo'],
        'brand' => ['brand', 'marca', 'fabricante'],
        'model' => ['model', 'modelo'],
        'general_state_id' => ['general_state_id', 'generalstateid', 'estado', 'estado_id'],
        'version' => ['version', 'versao'],
        'year' => ['year', 'ano'],
        'month' => ['month', 'mes'],
        'mes_iuc' => ['mes_iuc', 'mesiuc', 'mes-iuc', 'mes iuc'],
        'fuel' => ['fuel', 'combustivel'],
        'color' => ['color', 'cor', 'colour'],
        'kilometers' => ['kilometers', 'kilometros', 'kms', 'km'],
        'transmission' => ['transmission', 'caixa', 'cambio'],
        'foreign_license' => ['foreign_license', 'matricula_estrangeira', 'foreignlicense'],
        'inspec_b' => ['inspec_b', 'inspecb', 'inspecao_b', 'inspecao'],
        'purchase_price' => ['purchase_price', 'purchaseprice', 'purchase price', 'preco_custo', 'precocusto', 'preco-custo'],
    ];

    public function syncFromCsv(
        string $path,
        ?int $defaultGeneralStateId = null,
        ?string $delimiter = null,
        ?array $mapping = null,
        bool $hasHeader = true
    ): array
    {
        if (!is_file($path)) {
            throw new RuntimeException("CSV nao encontrado: {$path}");
        }

        [$rows, $stats] = $this->readCsv($path, $delimiter, $mapping, $hasHeader);

        if ($rows === []) {
            return [
                'csv_total' => 0,
                'created' => 0,
                'deleted' => 0,
                'existing' => 0,
                'skipped' => $stats['skipped'],
                'duplicates' => $stats['duplicates'],
            ];
        }

        return DB::transaction(function () use ($rows, $stats, $defaultGeneralStateId): array {
            $existingVehicles = Vehicle::withTrashed()->get(['id', 'license', 'deleted_at']);
            $existingNormalized = [];
            $activeVehicles = [];

            foreach ($existingVehicles as $vehicle) {
                $normalized = $this->normalizeLicense((string) $vehicle->license);
                if ($normalized === '') {
                    continue;
                }

                $existingNormalized[$normalized] = true;

                if ($vehicle->deleted_at === null) {
                    $activeVehicles[$vehicle->id] = $normalized;
                }
            }

            $brandCache = $this->loadBrandCache();
            $created = 0;
            $existing = 0;

            foreach ($rows as $normalizedLicense => $row) {
                if (isset($existingNormalized[$normalizedLicense])) {
                    $existing++;
                    continue;
                }

                $brand = $this->resolveBrand($row['brand'], $brandCache);
                $generalStateId = $row['general_state_id'] ?? $defaultGeneralStateId;

                if ($generalStateId === null) {
                    $generalStateId = GeneralState::query()->value('id');
                }

                if ($generalStateId === null) {
                    throw new RuntimeException('Nenhum general_state_id disponivel para criar viaturas.');
                }

                $payload = [
                    'license' => $normalizedLicense,
                    'brand_id' => $brand->id,
                    'general_state_id' => $generalStateId,
                ];

                $optionalFields = [
                    'model',
                    'version',
                    'year',
                    'month',
                    'mes_iuc',
                    'fuel',
                    'color',
                    'kilometers',
                    'transmission',
                    'foreign_license',
                    'inspec_b',
                    'purchase_price',
                ];

                $availableColumns = array_flip($this->vehicleColumns());

                foreach ($optionalFields as $field) {
                    if (!isset($availableColumns[$field])) {
                        continue;
                    }

                    if (!array_key_exists($field, $row)) {
                        continue;
                    }

                    $value = $row[$field];
                    if ($value === null || $value === '') {
                        continue;
                    }

                    $payload[$field] = $value;
                }

                // Model is stored as a string field; there is no model table to relate to.
                Vehicle::create($payload);

                $created++;
            }

            $csvLicenses = array_fill_keys(array_keys($rows), true);
            $idsToDelete = [];

            foreach ($activeVehicles as $id => $normalized) {
                if (!isset($csvLicenses[$normalized])) {
                    $idsToDelete[] = $id;
                }
            }

            $deleted = 0;
            if ($idsToDelete !== []) {
                // Soft delete keeps history and avoids cascading/nulling related records.
                Vehicle::whereIn('id', $idsToDelete)->delete();
                $deleted = count($idsToDelete);
            }

            return [
                'csv_total' => count($rows),
                'created' => $created,
                'deleted' => $deleted,
                'existing' => $existing,
                'skipped' => $stats['skipped'],
                'duplicates' => $stats['duplicates'],
            ];
        });
    }

    public function previewCsv(
        string $path,
        ?string $delimiter = null,
        bool $hasHeader = true,
        int $sampleRows = 5
    ): array {
        $handle = fopen($path, 'rb');
        if ($handle === false) {
            throw new RuntimeException("Nao foi possivel abrir o CSV: {$path}");
        }

        $headerLine = fgets($handle);
        if ($headerLine === false) {
            fclose($handle);
            throw new RuntimeException('CSV vazio.');
        }

        $headerLine = ltrim($headerLine, "\xEF\xBB\xBF");
        $delimiter = $delimiter ?: $this->detectDelimiter($headerLine);
        $firstRow = str_getcsv($headerLine, $delimiter);

        $headers = $hasHeader ? $firstRow : $this->makeColumnLabels(count($firstRow));
        $lines = [];

        if (!$hasHeader) {
            $lines[] = $firstRow;
        }

        while (($data = fgetcsv($handle, 0, $delimiter)) !== false && count($lines) < $sampleRows) {
            if ($data === [null] || $data === false) {
                continue;
            }

            $lines[] = $data;
        }

        fclose($handle);

        return [
            'headers' => $headers,
            'lines' => $lines,
            'delimiter' => $delimiter,
        ];
    }

    public function suggestMapping(array $headers): array
    {
        return $this->mapHeaders($headers);
    }

    private function readCsv(
        string $path,
        ?string $delimiter = null,
        ?array $mapping = null,
        bool $hasHeader = true
    ): array
    {
        $handle = fopen($path, 'rb');
        if ($handle === false) {
            throw new RuntimeException("Nao foi possivel abrir o CSV: {$path}");
        }

        $headerLine = fgets($handle);
        if ($headerLine === false) {
            fclose($handle);
            throw new RuntimeException('CSV vazio.');
        }

        $headerLine = ltrim($headerLine, "\xEF\xBB\xBF");
        $delimiter = $delimiter ?: $this->detectDelimiter($headerLine);
        $firstRow = str_getcsv($headerLine, $delimiter);
        $headerMap = $hasHeader ? $this->resolveHeaderMap($mapping, $firstRow) : $this->resolveHeaderMap($mapping, []);

        if (!isset($headerMap['license'], $headerMap['brand'])) {
            fclose($handle);
            throw new RuntimeException('CSV precisa de colunas de matricula/license e marca/brand.');
        }

        $rows = [];
        $skipped = 0;
        $duplicates = 0;

        if (!$hasHeader) {
            $this->consumeCsvRow($firstRow, $headerMap, $rows, $skipped, $duplicates);
        }

        while (($data = fgetcsv($handle, 0, $delimiter)) !== false) {
            if ($data === [null] || $data === false) {
                continue;
            }

            $this->consumeCsvRow($data, $headerMap, $rows, $skipped, $duplicates);
        }

        fclose($handle);

        return [$rows, ['skipped' => $skipped, 'duplicates' => $duplicates]];
    }

    private function consumeCsvRow(array $data, array $headerMap, array &$rows, int &$skipped, int &$duplicates): void
    {
        $licenseRaw = $this->extractValue($data, $headerMap['license']);
        $normalizedLicense = $this->normalizeLicense($licenseRaw);
        if ($normalizedLicense === '') {
            $skipped++;
            return;
        }

        if (isset($rows[$normalizedLicense])) {
            $duplicates++;
            return;
        }

        $brand = trim($this->extractValue($data, $headerMap['brand']));
        if ($brand === '') {
            $skipped++;
            return;
        }

        $rows[$normalizedLicense] = [
            'license' => $normalizedLicense,
            'brand' => $brand,
            'model' => $this->extractOptionalString($data, $headerMap, 'model'),
            'general_state_id' => $this->extractOptionalInt($data, $headerMap, 'general_state_id'),
            'version' => $this->extractOptionalString($data, $headerMap, 'version'),
            'year' => $this->extractOptionalInt($data, $headerMap, 'year'),
            'month' => $this->extractOptionalString($data, $headerMap, 'month'),
            'mes_iuc' => $this->extractOptionalString($data, $headerMap, 'mes_iuc'),
            'fuel' => $this->extractOptionalString($data, $headerMap, 'fuel'),
            'color' => $this->extractOptionalString($data, $headerMap, 'color'),
            'kilometers' => $this->extractOptionalInt($data, $headerMap, 'kilometers'),
            'transmission' => $this->extractOptionalString($data, $headerMap, 'transmission'),
            'foreign_license' => $this->extractOptionalString($data, $headerMap, 'foreign_license'),
            'inspec_b' => $this->extractOptionalString($data, $headerMap, 'inspec_b'),
            'purchase_price' => $this->extractOptionalDecimal($data, $headerMap, 'purchase_price'),
        ];
    }

    private function resolveHeaderMap(?array $mapping, array $headers): array
    {
        if ($mapping !== null) {
            return $this->normalizeMapping($mapping);
        }

        if ($headers === []) {
            throw new RuntimeException('CSV sem cabecalho requer mapeamento de colunas.');
        }

        return $this->mapHeaders($headers);
    }

    private function normalizeMapping(array $mapping): array
    {
        $allowed = [
            'license',
            'brand',
            'model',
            'general_state_id',
            'version',
            'year',
            'month',
            'mes_iuc',
            'fuel',
            'color',
            'kilometers',
            'transmission',
            'foreign_license',
            'inspec_b',
            'purchase_price',
        ];
        $normalized = [];

        foreach ($mapping as $field => $index) {
            if (!in_array($field, $allowed, true)) {
                continue;
            }

            $normalized[$field] = (int) $index;
        }

        return $normalized;
    }

    private function makeColumnLabels(int $count): array
    {
        $labels = [];
        for ($i = 1; $i <= $count; $i++) {
            $labels[] = 'Col ' . $i;
        }

        return $labels;
    }

    private function extractOptionalString(array $data, array $headerMap, string $field): ?string
    {
        if (!isset($headerMap[$field])) {
            return null;
        }

        $value = trim($this->extractValue($data, $headerMap[$field]));

        return $value !== '' ? $value : null;
    }

    private function extractOptionalInt(array $data, array $headerMap, string $field): ?int
    {
        $value = $this->extractOptionalString($data, $headerMap, $field);
        if ($value === null) {
            return null;
        }

        $normalized = preg_replace('/[\\s.,]/', '', $value) ?? '';
        if ($normalized === '' || !preg_match('/^-?\\d+$/', $normalized)) {
            return null;
        }

        return (int) $normalized;
    }

    private function extractOptionalDecimal(array $data, array $headerMap, string $field): ?string
    {
        $value = $this->extractOptionalString($data, $headerMap, $field);
        if ($value === null) {
            return null;
        }

        $normalized = str_replace(' ', '', $value);
        if (str_contains($normalized, ',') && str_contains($normalized, '.')) {
            $normalized = str_replace('.', '', $normalized);
            $normalized = str_replace(',', '.', $normalized);
        } elseif (str_contains($normalized, ',')) {
            $normalized = str_replace(',', '.', $normalized);
        }

        $normalized = preg_replace('/[^0-9\\.-]/', '', $normalized) ?? '';
        if ($normalized === '' || !preg_match('/^-?\\d+(\\.\\d+)?$/', $normalized)) {
            return null;
        }

        return $normalized;
    }

    private function vehicleColumns(): array
    {
        if ($this->vehicleColumns === null) {
            $this->vehicleColumns = Schema::getColumnListing((new Vehicle())->getTable());
        }

        return $this->vehicleColumns;
    }

    private function mapHeaders(array $headers): array
    {
        $aliasMap = [];
        foreach (self::HEADER_ALIASES as $field => $aliases) {
            foreach ($aliases as $alias) {
                $aliasMap[$this->normalizeHeader($alias)] = $field;
            }
        }

        $mapped = [];
        foreach ($headers as $index => $header) {
            $normalized = $this->normalizeHeader((string) $header);
            if ($normalized === '') {
                continue;
            }

            if (isset($aliasMap[$normalized])) {
                $mapped[$aliasMap[$normalized]] = $index;
            }
        }

        return $mapped;
    }

    private function normalizeHeader(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        $value = Str::ascii($value);
        $value = Str::lower($value);

        return preg_replace('/[^a-z0-9]+/', '', $value) ?? '';
    }

    private function detectDelimiter(string $line): string
    {
        $candidates = [',', ';', "\t", '|'];
        $best = ',';
        $bestCount = -1;

        foreach ($candidates as $candidate) {
            $count = substr_count($line, $candidate);
            if ($count > $bestCount) {
                $bestCount = $count;
                $best = $candidate;
            }
        }

        return $best;
    }

    private function extractValue(array $row, int $index): string
    {
        return isset($row[$index]) ? (string) $row[$index] : '';
    }

    private function normalizeLicense(string $license): string
    {
        $license = trim($license);
        if ($license === '') {
            return '';
        }

        $license = Str::upper($license);

        return preg_replace('/[\\s-]+/', '', $license) ?? '';
    }

    private function loadBrandCache(): array
    {
        $brands = Brand::withTrashed()->get(['id', 'name', 'deleted_at']);
        $cache = [];

        foreach ($brands as $brand) {
            $key = $this->normalizeBrandName((string) $brand->name);
            if ($key === '') {
                continue;
            }

            $cache[$key] = $brand;
        }

        return $cache;
    }

    private function normalizeBrandName(string $name): string
    {
        $name = trim($name);
        if ($name === '') {
            return '';
        }

        $name = Str::ascii($name);
        $name = Str::lower($name);

        return preg_replace('/\\s+/', ' ', $name) ?? '';
    }

    private function resolveBrand(string $name, array &$cache): Brand
    {
        $trimmed = trim($name);
        if ($trimmed === '') {
            throw new RuntimeException('Marca em falta ao criar viatura.');
        }

        $key = $this->normalizeBrandName($trimmed);
        if (isset($cache[$key])) {
            $brand = $cache[$key];
            if ($brand->trashed()) {
                $brand->restore();
            }

            return $brand;
        }

        $brand = Brand::create(['name' => $trimmed]);
        $cache[$key] = $brand;

        return $brand;
    }
}
