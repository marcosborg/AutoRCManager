<?php

namespace App\Http\Controllers\Admin;

use App\Models\VehiclePosition;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class GpsController
{
    /**
     * Devolve as posicoes mais recentes (uma por tracker), em JSON.
     */
    public function latest(Request $request): JsonResponse
    {
        $trackerId = $request->get('tracker_id');
        $limit = (int) $request->get('limit', 100);
        $limit = max(1, min($limit, 200));

        try {
            $latestIds = VehiclePosition::query()
                ->when($trackerId, fn ($query) => $query->where('tracker_id', $trackerId))
                ->selectRaw('MAX(id) as id')
                ->groupBy('tracker_id')
                ->pluck('id');

            $positions = VehiclePosition::query()
                ->whereIn('id', $latestIds)
                ->orderBy('tracker_id')
                ->orderByDesc('reported_at')
                ->limit($limit)
                ->get([
                    'id',
                    'tracker_id',
                    'latitude',
                    'longitude',
                    'speed_kph',
                    'fix_valid',
                    'voltage',
                    'reported_at',
                    'created_at',
                    'raw_data',
                ]);

            return response()->json([
                'data' => $positions,
                'count' => $positions->count(),
            ]);
        } catch (Throwable $exception) {
            Log::channel('gps')->error('Erro ao gerar JSON de posicoes GPS.', [
                'erro' => $exception->getMessage(),
            ]);

            return response()->json([
                'error' => 'Erro ao obter posicoes GPS.',
                'message' => $exception->getMessage(),
            ], 500);
        }
    }
}
