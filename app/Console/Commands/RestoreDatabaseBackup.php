<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use PDO;
use RuntimeException;
use Symfony\Component\Process\Process;
use Throwable;

class RestoreDatabaseBackup extends Command
{
    protected $signature = 'db:restore-backup
                            {path : Caminho para o ficheiro .sql}
                            {--connection=mysql : Ligacao de base de dados a usar}
                            {--force : Executa sem pedir confirmacao}
                            {--mysql-bin= : Caminho para o binario mysql}';

    protected $description = 'Restaura um dump SQL para a base de dados atualmente configurada.';

    public function handle(): int
    {
        $path = (string) $this->argument('path');
        if (!is_file($path)) {
            $this->error("Backup nao encontrado: {$path}");

            return self::FAILURE;
        }

        $connectionName = (string) $this->option('connection');
        $config = config("database.connections.{$connectionName}");
        if (!$this->validateConfig($config, $connectionName)) {
            return self::FAILURE;
        }

        $database = $config['database'];
        if (!$this->option('force') && !$this->confirm("Isto vai substituir a base `{$database}` pelo backup `{$path}`. Continuar?")) {
            $this->info('Operacao cancelada.');

            return self::SUCCESS;
        }

        $mysqlBinary = $this->resolveBinary('mysql', $this->option('mysql-bin'), env('MYSQL_BIN'));

        try {
            $this->dropAndCreate($config);
            $this->import($config, $path, $mysqlBinary);
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info('Backup restaurado com sucesso.');

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

    private function dropAndCreate(array $config): void
    {
        $database = $config['database'];
        $charset = $config['charset'] ?? 'utf8mb4';
        $collation = $config['collation'] ?? 'utf8mb4_unicode_ci';

        $this->warn("A recriar base `{$database}`...");

        $pdo = new PDO(
            sprintf('mysql:host=%s;port=%s', $config['host'], $config['port']),
            $config['username'],
            $config['password'] ?? '',
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );

        $pdo->exec('DROP DATABASE IF EXISTS `' . str_replace('`', '``', $database) . '`');
        $pdo->exec(sprintf(
            'CREATE DATABASE `%s` CHARACTER SET %s COLLATE %s',
            str_replace('`', '``', $database),
            $charset,
            $collation
        ));
    }

    private function import(array $config, string $path, string $mysqlBinary): void
    {
        $this->info("A importar backup para `{$config['database']}`...");

        $input = fopen($path, 'rb');
        if ($input === false) {
            throw new RuntimeException("Nao foi possivel abrir {$path} para leitura.");
        }

        $process = new Process([
            $mysqlBinary,
            '--host=' . $config['host'],
            '--port=' . $config['port'],
            '--user=' . $config['username'],
            $config['database'],
        ], null, ['MYSQL_PWD' => $config['password'] ?? ''], $input, 3600);

        $process->run();
        fclose($input);

        if (!$process->isSuccessful()) {
            throw new RuntimeException('Erro ao restaurar backup: ' . trim($process->getErrorOutput() ?: $process->getOutput()));
        }
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
