# Sprint E Hardening

## Explicit domain constants

- `App\Domain\Consignments\ConsignmentStatus`
  - ACTIVE, CLOSED
- `App\Domain\Ownership\OwnershipType`
  - GROUP, EXTERNAL
- `App\Domain\Repairs\RepairStatus`
  - CLOSED_ID = 3 (maps to existing repair_state_id)
- `App\Domain\Finance\AccountDepartments`
  - ACQUISITION = 1, GARAGE = 2, REVENUE = 3

## Centralized rules

- `App\Domain\Consignments\ConsignmentRules`
  - Active consignment detection
  - Sale blocking decision
- `App\Domain\Repairs\RepairRules`
  - Open repair detection

## Validations reinforced

- Consignment close checks now prevent overlap via edited end date.
- Sale block is reused via ConsignmentRules (no duplicate logic).
- Repair open check uses RepairRules with explicit closed mapping.

## Database hardening

- No new indexes or check constraints added in this sprint to avoid risk on
  existing environments. Candidates remain: composite index on
  `vehicle_consignments(vehicle_id, starts_at, ends_at)` and
  `account_operations(vehicle_id, date)`.

## Tests

- Service tests were not added because core tables (vehicles, account operations,
  etc.) do not have migrations in this repo, which would make test database
  setup unreliable without creating schema in tests.

## Residual risks

- Repair closed state still depends on `repair_state_id = 3`.
- Consignment period overlap prevention relies on service usage (no DB constraint).
