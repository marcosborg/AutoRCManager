# Sincronizar BD externa para interna

Comando Artisan que apaga a base interna (sandbox) e recria-a com uma copia integral da base externa (production).

## Comando

```
php artisan db:sync-external
```

- Pergunta confirmacao antes de destruir a base interna. Use `--force` para ignorar o prompt.
- Opcional `--keep-dump` para guardar o ficheiro gerado em `storage/app`.
- Opcional `--dump-bin` e `--mysql-bin` para indicar caminhos dos binarios `mysqldump` e `mysql`.

Exemplo completo no Windows (caminho XAMPP):

```
php artisan db:sync-external --force ^
  --dump-bin="C:\xampp\mysql\bin\mysqldump.exe" ^
  --mysql-bin="C:\xampp\mysql\bin\mysql.exe"
```

## Variaveis de ambiente

```
# Caminhos alternativos para os binarios (evita passar pelas flags)
MYSQL_DUMP_BIN="C:\path\to\mysqldump.exe"
MYSQL_BIN="C:\path\to\mysql.exe"
```

No Windows, use barras normais (`/`) ou escape a barra invertida dupla para evitar erros do dotenv:

```
MYSQL_DUMP_BIN="C:/xampp/mysql/bin/mysqldump.exe"
MYSQL_BIN="C:/xampp/mysql/bin/mysql.exe"
# ou
MYSQL_DUMP_BIN="C:\\xampp\\mysql\\bin\\mysqldump.exe"
MYSQL_BIN="C:\\xampp\\mysql\\bin\\mysql.exe"
```

O comando usa as credenciais das conexoes definidas em `config/database.php`:
- Externa (origem): `DB_*_PRODUCTION`
- Interna (destino): `DB_*_SANDBOX`

Certifique-se de que estes valores estao corretos antes de executar.

## Pre-requisitos

- Binarios `mysqldump` e `mysql` instalados e acessiveis (PATH ou via variaveis/flags).
- Acesso de rede da maquina para a base externa.

## Observacoes

- Operacao destrutiva: a base interna sera eliminada e recriada.
- Mantem charset/collation configurados para a conexao interna.
- O dump temporario e removido ao final, a menos que `--keep-dump` seja usado.
