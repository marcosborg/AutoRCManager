<?php

namespace App\Console\Commands;

use App\Services\VehicleCsvSyncService;
use Illuminate\Console\Command;
use RuntimeException;

class SyncVehiclesFromCsv extends Command
{
    protected $signature = 'vehicles:sync-csv
                            {path : Caminho para o ficheiro CSV}
                            {--delimiter= : Delimitador manual (ex: ; , \\t)}
                            {--general-state-id= : General state a usar em novas viaturas}';

    protected $description = 'Sincroniza a tabela vehicles com um CSV (matricula como identificador unico).';

    public function handle(VehicleCsvSyncService $service): int
    {
        $path = (string) $this->argument('path');
        $delimiter = $this->option('delimiter');
        $generalStateId = $this->option('general-state-id');

        if ($generalStateId !== null && $generalStateId !== '') {
            $generalStateId = (int) $generalStateId;
        } else {
            $generalStateId = null;
        }

        try {
            $result = $service->syncFromCsv($path, $generalStateId, $delimiter ?: null);
        } catch (RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info('Sincronizacao concluida.');
        $this->line('CSV total: ' . $result['csv_total']);
        $this->line('Existentes: ' . $result['existing']);
        $this->line('Criadas: ' . $result['created']);
        $this->line('Removidas: ' . $result['deleted']);
        $this->line('Ignoradas: ' . $result['skipped']);
        $this->line('Duplicadas no CSV: ' . $result['duplicates']);

        return self::SUCCESS;
    }
}
