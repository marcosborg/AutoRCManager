<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use RuntimeException;
use Symfony\Component\Process\Process;
use Throwable;

class BackupCurrentDatabase extends Command
{
    protected $signature = 'db:backup-current
                            {--connection=mysql : Ligacao de base de dados a usar}
                            {--output= : Caminho do ficheiro .sql; por defeito usa storage/app/backups}
                            {--dump-bin= : Caminho para o binario mysqldump}';

    protected $description = 'Cria um dump SQL da base de dados atualmente configurada.';

    public function handle(): int
    {
        $connectionName = (string) $this->option('connection');
        $config = config("database.connections.{$connectionName}");

        if (!$this->validateConfig($config, $connectionName)) {
            return self::FAILURE;
        }

        $output = $this->option('output') ?: $this->defaultOutputPath($config['database']);
        $output = (string) $output;
        $dir = dirname($output);

        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            $this->error("Nao foi possivel criar a pasta de backups: {$dir}");

            return self::FAILURE;
        }

        $dumpBinary = $this->resolveBinary('mysqldump', $this->option('dump-bin'), env('MYSQL_DUMP_BIN'));

        try {
            $this->dump($config, $output, $dumpBinary);
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info('Backup criado com sucesso.');
        $this->line('Ficheiro: ' . $output);

        return self::SUCCESS;
    }

    private function validateConfig(?array $config, string $connectionName): bool
    {
        if ($config === null) {
            $this->error("Ligacao nao encontrada: {$connectionName}");

            return false;
        }

        foreach (['host', 'port', 'database', 'username'] as $key) {
            if (!isset($config[$key]) || $config[$key] === '') {
                $this->error("Configuracao incompleta para {$connectionName}: falta {$key}.");

                return false;
            }
        }

        return true;
    }

    private function defaultOutputPath(string $database): string
    {
        $safeDatabase = preg_replace('/[^A-Za-z0-9_.-]+/', '_', $database) ?: 'database';

        return storage_path('app/backups/' . $safeDatabase . '-' . date('Ymd_His') . '.sql');
    }

    private function dump(array $config, string $output, string $dumpBinary): void
    {
        $this->info("A criar backup de `{$config['database']}`...");

        $command = [
            $dumpBinary,
            '--host=' . $config['host'],
            '--port=' . $config['port'],
            '--user=' . $config['username'],
            '--set-gtid-purged=OFF',
            '--single-transaction',
            '--quick',
            '--routines',
            '--events',
            '--triggers',
            $config['database'],
        ];

        $error = $this->runDumpProcess($command, $output, $config['password'] ?? '');
        if ($error === null) {
            return;
        }

        if (stripos($error, 'set-gtid-purged') !== false || stripos($error, 'unknown variable') !== false) {
            $this->warn('mysqldump nao aceita --set-gtid-purged; a tentar sem a flag...');

            $command = array_values(array_filter($command, static fn (string $part): bool => stripos($part, 'set-gtid-purged') === false));
            $error = $this->runDumpProcess($command, $output, $config['password'] ?? '');
        }

        if ($error !== null) {
            throw new RuntimeException('Erro ao criar backup: ' . $error);
        }
    }

    private function runDumpProcess(array $command, string $output, string $password): ?string
    {
        $handle = fopen($output, 'wb');
        if ($handle === false) {
            throw new RuntimeException("Nao foi possivel abrir {$output} para escrita.");
        }

        $process = new Process($command, null, ['MYSQL_PWD' => $password]);
        $process->setTimeout(3600);
        $process->run(function (string $type, string $buffer) use ($handle): void {
            if ($type === Process::OUT) {
                fwrite($handle, $buffer);
            }
        });

        fclose($handle);

        if ($process->isSuccessful()) {
            return null;
        }

        return trim($process->getErrorOutput() ?: $process->getOutput());
    }

    private function resolveBinary(string $default, ?string $option, ?string $envValue): string
    {
        foreach ([$option, $envValue, $default] as $value) {
            if (!$value) {
                continue;
            }

            $hasPath = str_contains($value, '/') || str_contains($value, '\\');
            if (!$hasPath || file_exists($value)) {
                return $value;
            }
        }

        throw new RuntimeException("Binario {$default} nao encontrado.");
    }
}
