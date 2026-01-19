<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use App\Services\VehicleTimelineService;
use Dompdf\Dompdf;
use Dompdf\Options;
use Gate;
use Symfony\Component\HttpFoundation\Response;

class VehicleTimelineExportController extends Controller
{
    public function exportPdf(Vehicle $vehicle, VehicleTimelineService $service)
    {
        abort_if(Gate::denies('vehicle_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $events = $service->buildForVehicle($vehicle);
        $totalCost = abs((float) $events->where('type', 'cost')->sum('amount'));
        $totalRevenue = (float) $events->where('type', 'revenue')->sum('amount');
        $result = $totalRevenue - $totalCost;

        $startsAt = $events->min('date_start');
        $endsAt = $events->max('date_start');

        $html = view('exports.vehicle_timeline_pdf', compact(
            'vehicle',
            'events',
            'totalCost',
            'totalRevenue',
            'result',
            'startsAt',
            'endsAt'
        ))->render();

        $options = new Options();
        $options->set('isRemoteEnabled', false);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $filename = sprintf('timeline-%s.pdf', $vehicle->license ?? $vehicle->foreign_license ?? $vehicle->id);

        return $dompdf->stream($filename, [
            'Attachment' => true,
        ]);
    }
}
