# Sprint F Exports

## What can be exported

- Vehicle timeline as PDF.
- Operational unit report as CSV.

## Formats

- PDF: generated from Blade using dompdf.
- CSV: generated from OperationalUnitReportService data.

## Limitations

- Values are informational only (no taxes or amortizations).
- Exports reuse existing services and do not change data.

## Usage

- Vehicle timeline PDF:
  - `/admin/vehicles/{vehicle}/timeline/export/pdf`
- Operational unit report CSV:
  - `/admin/reports/operational-units/export?from=DD/MM/YYYY&to=DD/MM/YYYY`
