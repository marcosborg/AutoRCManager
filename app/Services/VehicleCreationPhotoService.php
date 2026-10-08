<?php

namespace App\Services;

use App\Models\Vehicle;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class VehicleCreationPhotoService
{
    public function store(Vehicle $vehicle, array $groups): array
    {
        $failed = [];

        foreach ($groups as $collection => $files) {
            foreach ($files as $file) {
                $token = (string) Str::uuid();
                try {
                    DB::transaction(function () use ($vehicle, $file, $collection, $token) {
                        try {
                            $vehicle->addMedia($file)
                                ->withCustomProperties(['creation_upload_token' => $token])
                                ->toMediaCollection($collection);
                        } catch (\Throwable $exception) {
                            // Clean only this attempt, before its media records are rolled back.
                            $vehicle->media()->where('custom_properties->creation_upload_token', $token)
                                ->get()->each->delete();
                            throw $exception;
                        }
                    });
                } catch (\Throwable $exception) {
                    report($exception);
                    $failed[] = [
                        'name' => $file->getClientOriginalName(),
                        'group' => $collection === 'inicial' ? 'Fotografias da aquisição' : 'Fotografias atuais',
                    ];
                }
            }
        }

        return $failed;
    }
}
