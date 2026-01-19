# Sprint D Reports - Operational Units

## Concept of responsibility

An operational unit is responsible for a vehicle when there is an internal
consignment active for that unit, within the interval [starts_at, ends_at].
Vehicles without consignments are not included in unit reports.

## Imputation rules

- Each AccountOperation is assigned to the unit whose consignment covers
  the operation date.
- Department 3 is treated as revenue; other departments are treated as cost.
- Negative values are always treated as cost.
- No duplication or manual adjustments.

## How aggregation works

1) Collect consignments that overlap the requested date interval.
2) For each operation in the interval, find the consignment covering its date.
3) Aggregate cost/revenue per destination unit.

## Limitations

- If no date range is provided, the report defaults to current month to today.
- Only operations inside a consignment period are counted.
- Operations without consignment coverage are excluded.
- Totals are informational (no taxes, depreciation, or accounting adjustments).

## Manual checklist

1) Create a consignment for a unit.
2) Register costs/revenues during the consignment window.
3) Verify the unit report aggregates correctly.
4) Confirm events outside consignments do not appear.
