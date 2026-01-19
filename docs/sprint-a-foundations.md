# Sprint A Foundations

This sprint adds foundational tables that separate operational units, ownership,
locations, and internal consignments without changing existing behavior.

## Why these tables exist

- `operational_units` defines internal units (Salvados, Oficina, Stand, Rent-a-Car)
  as first-class records instead of implicit states.
- `vehicle_ownerships` keeps ownership history separate from location, so a
  vehicle can move without changing ownership.
- `vehicle_locations` tracks where a vehicle is operationally assigned over
  time, independent of ownership and `general_state_id`.
- `vehicle_consignments` records internal, temporary assignments between units,
  preserving reference value and a full history.

## How they relate to Vehicle

- A vehicle has many ownerships, locations, and consignments.
- None of these tables replace existing fields like `client_id` or
  `general_state_id`; they add historical layers for future logic.

## Future problems they solve

- Clear separation between ownership, location, and operational status.
- Auditable timelines per vehicle, including internal consignments.
- Foundation for cost attribution and reporting by unit without internal billing.
