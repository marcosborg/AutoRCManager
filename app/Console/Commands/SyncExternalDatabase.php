<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use PDO;
use RuntimeException;
use Symfony\Component\Process\Process;
use Throwable;

class SyncExternalDatabase extends Command
{
    protected $signature = 'db:sync-external
                            {--force : Executa sem pedir confirmacao}
                            {--keep-dump : Mantem o ficheiro de dump gerado para analise}
                            {--dump-bin= : Caminho para o binario mysqldump (opcional)}
                            {--mysql-bin= : Caminho para o binario mysql (opcional)}';

    protected $description = 'Recria a base interna com uma copia integral da base externa (production -> sandbox).';

    public function handle(): int
    {
        $external = config('database.connections.mysql_external');
        $internal = config('database.connections.mysql_internal');

        if (!$this->validateConfig($external, 'externa') || !$this->validateConfig($internal, 'interna')) {
            return self::FAILURE;
        }

        $externalName = $external['database'] ?? 'desconhecida';
        $internalName = $internal['database'] ?? 'desconhecida';

        if (!$this->option('force') && !$this->confirm(
            "Isto vai eliminar a base interna `{$internalName}` e substitui-la por uma copia de `{$externalName}`. Continuar?"
        )) {
            $this->info('Operacao cancelada.');

            return self::SUCCESS;
        }

        $dumpPath = storage_path('app/db-sync-external-' . date('Ymd_His') . '.sql');

        $dumpBinary = $this->resolveBinary('mysqldump', $this->option('dump-bin'), env('MYSQL_DUMP_BIN'));
        $mysqlBinary = $this->resolveBinary('mysql', $this->option('mysql-bin'), env('MYSQL_BIN'));

        try {
            $this->dropAndCreateInternal($internal);
            $this->exportExternal($external, $dumpPath, $dumpBinary);
            $this->importIntoInternal($internal, $dumpPath, $mysqlBinary);
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        } finally {
            if (!$this->option('keep-dump') && file_exists($dumpPath)) {
                @unlink($dumpPath);
            }
        }

        $this->info('BD interna atualizada com sucesso.');

        return self::SUCCESS;
    }

    private function validateConfig(?array $config, string $label): bool
    {
        $required = ['host', 'port', 'database', 'username'];

        foreach ($required as $key) {
            if (!isset($config[$key]) || $config[$key] === '') {
                $this->error("Configuracao {$label} incompleta: falta {$key}.");

                return false;
            }
        }

        return true;
    }

    private function dropAndCreateInternal(array $config): void
    {
        $dbName = $config['database'];
        $charset = $config['charset'] ?? 'utf8mb4';
        $collation = $config['collation'] ?? 'utf8mb4_unicode_ci';

        $this->warn("A recriar base interna `{$dbName}`...");

        $pdo = $this->makeAdminPdo($config);

        $pdo->exec('DROP DATABASE IF EXISTS `' . $dbName . '`');
        $pdo->exec(sprintf(
            'CREATE DATABASE `%s` CHARACTER SET %s COLLATE %s',
            $dbName,
            $charset,
            $collation
        ));
    }

    private function exportExternal(array $config, string $dumpPath, string $dumpBinary): void
    {
        $this->info('A gerar dump da base externa...');

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
            '--add-drop-table',
            $config['database'],
        ];

        $error = $this->runDumpProcess($command, $dumpPath, $config['password'] ?? '');
        if ($error === null) {
            return;
        }

        if (stripos($error, 'set-gtid-purged') !== false || stripos($error, 'unknown variable') !== false) {
            $this->warn('mysqldump nao aceita --set-gtid-purged; a tentar sem a flag...');

            $command = array_values(array_filter($command, static fn (string $part): bool => stripos($part, 'set-gtid-purged') === false));
            $error = $this->runDumpProcess($command, $dumpPath, $config['password'] ?? '');
        }

        if ($error !== null) {
            throw new RuntimeException('Erro ao gerar dump da base externa: ' . $error);
        }
    }

    private function importIntoInternal(array $config, string $dumpPath, string $mysqlBinary): void
    {
        $this->info('A importar dump na base interna...');

        if (!file_exists($dumpPath)) {
            throw new RuntimeException("Ficheiro de dump nao encontrado: {$dumpPath}");
        }

        $input = fopen($dumpPath, 'r');
        if ($input === false) {
            throw new RuntimeException("Nao foi possivel abrir {$dumpPath} para leitura.");
        }

        $command = [
            $mysqlBinary,
            '--host=' . $config['host'],
            '--port=' . $config['port'],
            '--user=' . $config['username'],
            $config['database'],
        ];

        $process = new Process($command, null, ['MYSQL_PWD' => $config['password'] ?? ''], $input, 3600);
        $process->run();

        fclose($input);

        if (!$process->isSuccessful()) {
            $message = trim($process->getErrorOutput() ?: $process->getOutput());
            throw new RuntimeException('Erro ao importar dump na base interna: ' . $message);
        }
    }

    private function makeAdminPdo(array $config): PDO
    {
        $dsn = sprintf('mysql:host=%s;port=%s', $config['host'], $config['port']);

        try {
            return new PDO($dsn, $config['username'], $config['password'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]);
        } catch (Throwable $exception) {
            throw new RuntimeException(
                'Falha ao ligar a base interna para recriacao: ' . $exception->getMessage(),
                0,
                $exception
            );
        }
    }

    private function resolveBinary(string $default, ?string $option, ?string $envValue): string
    {
        $candidates = [];
        if ($option) {
            $candidates[] = ['value' => $option, 'source' => 'option'];
        }
        if ($envValue) {
            $candidates[] = ['value' => $envValue, 'source' => 'env'];
        }
        $candidates[] = ['value' => $default, 'source' => 'default'];

        foreach ($candidates as $candidate) {
            $value = $candidate['value'];
            $hasPath = str_contains($value, '/') || str_contains($value, '\\');

            if ($hasPath) {
                if (file_exists($value)) {
                    return $value;
                }

                if ($candidate['source'] === 'option') {
                    throw new RuntimeException("Binario {$value} nao encontrado. Defina o caminho correto.");
                }

                continue;
            }

            return $value;
        }

        throw new RuntimeException("Binario {$default} nao encontrado. Defina o caminho correto.");
    }

    /**
     * Executa mysqldump e grava no caminho indicado. Devolve null em sucesso ou mensagem de erro em caso de falha.
     */
    private function runDumpProcess(array $command, string $dumpPath, string $password): ?string
    {
        $handle = fopen($dumpPath, 'w+');
        if ($handle === false) {
            throw new RuntimeException("Nao foi possivel abrir {$dumpPath} para escrita.");
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
}
