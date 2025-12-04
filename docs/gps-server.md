# Servidor TCP de GPS

Variaveis de ambiente:

```
GPS_SERVER_HOST=0.0.0.0
GPS_SERVER_PORT=5000
```

Arrancar o servidor:

```
php artisan gps:serve
php artisan gps:serve --port=5050
```

Exemplo de configuracao Supervisor (`/etc/supervisor/conf.d/gps-server.conf`):

```
[program:gps-server]
process_name=%(program_name)s
command=/usr/bin/php /var/www/autorc/artisan gps:serve --port=5000
autostart=true
autorestart=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/var/www/autorc/storage/logs/gps-server.log
stopwaitsecs=10
```

Teste rapido com `nc`/`telnet` enviando um pacote de exemplo:

```
printf "imei:7026246067,tracker,201223064947,,F,064947.000,A,4124.5028,N,00210.1212,W,0.00,;\r\n" | nc 127.0.0.1 5000
```
