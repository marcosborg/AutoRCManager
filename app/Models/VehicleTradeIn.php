<?php

namespace App\Models;

use App\Traits\Auditable;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class VehicleTradeIn extends Model implements HasMedia
{
    use SoftDeletes, InteractsWithMedia, HasFactory, Auditable;

    public const STATUS_PENDING = 'pending';
    public const STATUS_CONVERTED = 'converted';
    public const STATUS_REJECTED = 'rejected';

    public const STATUS_SELECT = [
        self::STATUS_PENDING => 'Pendente',
        self::STATUS_CONVERTED => 'Verificada',
        self::STATUS_REJECTED => 'Rejeitada',
    ];

    public const DOCUMENT_COLLECTIONS = [
        'purchase_sale_rgpd' => 'Declaracao Compra e Venda + RGPD',
        'ipo' => 'IPO - Ficha',
        'internal_invoice' => 'Fatura interna',
        'reservation_extinction_authorization' => 'Autorizacao extincao reserva',
    ];

    public const STANDALONE_DOCUMENT_COLLECTIONS = [
        'vehicle_delivery_declaration' => 'Declaracao de entrega de viatura',
        'ipo' => 'IPO - Ficha',
        'internal_invoice' => 'Fatura interna',
        'reservation_extinction_authorization' => 'Autorizacao extincao reserva',
    ];

    public $table = 'vehicle_trade_ins';

    protected $fillable = [
        'sold_vehicle_id',
        'created_by_id',
        'converted_by_id',
        'created_vehicle_id',
        'license',
        'normalized_license',
        'amount',
        'status',
        'notes',
        'rejection_reason',
        'converted_at',
        'rejected_at',
        'has_registration_title',
        'has_purchase_sale_rgpd',
        'has_vehicle_delivery_declaration',
        'has_seller_identification',
        'has_ipo',
        'has_two_keys',
        'has_charging_cable_mode_2',
        'has_charging_cable_mode_3',
        'has_manuals',
        'has_internal_invoice',
        'has_finance_mod_2',
        'has_promissory_note',
        'has_reservation_extinction_authorization',
    ];

    protected $dates = [
        'converted_at',
        'rejected_at',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $casts = [
        'amount' => 'float',
        'has_registration_title' => 'boolean',
        'has_purchase_sale_rgpd' => 'boolean',
        'has_vehicle_delivery_declaration' => 'boolean',
        'has_seller_identification' => 'boolean',
        'has_ipo' => 'boolean',
        'has_two_keys' => 'boolean',
        'has_charging_cable_mode_2' => 'boolean',
        'has_charging_cable_mode_3' => 'boolean',
        'has_manuals' => 'boolean',
        'has_internal_invoice' => 'boolean',
        'has_finance_mod_2' => 'boolean',
        'has_promissory_note' => 'boolean',
        'has_reservation_extinction_authorization' => 'boolean',
    ];

    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }

    public function registerMediaConversions(Media $media = null): void
    {
        $this->addMediaConversion('thumb')->fit('crop', 50, 50);
        $this->addMediaConversion('preview')->fit('crop', 120, 120);
    }

    public static function normalizeLicense(string $license): string
    {
        return preg_replace('/[\s-]+/', '', Str::upper(trim($license))) ?? '';
    }

    public function sold_vehicle()
    {
        return $this->belongsTo(Vehicle::class, 'sold_vehicle_id');
    }

    public function created_vehicle()
    {
        return $this->belongsTo(Vehicle::class, 'created_vehicle_id');
    }

    public function created_by()
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function converted_by()
    {
        return $this->belongsTo(User::class, 'converted_by_id');
    }
}
