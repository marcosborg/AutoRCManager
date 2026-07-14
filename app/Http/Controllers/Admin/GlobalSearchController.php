<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class GlobalSearchController extends Controller
{
    public function search(Request $request): JsonResponse
    {
        $search = $request->input('search');

        if (! is_array($search) || ! isset($search['term'])) {
            abort(400);
        }

        $term = trim((string) $search['term']);

        if (mb_strlen($term) < 3) {
            return response()->json(['results' => []]);
        }

        return response()->json([
            'results' => [
                ...$this->vehicleResults($term),
                ...$this->clientResults($term),
            ],
        ]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function vehicleResults(string $term): array
    {
        $route = $this->resultRoute('vehicle_edit', 'vehicle_show', 'admin.vehicles.edit', 'admin.vehicles.show');

        if ($route === null) {
            return [];
        }

        return Vehicle::query()
            ->with('brand')
            ->where(function (Builder $query) use ($term): void {
                $query->searchByLicense($term)
                    ->orWhere('model', 'like', '%'.$term.'%')
                    ->orWhereHas('brand', function (Builder $brandQuery) use ($term): void {
                        $brandQuery->where('name', 'like', '%'.$term.'%');
                    });
            })
            ->orderBy('license')
            ->limit(10)
            ->get()
            ->map(function (Vehicle $vehicle) use ($route): array {
                $description = trim(implode(' ', array_filter([
                    $vehicle->brand?->name,
                    $vehicle->model,
                ])));

                return [
                    'id' => 'vehicle-'.$vehicle->id,
                    'model' => 'Viatura',
                    'title' => $vehicle->license ?: ($vehicle->foreign_license ?: 'Sem matrícula'),
                    'details' => $this->filledDetails([
                        'Matrícula estrangeira' => $vehicle->foreign_license,
                        'Viatura' => $description,
                    ]),
                    'url' => route($route, $vehicle),
                ];
            })
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function clientResults(string $term): array
    {
        $route = $this->resultRoute('client_edit', 'client_show', 'admin.clients.edit', 'admin.clients.show');
        $normalizedPhone = preg_replace('/\D+/', '', $term) ?? '';

        if ($route === null) {
            return [];
        }

        return Client::query()
            ->where(function (Builder $query) use ($term, $normalizedPhone): void {
                foreach (['name', 'company_name', 'vat', 'company_vat', 'phone', 'company_phone', 'email', 'company_email'] as $field) {
                    $query->orWhere($field, 'like', '%'.$term.'%');
                }

                if (mb_strlen($normalizedPhone) >= 3) {
                    foreach (['phone', 'company_phone'] as $phoneField) {
                        $query->orWhereRaw(
                            "REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(COALESCE({$phoneField}, ''), ' ', ''), '-', ''), '(', ''), ')', ''), '+', ''), '.', '') LIKE ?",
                            ['%'.$normalizedPhone.'%']
                        );
                    }
                }
            })
            ->orderBy('name')
            ->limit(10)
            ->get()
            ->map(function (Client $client) use ($route): array {
                return [
                    'id' => 'client-'.$client->id,
                    'model' => 'Cliente',
                    'title' => $client->name ?: ($client->company_name ?: 'Cliente sem nome'),
                    'details' => $this->filledDetails([
                        'Empresa' => $client->company_name,
                        'NIF' => $client->vat ?: $client->company_vat,
                        'Telefone' => $client->phone ?: $client->company_phone,
                        'Email' => $client->email ?: $client->company_email,
                    ]),
                    'url' => route($route, $client),
                ];
            })
            ->all();
    }

    private function resultRoute(string $editAbility, string $showAbility, string $editRoute, string $showRoute): ?string
    {
        if (Gate::allows($editAbility)) {
            return $editRoute;
        }

        return Gate::allows($showAbility) ? $showRoute : null;
    }

    /**
     * @param  array<string, mixed>  $details
     * @return array<int, array{label: string, value: string}>
     */
    private function filledDetails(array $details): array
    {
        return collect($details)
            ->filter(fn (mixed $value): bool => $value !== null && trim((string) $value) !== '')
            ->map(fn (mixed $value, string $label): array => [
                'label' => $label,
                'value' => (string) $value,
            ])
            ->values()
            ->all();
    }
}
