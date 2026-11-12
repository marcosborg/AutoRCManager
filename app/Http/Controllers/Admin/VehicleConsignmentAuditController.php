<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\VehicleConsignmentAudit;
use App\Support\LicensePlate;
use Gate;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VehicleConsignmentAuditController extends Controller
{
    public function index(Request $request)
    {
        abort_if(Gate::denies('vehicle_consignment_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');
        $request->validate(['export' => ['nullable', 'in:csv']]);

        $license = trim((string) $request->query('license', ''));
        $normalizedLicense = LicensePlate::normalize($license);
        $occurrenceDate = trim((string) $request->query('occurrence_date', ''));
        $occurrenceDay = null;
        if ($occurrenceDate !== '') {
            try {
                $occurrenceDay = Carbon::createFromFormat('Y-m-d', $occurrenceDate)->startOfDay();
            } catch (\Throwable) {
                return back()->withErrors(['occurrence_date' => 'A data da ocorrência é inválida.']);
            }
        }
        $query = VehicleConsignmentAudit::query()
            ->when($normalizedLicense !== '', function ($query) use ($normalizedLicense) {
                $like = '%' . $normalizedLicense . '%';
                $query->where(function ($subQuery) use ($like) {
                    $subQuery
                        ->whereRaw("REPLACE(REPLACE(UPPER(vehicle_license_before), '-', ''), ' ', '') LIKE ?", [$like])
                        ->orWhereRaw("REPLACE(REPLACE(UPPER(vehicle_license_after), '-', ''), ' ', '') LIKE ?", [$like]);
                });
            })
            ->when($occurrenceDay, function ($query) use ($occurrenceDay) {
                $endOfDay = $occurrenceDay->copy()->endOfDay();
                $query
                    ->where('effective_starts_at', '<=', $endOfDay)
                    ->where(function ($subQuery) use ($occurrenceDay) {
                        $subQuery->whereNull('effective_ends_at')
                            ->orWhere('effective_ends_at', '>=', $occurrenceDay);
                    });
            })
            ->latest('id');

        if ($request->input('export') === 'csv') {
            return response()->streamDownload(function () use ($query) {
                $out = fopen('php://output', 'w');
                fwrite($out, "\xEF\xBB\xBF");
                fputcsv($out, ['ID', 'Data', 'Ação', 'Matrícula anterior', 'Matrícula atual', 'Consignação', 'Utilizador', 'ID utilizador', 'IP', 'Início efetivo', 'Fim efetivo', 'Alterações'], ';');
                foreach ($query->lazy(200) as $audit) {
                    $before = $audit->before ?? [];
                    $after = $audit->after ?? [];
                    $changes = [];
                    foreach (array_unique(array_merge(array_keys($before), array_keys($after))) as $key) {
                        if (in_array($key, ['created_at', 'updated_at'], true) || ($before[$key] ?? null) == ($after[$key] ?? null)) continue;
                        $format = fn ($value) => is_scalar($value) ? (string) $value : json_encode($value, JSON_UNESCAPED_UNICODE);
                        $changes[] = $key . ': ' . $format($before[$key] ?? null) . ' → ' . $format($after[$key] ?? null);
                    }
                    $row = [$audit->id, $audit->created_at?->format('Y-m-d H:i:s'),
                        ['created' => 'Criada', 'updated' => 'Editada', 'deleted' => 'Eliminada'][$audit->action] ?? $audit->action,
                        $audit->vehicle_license_before, $audit->vehicle_license_after, $audit->consignment_id,
                        $audit->user_name ?: 'Sistema', $audit->user_id, $audit->ip_address,
                        $audit->effective_starts_at?->format('Y-m-d H:i:s'), $audit->effective_ends_at?->format('Y-m-d H:i:s'),
                        implode(' | ', $changes)];
                    fputcsv($out, array_map(function ($value) {
                        $value = (string) $value;
                        return preg_match('/^\s*[=+@-]/u', $value) ? "'" . $value : $value;
                    }, $row), ';');
                }
                fclose($out);
            }, 'historico-consignacoes-' . now('Europe/Lisbon')->format('Y-m-d') . '.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
        }
        $audits = $query->paginate(100)->withQueryString();

        return view('admin.vehicleConsignmentAudits.index', compact('audits', 'license', 'occurrenceDate'));
    }
}
