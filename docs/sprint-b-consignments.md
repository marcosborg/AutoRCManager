# Sprint B Consignacoes Operacionais

## Regras de negocio implementadas

- Consignacao ativa so pode ter estado `active` ou `closed`.
- Nao permite consignacao ativa se ja existir outra ativa para a viatura.
- Nao permite sobreposicao de datas entre consignacoes (permite encostar em `ends_at == starts_at`).
- Ao criar consignacao, cria localizacao operacional na unidade de destino.
- Ao criar consignacao, encerra a localizacao ativa anterior (sem apagar historico).
- Ao encerrar consignacao, encerra a localizacao correspondente.
- Bloqueia venda (definida por `sale_date`) quando existe consignacao ativa.
- Bloqueia encerramento de consignacao se existir reparacao aberta.
  - Considera "aberta" qualquer reparacao com `repair_state_id != 3`.

## Pontos do sistema com validacoes

- `app/Http/Requests/StoreVehicleConsignmentRequest.php`
  - valida estado da consignacao e formatos de data.
- `app/Http/Requests/UpdateVehicleConsignmentRequest.php`
  - valida estado e data de encerramento.
- `app/Services/VehicleConsignmentService.php`
  - regras de sobreposicao, localizacao ativa e bloqueio com reparacao.
- `app/Http/Requests/UpdateVehicleRequest.php`
  - bloqueio de venda quando existe consignacao ativa (via `sale_date`).

## Como migrar e criar dados base

```
php artisan migrate
php artisan db:seed --class=OperationalUnitSeeder
```

## Checklist manual (Sprint B)

1) Criar consignacao com viatura e unidades validas.
2) Tentar criar segunda consignacao ativa para a mesma viatura (deve falhar).
3) Encerrar consignacao com `ends_at` valido.
4) Tentar encerrar consignacao com `ends_at` anterior a `starts_at` (deve falhar).
5) Criar consignacao e confirmar criacao de `vehicle_locations`.
6) Encerrar consignacao e confirmar fecho da `vehicle_locations`.
7) Tentar encerrar consignacao com reparacao aberta (deve falhar).
8) Tentar definir `sale_date` com consignacao ativa (deve falhar).
