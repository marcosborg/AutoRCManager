<?php

namespace App\Console\Commands;

use App\Models\Brand;
use App\Models\GeneralState;
use App\Models\Vehicle;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use SpreadsheetReader;
use Throwable;

class ImportStandVehicles extends Command
{
    protected $signature = 'vehicles:import-stand
                            {path : Caminho para CSV/XLS/XLSX/ODS}
                            {--state=Stand : Estado geral a aplicar}
                            {--normalized-output= : Caminho para gravar CSV normalizado}
                            {--dry-run : Valida e mostra contagens sem gravar}';

    protected $description = 'Importa viaturas de stock em modo aditivo, sem remover existentes.';

    private int $normalizationSkipped = 0;

    private int $normalizationDuplicates = 0;

    private const HEADER_ALIASES = [
        'brand' => ['marca', 'brand', 'marca/modelo', 'fabricante'],
        'model' => ['modelo', 'model'],
        'license' => ['matricula', 'matrícula', 'license', 'licence', 'matricula veiculo', 'matricula_veiculo'],
        'version' => ['versao', 'versão', 'version'],
        'year' => ['ano', 'data da matricula', 'data da matrícula', 'year'],
        'kilometers' => ['kms', 'km', 'quilometros', 'quilómetros', 'kilometers', 'kilometros'],
        'fuel' => ['combustivel', 'combustível', 'fuel'],
        'pvp' => ['preco marcado', 'preço marcado', 'pvp'],
        'minimum_price' => ['preco minimo', 'preço minimo', 'preço mínimo', 'minimum_price'],
    ];

    public function handle(): int
    {
        $path = (string) $this->argument('path');

        if (!is_file($path)) {
            $this->error("Ficheiro nao encontrado: {$path}");

            return self::FAILURE;
        }

        try {
            $rows = $this->readRows($path);
            $normalizedRows = $this->normalizeRows($rows);
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        if ($this->option('normalized-output')) {
            $this->writeNormalizedCsv((string) $this->option('normalized-output'), $normalizedRows);
        }

        if ($this->option('dry-run')) {
            $existing = $this->countExisting($normalizedRows);
            $this->printResult(count($normalizedRows), $existing, count($normalizedRows) - $existing, 0, 0, true);

            return self::SUCCESS;
        }

        $stateName = trim((string) $this->option('state')) ?: 'Stand';

        try {
            $result = DB::transaction(function () use ($normalizedRows, $stateName): array {
                $state = $this->resolveGeneralState($stateName);
                $brandCache = $this->loadBrandCache();
                [$activeVehicles, $trashedVehicles] = $this->existingVehicleMaps();

                $created = 0;
                $existing = 0;
                $restored = 0;
                $stateUpdated = 0;

                foreach ($normalizedRows as $row) {
                    if (isset($activeVehicles[$row['license']])) {
                        $vehicle = $activeVehicles[$row['license']];
                        if ((int) $vehicle->general_state_id !== (int) $state->id) {
                            $vehicle->update(['general_state_id' => $state->id]);
                            $stateUpdated++;
                        }

                        $existing++;
                        continue;
                    }

                    if (isset($trashedVehicles[$row['license']])) {
                        $vehicle = $trashedVehicles[$row['license']];
                        $vehicle->restore();
                        $vehicle->update(['general_state_id' => $state->id]);
                        $activeVehicles[$row['license']] = $vehicle;
                        $restored++;
                        $existing++;
                        continue;
                    }

                    $brand = $this->resolveBrand($row['brand'], $brandCache);

                    $vehicle = Vehicle::create(array_filter([
                        'general_state_id' => $state->id,
                        'license' => $row['license'],
                        'brand_id' => $brand->id,
                        'model' => $row['model'],
                        'version' => $row['version'],
                        'year' => $row['year'],
                        'kilometers' => $row['kilometers'],
                        'fuel' => $row['fuel'],
                        'pvp' => $row['pvp'],
                        'minimum_price' => $row['minimum_price'],
                    ], static fn ($value): bool => $value !== null && $value !== ''));

                    $activeVehicles[$row['license']] = $vehicle;
                    $created++;
                }

                return [
                    'total' => count($normalizedRows),
                    'existing' => $existing,
                    'created' => $created,
                    'restored' => $restored,
                    'state_updated' => $stateUpdated,
                    'state' => $state->name,
                ];
            });
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->printResult($result['total'], $result['existing'], $result['created'], $result['restored'], $result['state_updated'], false);
        $this->line('Estado geral: ' . $result['state']);

        return self::SUCCESS;
    }

    private function readRows(string $path): array
    {
        error_reporting(E_ALL & ~E_WARNING & ~E_DEPRECATED);

        $reader = new SpreadsheetReader($path);
        $rows = [];

        $sheets = method_exists($reader, 'Sheets') ? $reader->Sheets() : [null];
        foreach (array_keys($sheets) as $sheetIndex) {
            if (method_exists($reader, 'ChangeSheet')) {
                $reader->ChangeSheet($sheetIndex);
            }

            $headers = null;
            foreach ($reader as $line) {
                $line = array_map(static fn ($value): string => trim((string) $value), $line);
                if ($this->isEmptyLine($line)) {
                    continue;
                }

                if ($headers === null) {
                    $headers = $this->mapHeaders($line);
                    continue;
                }

                $rows[] = $this->rowFromLine($line, $headers);
            }
        }

        return $rows;
    }

    private function normalizeRows(array $rows): array
    {
        $normalized = [];
        $this->normalizationDuplicates = 0;
        $this->normalizationSkipped = 0;

        foreach ($rows as $row) {
            $license = $this->normalizeLicense($row['license'] ?? '');
            $brand = trim((string) ($row['brand'] ?? ''));
            $model = trim((string) ($row['model'] ?? ''));

            if ($license === '' || $brand === '' || $model === '') {
                $this->normalizationSkipped++;
                continue;
            }

            if (isset($normalized[$license])) {
                $this->normalizationDuplicates++;
                continue;
            }

            $normalized[$license] = [
                'license' => $license,
                'brand' => $brand,
                'model' => $model,
                'version' => trim((string) ($row['version'] ?? '')),
                'year' => $this->normalizeYear($row['year'] ?? null),
                'kilometers' => $this->normalizeInt($row['kilometers'] ?? null),
                'fuel' => trim((string) ($row['fuel'] ?? '')),
                'pvp' => $this->normalizeDecimal($row['pvp'] ?? null),
                'minimum_price' => $this->normalizeDecimal($row['minimum_price'] ?? null),
            ];
        }

        if ($this->normalizationSkipped > 0) {
            $this->warn("Linhas ignoradas por falta de matricula, marca ou modelo: {$this->normalizationSkipped}");
        }

        if ($this->normalizationDuplicates > 0) {
            $this->warn("Matriculas duplicadas no ficheiro: {$this->normalizationDuplicates}");
        }

        return array_values($normalized);
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
            $key = $this->normalizeHeader($header);
            if (isset($aliasMap[$key])) {
                $mapped[$aliasMap[$key]] = $index;
            }
        }

        foreach (['license', 'brand', 'model'] as $required) {
            if (!isset($mapped[$required])) {
                throw new RuntimeException("Ficheiro sem coluna obrigatoria: {$required}");
            }
        }

        return $mapped;
    }

    private function rowFromLine(array $line, array $headers): array
    {
        $row = [];
        foreach ($headers as $field => $index) {
            $row[$field] = $line[$index] ?? '';
        }

        return $row;
    }

    private function resolveGeneralState(string $name): GeneralState
    {
        $existing = GeneralState::withTrashed()->get()->first(function (GeneralState $state) use ($name): bool {
            return $this->normalizeHeader($state->name) === $this->normalizeHeader($name);
        });

        if ($existing) {
            if ($existing->trashed()) {
                $existing->restore();
            }

            return $existing;
        }

        return GeneralState::create(['name' => $name]);
    }

    private function loadBrandCache(): array
    {
        $cache = [];
        foreach (Brand::withTrashed()->get() as $brand) {
            $cache[$this->normalizeHeader($brand->name)] = $brand;
        }

        return $cache;
    }

    private function resolveBrand(string $name, array &$cache): Brand
    {
        $key = $this->normalizeHeader($name);

        if (isset($cache[$key])) {
            if ($cache[$key]->trashed()) {
                $cache[$key]->restore();
            }

            return $cache[$key];
        }

        $brand = Brand::create(['name' => trim($name)]);
        $cache[$key] = $brand;

        return $brand;
    }

    private function existingVehicleMaps(): array
    {
        $active = [];
        $trashed = [];

        foreach (Vehicle::withTrashed()->get(['id', 'license', 'general_state_id', 'deleted_at']) as $vehicle) {
            $normalized = $this->normalizeLicense((string) $vehicle->license);
            if ($normalized !== '') {
                if ($vehicle->trashed()) {
                    $trashed[$normalized] ??= $vehicle;
                } else {
                    $active[$normalized] = $vehicle;
                }
            }
        }

        foreach (array_keys($active) as $license) {
            unset($trashed[$license]);
        }

        return [$active, $trashed];
    }

    private function existingLicenseMap(): array
    {
        [$active, $trashed] = $this->existingVehicleMaps();

        return array_fill_keys(array_unique(array_merge(array_keys($active), array_keys($trashed))), true);
    }

    private function countExisting(array $rows): int
    {
        $existing = $this->existingLicenseMap();
        $count = 0;

        foreach ($rows as $row) {
            if (isset($existing[$row['license']])) {
                $count++;
            }
        }

        return $count;
    }

    private function writeNormalizedCsv(string $path, array $rows): void
    {
        $handle = fopen($path, 'wb');
        if ($handle === false) {
            throw new RuntimeException("Nao foi possivel escrever CSV normalizado: {$path}");
        }

        fputcsv($handle, ['license', 'brand', 'model', 'version', 'year', 'kilometers', 'fuel', 'pvp', 'minimum_price']);
        foreach ($rows as $row) {
            fputcsv($handle, $row);
        }

        fclose($handle);
    }

    private function printResult(int $total, int $existing, int $created, int $restored, int $stateUpdated, bool $dryRun): void
    {
        $this->info($dryRun ? 'Validacao concluida.' : 'Importacao concluida.');
        $this->line('Normalizadas: ' . $total);
        $this->line('Existentes: ' . $existing);
        $this->line('Criadas: ' . $created);
        $this->line('Restauradas: ' . $restored);
        $this->line('Estados atualizados: ' . $stateUpdated);
        $this->line('Ignoradas: ' . $this->normalizationSkipped);
        $this->line('Duplicadas no ficheiro: ' . $this->normalizationDuplicates);
        $this->line('Removidas: 0');
    }

    private function normalizeHeader(string $value): string
    {
        $value = Str::ascii(trim($value));
        $value = Str::lower($value);

        return preg_replace('/[^a-z0-9]+/', '', $value) ?? '';
    }

    private function normalizeLicense(string $value): string
    {
        $value = Str::upper(trim($value));

        return preg_replace('/[\s-]+/', '', $value) ?? '';
    }

    private function normalizeYear($value): ?int
    {
        $year = $this->normalizeInt($value);

        return $year !== null && $year >= 1900 && $year <= 2100 ? $year : null;
    }

    private function normalizeInt($value): ?int
    {
        $value = preg_replace('/[\s.,]/', '', (string) $value) ?? '';

        return preg_match('/^-?\d+$/', $value) ? (int) $value : null;
    }

    private function normalizeDecimal($value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        $value = str_replace(' ', '', $value);
        if (str_contains($value, ',') && str_contains($value, '.')) {
            $value = str_replace('.', '', $value);
            $value = str_replace(',', '.', $value);
        } elseif (str_contains($value, ',')) {
            $value = str_replace(',', '.', $value);
        }

        $value = preg_replace('/[^0-9.-]/', '', $value) ?? '';

        return preg_match('/^-?\d+(\.\d+)?$/', $value) ? $value : null;
    }

    private function isEmptyLine(array $line): bool
    {
        foreach ($line as $value) {
            if (trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }
}
