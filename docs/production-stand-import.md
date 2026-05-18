# Importacao de stock para producao

Este fluxo importa um ficheiro de stock para a base de producao em modo aditivo.
Nao apaga viaturas fora do ficheiro.

## Ficheiro

O ficheiro pode ser CSV, XLS, XLSX ou ODS.

Campos minimos:

- matricula
- marca
- modelo

Campos opcionais lidos quando existirem:

- versao
- ano
- kms
- combustivel
- pvp / preco marcado
- preco minimo

## Validar sem gravar

No Windows/PowerShell, executar a partir da raiz do projeto:

```powershell
$env:DB_PROFILE='production'
php artisan vehicles:import-stand "C:\Users\sara.borges\Desktop\STOCK (2).xlsx" --state=Stand --dry-run
Remove-Item Env:\DB_PROFILE
```

## Backup antes de importar

```powershell
$env:DB_PROFILE='production'
php artisan db:backup-current
Remove-Item Env:\DB_PROFILE
```

O ficheiro fica em `storage/app/backups`, com nome semelhante a:

```text
storage/app/backups/manage_autorcpe_manager_back-YYYYMMDD_HHMMSS.sql
```

## Importar em producao

```powershell
$env:DB_PROFILE='production'
php artisan vehicles:import-stand "C:\Users\sara.borges\Desktop\STOCK (2).xlsx" --state=Stand --normalized-output="storage/app/vehicles-stand-normalized.csv"
Remove-Item Env:\DB_PROFILE
```

O comando:

- cria o estado `Stand` se nao existir;
- cria marcas inexistentes;
- normaliza matriculas removendo espacos e hifens;
- cria viaturas novas;
- muda viaturas existentes para `Stand`;
- restaura viaturas soft-deleted do ficheiro quando nao existe uma ativa com a mesma matricula;
- nao remove viaturas que nao estejam no ficheiro.

## Restaurar backup

Usar o caminho exato do backup criado antes da importacao:

```powershell
$env:DB_PROFILE='production'
php artisan db:restore-backup "E:\websites\autorc\storage\app\backups\manage_autorcpe_manager_back-YYYYMMDD_HHMMSS.sql" --force
Remove-Item Env:\DB_PROFILE
```

O restauro recria a base configurada para `DB_PROFILE=production` e importa o dump.

## Backup desta execucao

Antes da importacao de 2026-05-18 foi criado este backup:

```text
E:\websites\autorc\storage\app\backups\manage_autorcpe_manager_back-20260518_182513.sql
```

Comando de restauro direto para este backup:

```powershell
$env:DB_PROFILE='production'
php artisan db:restore-backup "E:\websites\autorc\storage\app\backups\manage_autorcpe_manager_back-20260518_182513.sql" --force
Remove-Item Env:\DB_PROFILE
```
