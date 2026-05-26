# Encomendas de Peças

O módulo de encomendas de peças é independente do lançamento manual de peças usadas nas reparações (`repair_parts`).

- `repair_parts`: material/serviços efetivamente usados na intervenção, lançado manualmente na app ou backoffice.
- `part_orders`: pedidos, cotações, pagamentos, receções e follow-up de encomendas.

Uma encomenda pode estar associada a uma reparação, a uma viatura ou ficar sem viatura. Quando é associada a uma reparação, a viatura é derivada automaticamente da reparação.

## Alertas

Comandos diários:

```bash
php artisan part-orders:check-delays
php artisan part-payments:check-overdue
```

O primeiro marca encomendas atrasadas. O segundo marca pagamentos vencidos.
