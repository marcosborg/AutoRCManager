# Sprint C Timeline

## Concept

Timeline is a read-only, chronological list of operational and financial events
for a single vehicle. Events are aggregated in memory and never persisted.

## Event sources

- State changes: VehicleStateTransfer (created_at).
- Consignments: VehicleConsignment (starts_at / ends_at).
- Repairs: Repair (timestamp or created_at, status in description).
- Financial costs/revenues:
  - AccountOperation (department 3 = revenue, others = cost).
  - VehicleFinancialEntry (entry_type).

## Limitations

- VehicleLocation is not shown to avoid redundancy with consignments.
- Repairs do not have a close timestamp, so only a start point is shown.
- Totals are informational only (no taxes, no depreciation).

## Manual checklist

1) Open a vehicle and access Timeline.
2) If no events exist, verify the empty state.
3) Create a consignment and verify it appears.
4) Open/close a repair and verify it appears.
5) Add a cost and verify it appears as negative.
6) Add a revenue and verify it appears as positive.
7) Confirm chronological ordering.
