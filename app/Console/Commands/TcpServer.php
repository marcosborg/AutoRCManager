<?php

namespace App\Console\Commands;

use App\Models\VehiclePosition;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class TcpServer extends Command
{
    protected $signature = 'gps:serve {--port= : Porta TCP para escutar (sobrepoe .env)}';

    protected $description = 'Inicia um servidor TCP para receber dados GPS dos trackers';

    public function handle(): int
    {
        $host = env('GPS_SERVER_HOST', '0.0.0.0');
        $portOption = $this->option('port');
        $port = $portOption !== null && $portOption !== '' ? (int) $portOption : (int) env('GPS_SERVER_PORT', 5000);

        if ($port <= 0) {
            $this->error('Porta invalida para o servidor GPS.');

            return self::FAILURE;
        }

        if (!function_exists('socket_create')) {
            $this->error('Extensao sockets nao disponivel no PHP atual. Ative a extensao sockets para usar este comando.');
            Log::channel('gps')->error('Extensao sockets nao disponivel; servidor TCP nao pode iniciar.');

            return self::FAILURE;
        }

        $logger = Log::channel('gps');

        // Evita processos zombie quando os filhos terminam, se pcntl estiver disponivel.
        if (function_exists('pcntl_signal')) {
            pcntl_signal(SIGCHLD, SIG_IGN);
        } else {
            $logger->warning('pcntl_signal nao disponivel; evitar zombies nao e possivel neste ambiente.');
        }

        $serverSocket = @socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        if ($serverSocket === false) {
            $message = socket_strerror(socket_last_error());
            $logger->error('Falha ao criar socket do servidor.', ['error' => $message]);
            $this->error("Nao foi possivel criar o socket: {$message}");

            return self::FAILURE;
        }

        socket_set_option($serverSocket, SOL_SOCKET, SO_REUSEADDR, 1);
        socket_set_nonblock($serverSocket);

        if (@socket_bind($serverSocket, $host, $port) === false) {
            $message = socket_strerror(socket_last_error($serverSocket));
            $logger->error('Falha no bind do socket.', ['host' => $host, 'port' => $port, 'error' => $message]);
            $this->error("Nao foi possivel fazer bind ao porto: {$message}");

            return self::FAILURE;
        }

        if (@socket_listen($serverSocket, 10) === false) {
            $message = socket_strerror(socket_last_error($serverSocket));
            $logger->error('Falha no listen do socket.', ['host' => $host, 'port' => $port, 'error' => $message]);
            $this->error("Nao foi possivel colocar o socket em escuta: {$message}");

            return self::FAILURE;
        }

        $logger->info('Servidor GPS iniciado e em escuta.', ['host' => $host, 'port' => $port]);
        $this->info(sprintf('Servidor GPS a escutar em %s:%s', $host, $port));

        // Loop infinito de aceitacao de novas ligacoes.
        while (true) {
            $clientSocket = @socket_accept($serverSocket);

            if ($clientSocket === false) {
                // Pequena pausa para evitar busy-wait quando nao ha novas ligacoes.
                usleep(100000);
                continue;
            }

            // Fork se suportado; caso contrario trata no processo principal.
            if (!function_exists('pcntl_fork')) {
                $logger->warning('pcntl_fork nao disponivel; a ligacao sera tratada no processo principal.', [
                    'endereco' => $peerAddress ?? null,
                    'porta' => $peerPort ?? null,
                ]);
                $this->handleClient($clientSocket);
                socket_close($clientSocket);
                continue;
            }

            $pid = pcntl_fork();

            if ($pid === -1) {
                $logger->error('Falha ao fazer fork para nova ligacao.');
                socket_close($clientSocket);
                usleep(100000);
                continue;
            }

            if ($pid === 0) {
                // Processo filho: trata a ligacao e termina.
                socket_close($serverSocket);
                $this->handleClient($clientSocket);
                socket_close($clientSocket);

                return 0;
            }

            // Processo pai: fecha a copia do socket do cliente e continua a aceitar.
            socket_close($clientSocket);
        }
    }

    /**
     * Le dados de uma ligacao e processa pacotes completos.
     */
    private function handleClient($clientSocket): void
    {
        $logger = Log::channel('gps');

        $peerAddress = null;
        $peerPort = null;
        @socket_getpeername($clientSocket, $peerAddress, $peerPort);

        $logger->info('Nova conexao recebida.', ['endereco' => $peerAddress, 'porta' => $peerPort]);

        $buffer = '';

        while (true) {
            $data = @socket_read($clientSocket, 2048, PHP_BINARY_READ);

            if ($data === false) {
                $errorCode = socket_last_error($clientSocket);
                $errorMessage = socket_strerror($errorCode);

                // Erros nao criticos (ex.: EWOULDBLOCK) apenas esperam mais dados.
                if (!in_array($errorCode, [SOCKET_EAGAIN, SOCKET_EWOULDBLOCK], true)) {
                    $logger->error('Erro ao ler do socket do cliente.', ['erro' => $errorMessage]);
                    break;
                }

                usleep(50000);
                continue;
            }

            // Ligacao terminada pelo cliente.
            if ($data === '' || $data === "\0") {
                break;
            }

            $buffer .= $data;

            // Processa pacotes completos delimitados por ; ou quebras de linha.
            while (preg_match('/[;\\r\\n]/', $buffer, $matches, PREG_OFFSET_CAPTURE)) {
                $delimiterPosition = $matches[0][1];
                $packet = substr($buffer, 0, $delimiterPosition + 1);
                $buffer = substr($buffer, $delimiterPosition + 1);

                $packet = trim($packet);
                if ($packet === '') {
                    continue;
                }

                try {
                    $this->processPacket($packet);
                } catch (Throwable $exception) {
                    $logger->error('Erro inesperado ao processar pacote.', [
                        'erro' => $exception->getMessage(),
                        'raw' => $packet,
                    ]);
                }
            }
        }

        $logger->info('Conexao encerrada.', ['endereco' => $peerAddress, 'porta' => $peerPort]);
    }

    /**
     * Faz o parse do pacote e grava a posicao.
     */
    private function processPacket(string $rawData): void
    {
        $logger = Log::channel('gps');
        $logger->info('Pacote recebido.', ['raw' => $rawData]);

        $rawTrimmed = trim($rawData);
        $clean = rtrim($rawTrimmed, ';');
        $clean = rtrim($clean, ',');

        if ($clean === '') {
            $logger->warning('Pacote vazio apos limpeza.', ['raw' => $rawData]);

            return;
        }

        $parts = explode(',', $clean);

        $first = $parts[0] ?? '';
        if (!Str::startsWith(Str::lower($first), 'imei:')) {
            $logger->warning('Pacote ignorado por nao conter IMEI.', ['raw' => $rawData]);

            return;
        }

        $trackerId = trim(substr($first, 5));
        if ($trackerId === '') {
            $logger->warning('IMEI vazio no pacote.', ['raw' => $rawData]);

            return;
        }

        $fixIndex = null;
        $fixValid = false;

        foreach ($parts as $index => $part) {
            if ($part === 'A' || $part === 'V') {
                $fixIndex = $index;
                $fixValid = $part === 'A';
                break;
            }
        }

        if ($fixIndex === null) {
            $logger->warning('Pacote sem indicacao de fix (A/V).', ['raw' => $rawData]);

            return;
        }

        $latitude = $this->parseCoordinate($parts[$fixIndex + 1] ?? null, $parts[$fixIndex + 2] ?? null);
        $longitude = $this->parseCoordinate($parts[$fixIndex + 3] ?? null, $parts[$fixIndex + 4] ?? null);
        $speedKph = $this->convertSpeedKph($parts[$fixIndex + 5] ?? null);

        if ($latitude === null || $longitude === null) {
            $logger->warning('Coordenadas invalidas ou ausentes.', ['raw' => $rawData]);

            return;
        }

        $reportedAt = $this->extractTimestamp($parts);
        $voltage = $this->extractVoltage($parts, $fixIndex + 6);

        VehiclePosition::create([
            'tracker_id'  => $trackerId,
            'latitude'    => $latitude,
            'longitude'   => $longitude,
            'speed_kph'   => $speedKph,
            'fix_valid'   => $fixValid,
            'voltage'     => $voltage,
            'reported_at' => $reportedAt,
            'raw_data'    => $rawTrimmed,
        ]);

        $logger->info('Posicao gravada com sucesso.', [
            'tracker_id' => $trackerId,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'speed_kph' => $speedKph,
            'fix_valid' => $fixValid,
            'reported_at' => $reportedAt,
            'voltage' => $voltage,
        ]);
    }

    /**
     * Converte coordenadas do formato ddmm.mmmm para graus decimais.
     */
    private function parseCoordinate(?string $value, ?string $hemisphere): ?float
    {
        if ($value === null || $hemisphere === null) {
            return null;
        }

        $value = trim($value);
        if ($value === '' || !is_numeric($value)) {
            return null;
        }

        $numeric = (float) $value;
        $degrees = floor($numeric / 100);
        $minutes = fmod($numeric, 100);

        $decimal = $degrees + ($minutes / 60);

        $hemisphere = strtoupper(trim($hemisphere));
        if ($hemisphere === 'S' || $hemisphere === 'W') {
            $decimal *= -1;
        }

        return round($decimal, 6);
    }

    /**
     * Converte velocidade de nos para km/h.
     */
    private function convertSpeedKph(?string $knots): int
    {
        if ($knots === null || !is_numeric($knots)) {
            return 0;
        }

        $speed = (float) $knots * 1.852;

        return (int) round($speed);
    }

    /**
     * Extrai data/hora no formato yymmddhhmmss; devolve now() se nao existir.
     */
    private function extractTimestamp(array $parts): Carbon
    {
        foreach ($parts as $part) {
            $candidate = trim($part);
            if (preg_match('/^\\d{12}$/', $candidate) === 1) {
                try {
                    return Carbon::createFromFormat('ymdHis', $candidate, 'UTC');
                } catch (Throwable $exception) {
                    // Ignora e tenta proximo candidato.
                }
            }
        }

        return Carbon::now('UTC');
    }

    /**
     * Extrai tensao da bateria por heuristica.
     */
    private function extractVoltage(array $parts, int $startIndex): ?float
    {
        foreach ($parts as $part) {
            if (stripos($part, 'battery') !== false || stripos($part, 'volt') !== false) {
                if (preg_match('/([0-9]+(?:\\.[0-9]+)?)/', $part, $matches) === 1) {
                    return round((float) $matches[1], 2);
                }
            }
        }

        $total = count($parts);
        for ($i = $startIndex; $i < $total; $i++) {
            $candidate = trim($parts[$i]);

            if ($candidate === '') {
                continue;
            }

            if (!is_numeric($candidate) && preg_match('/([0-9]+(?:\\.[0-9]+)?)/', $candidate, $matches) === 1) {
                $candidate = $matches[1];
            }

            if (!is_numeric($candidate)) {
                continue;
            }

            $value = (float) $candidate;
            if ($value >= 3.0 && $value <= 30.0) {
                return round($value, 2);
            }
        }

        return null;
    }
}
