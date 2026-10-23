<?php

namespace App\Support;

use App\Models\Vehicle;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class VehicleDocuments
{
    public const COLLECTIONS = ['documents', 'additional_documents', 'dav'];
    public const TYPES = [
        'unknown' => 'Por identificar',
        'dua' => 'DUA',
        'inspection' => 'Inspeção (IPO)',
        'dua_inspection' => 'DUA e inspeção no mesmo ficheiro',
        'dav' => 'DAV',
        'purchase_invoice' => 'Fatura de compra',
        'signed_purchase_form' => 'Impresso de compra assinado',
        'purchase_and_sale_agreement' => 'Contrato de Compra e Venda',
        'copy_of_the_citizen_card' => 'Cópia do Cartão de Cidadão',
        'tax_identification_card' => 'Cartão de Identificação Fiscal',
        'copy_of_the_stamp_duty_receipt' => 'Cópia do Recibo do Imposto de Selo',
        'release_of_reservation_or_mortgage' => 'Extinção de reserva / hipoteca',
        'leasing_agreement' => 'Contrato de Leasing',
        'other' => 'Outro documento',
    ];

    public static function checks(): array
    {
        $fields = ['purchase_and_sale_agreement', 'copy_of_the_citizen_card', 'tax_identification_card',
            'copy_of_the_stamp_duty_receipt', 'vehicle_ownership_title', 'release_of_reservation_or_mortgage', 'leasing_agreement'];
        $checks = [];
        foreach ($fields as $field) {
            $checks[$field] = $field === 'vehicle_ownership_title' ? 'DUA'
                : ($field === 'release_of_reservation_or_mortgage' ? 'Extinção de reserva / hipoteca' : trans('cruds.vehicle.fields.'.$field));
        }
        return $checks;
    }

    public static function pendingChecks(Vehicle $vehicle): array
    {
        return array_filter(self::checks(), fn ($field) => (int) $vehicle->{$field} !== 1 && !self::notApplicable($vehicle, $field), ARRAY_FILTER_USE_KEY);
    }

    public static function applicabilityDocuments(): array
    {
        return self::checks() + ['inspection' => 'Inspeção (IPO)', 'dav' => 'DAV', 'purchase_invoice' => 'Fatura de compra', 'signed_purchase_form' => 'Impresso de compra assinado'];
    }

    public static function notApplicable(Vehicle $vehicle, string $field): bool
    {
        return data_get($vehicle->document_applicability, $field.'.status') === 'not_applicable';
    }

    public static function applicabilityRevision(Vehicle $vehicle, string $field): string
    {
        return hash('sha256', json_encode($vehicle->document_applicability[$field] ?? null));
    }

    public static function pendingChecksQuery(\Illuminate\Database\Eloquent\Builder $query): void
    {
        foreach (array_keys(self::checks()) as $field) {
            $path = 'document_applicability->'.$field.'->status';
            $query->orWhere(function ($q) use ($field, $path) {
                $q->where(fn ($receipt) => $receipt->whereNull($field)->orWhere($field, '!=', 1))
                    ->where(fn ($applicable) => $applicable->whereNull($path)->orWhere($path, '!=', 'not_applicable'));
            });
        }
    }

    public static function unidentifiedQuery(\Illuminate\Database\Eloquent\Builder $query): void
    {
        $classification = 'custom_properties->document_classification';
        $type = $classification.'->type';
        $query->whereNull('custom_properties->document_annulment')->whereIn('collection_name', self::COLLECTIONS)->where(function ($q) use ($classification, $type) {
            $q->where(function ($legacy) use ($classification) {
                $legacy->whereNull($classification)->where('collection_name', '!=', 'dav');
            })->orWhere(function ($classified) use ($classification, $type) {
                $classified->whereNotNull($classification)->where(function ($invalid) use ($type) {
                    $invalid->whereNull($type)->orWhereNotIn($type, array_diff(array_keys(self::TYPES), ['unknown']));
                });
            });
        });
    }

    public static function files(Vehicle $vehicle)
    {
        return $vehicle->media->whereIn('collection_name', self::COLLECTIONS)->sortBy('id');
    }

    public static function type(Media $media): string
    {
        $classification = $media->getCustomProperty('document_classification');
        if ($classification === null) {
            return $media->collection_name === 'dav' ? 'dav' : 'unknown';
        }
        $type = is_array($classification) ? ($classification['type'] ?? null) : null;
        return is_string($type) && array_key_exists($type, self::TYPES) ? $type : 'unknown';
    }

    public static function annulled(Media $media): bool
    {
        return $media->getCustomProperty('document_annulment') !== null;
    }

    public static function revision(Media $media): string
    {
        return hash('sha256', json_encode(self::annulled($media) ? [$media->getCustomProperty('document_classification'), $media->getCustomProperty('document_annulment')] : $media->getCustomProperty('document_classification')));
    }

    public static function matches($file, string $document): bool
    {
        $type = $document === 'vehicle_ownership_title' ? 'dua' : $document;
        return !self::annulled($file) && in_array($file->collection_name, self::COLLECTIONS, true)
            && (self::type($file) === $type || (self::type($file) === 'dua_inspection' && in_array($type, ['dua','inspection'], true)));
    }

    public static function count(Vehicle $vehicle, string $type): int
    {
        return self::files($vehicle)->filter(function ($media) use ($type) {
            $actual = self::type($media);
            return !self::annulled($media) && ($actual === $type || ($actual === 'dua_inspection' && in_array($type, ['dua', 'inspection'], true)));
        })->count();
    }
}
