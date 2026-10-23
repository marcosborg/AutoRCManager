<?php

namespace App\Observers;

use App\Models\VehicleDocumentFollowup;
use App\Support\VehicleDocuments;
use Illuminate\Support\Facades\DB;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class VehicleDocumentMediaObserver
{
    public function updated(Media $media): void
    {
        $this->review($media, false);
    }

    public function deleted(Media $media): void
    {
        $this->review($media, true);
    }

    private function review(Media $media, bool $deleted): void
    {
        if (! $deleted && ! $media->wasChanged(['custom_properties', 'collection_name', 'model_id', 'model_type'])) {
            return;
        }
        DB::transaction(function () use ($media, $deleted) {
            foreach (VehicleDocumentFollowup::where('media_id', $media->id)->where('status', 'completed')->lockForUpdate()->get() as $f) {
                if ($deleted || $media->model_type !== $f->vehicle?->getMorphClass() || (int) $media->model_id !== (int) $f->vehicle_id || ! VehicleDocuments::matches($media, $f->document)) {
                    $f->status = 'review';
                    $f->record('proof_changed', VehicleDocuments::annulled($media) ? 'A cópia digital foi anulada: '.$media->getCustomProperty('document_annulment.reason').'. Conferir antes de reativar o lembrete.' : 'A cópia digital foi removida ou deixou de corresponder ao documento. Conferir antes de reativar o lembrete.');
                }
            }
        });
    }
}
