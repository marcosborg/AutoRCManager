<?php

namespace App\Models;

use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Vehicle extends Model implements HasMedia
{
    use SoftDeletes, InteractsWithMedia, HasFactory;

    public $table = 'vehicles';

    public const TRANSMISSION_SELECT = [
        'manual'    => 'Manual',
        'automatic' => 'Automáticas',
    ];

    protected $appends = [
        'documents',
        'photos',
        'invoice',
        'inicial',
        'withdrawal_authorization_file',
        'withdrawal_documents',
        'payment_comprovant',
    ];

    protected $dates = [
        'license_date',
        'date',
        'payment_date',
        'withdrawal_authorization_date',
        'pickup_state_date',
        'chekin_date',
        'sale_date',
        'sele_chekout',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $fillable = [
        'general_state_id',
        'license',
        'foreign_license',
        'brand_id',
        'model',
        'version',
        'transmission',
        'year',
        'month',
        'license_date',
        'color',
        'fuel',
        'kilometers',
        'inspec_b',
        'purchase_and_sale_agreement',
        'copy_of_the_citizen_card',
        'tax_identification_card',
        'copy_of_the_stamp_duty_receipt',
        'vehicle_registration_document',
        'vehicle_ownership_title',
        'vehicle_keys',
        'vehicle_manuals',
        'release_of_reservation_or_mortgage',
        'leasing_agreement',
        'cables',
        'date',
        'pending',
        'additional_items',
        'purchase_price',
        'suplier_id',
        'payment_date',
        'payment_status_id',
        'amount_paid',
        'carrier_id',
        'storage_location',
        'withdrawal_authorization',
        'withdrawal_authorization_date',
        'pickup_state_id',
        'pickup_state_date',
        'total_price',
        'minimum_price',
        'pvp',
        'client_id',
        'client_amount_paid',
        'payment_notes',
        'client_registration',
        'chekin_documents',
        'chekin_date',
        'sale_date',
        'sele_chekout',
        'first_key',
        'scuts',
        'key',
        'manuals',
        'elements_with_vehicle',
        'sale_notes',
        'local',
        'created_at',
        'updated_at',
        'deleted_at',
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

    public function general_state()
    {
        return $this->belongsTo(GeneralState::class, 'general_state_id');
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class, 'brand_id');
    }

    public function getLicenseDateAttribute($value)
    {
        return $value ? Carbon::parse($value)->format(config('panel.date_format')) : null;
    }

    public function setLicenseDateAttribute($value)
    {
        $this->attributes['license_date'] = $value ? Carbon::createFromFormat(config('panel.date_format'), $value)->format('Y-m-d') : null;
    }

    public function seller_client()
    {
        return $this->belongsTo(Client::class, 'seller_client_id');
    }

    public function buyer_client()
    {
        return $this->belongsTo(Client::class, 'buyer_client_id');
    }

    public function getDateAttribute($value)
    {
        return $value ? Carbon::parse($value)->format(config('panel.date_format')) : null;
    }

    public function setDateAttribute($value)
    {
        $this->attributes['date'] = $value ? Carbon::createFromFormat(config('panel.date_format'), $value)->format('Y-m-d') : null;
    }

    public function getDocumentsAttribute()
    {
        return $this->getMedia('documents');
    }

    public function getPhotosAttribute()
    {
        $files = $this->getMedia('photos');
        $files->each(function ($item) {
            $item->url       = $item->getUrl();
            $item->thumbnail = $item->getUrl('thumb');
            $item->preview   = $item->getUrl('preview');
        });

        return $files;
    }

    public function suplier()
    {
        return $this->belongsTo(Suplier::class, 'suplier_id');
    }

    public function getPaymentDateAttribute($value)
    {
        return $value ? Carbon::parse($value)->format(config('panel.date_format')) : null;
    }

    public function setPaymentDateAttribute($value)
    {
        $this->attributes['payment_date'] = $value ? Carbon::createFromFormat(config('panel.date_format'), $value)->format('Y-m-d') : null;
    }

    public function getInvoiceAttribute()
    {
        return $this->getMedia('invoice');
    }

    public function getInicialAttribute()
    {
        $files = $this->getMedia('inicial');
        $files->each(function ($item) {
            $item->url       = $item->getUrl();
            $item->thumbnail = $item->getUrl('thumb');
            $item->preview   = $item->getUrl('preview');
        });

        return $files;
    }

    public function payment_status()
    {
        return $this->belongsTo(PaymentStatus::class, 'payment_status_id');
    }

    public function carrier()
    {
        return $this->belongsTo(Carrier::class, 'carrier_id');
    }

    public function getWithdrawalAuthorizationFileAttribute()
    {
        return $this->getMedia('withdrawal_authorization_file');
    }

    public function getWithdrawalAuthorizationDateAttribute($value)
    {
        return $value ? Carbon::parse($value)->format(config('panel.date_format')) : null;
    }

    public function setWithdrawalAuthorizationDateAttribute($value)
    {
        $this->attributes['withdrawal_authorization_date'] = $value ? Carbon::createFromFormat(config('panel.date_format'), $value)->format('Y-m-d') : null;
    }

    public function getWithdrawalDocumentsAttribute()
    {
        return $this->getMedia('withdrawal_documents');
    }

    public function pickup_state()
    {
        return $this->belongsTo(PickupState::class, 'pickup_state_id');
    }

    public function getPickupStateDateAttribute($value)
    {
        return $value ? Carbon::parse($value)->format(config('panel.date_format')) : null;
    }

    public function setPickupStateDateAttribute($value)
    {
        $this->attributes['pickup_state_date'] = $value ? Carbon::createFromFormat(config('panel.date_format'), $value)->format('Y-m-d') : null;
    }

    public function client()
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    public function getPaymentComprovantAttribute()
    {
        return $this->getMedia('payment_comprovant');
    }

    public function getChekinDateAttribute($value)
    {
        return $value ? Carbon::parse($value)->format(config('panel.date_format')) : null;
    }

    public function setChekinDateAttribute($value)
    {
        $this->attributes['chekin_date'] = $value ? Carbon::createFromFormat(config('panel.date_format'), $value)->format('Y-m-d') : null;
    }

    public function getSaleDateAttribute($value)
    {
        return $value ? Carbon::parse($value)->format(config('panel.date_format')) : null;
    }

    public function setSaleDateAttribute($value)
    {
        $this->attributes['sale_date'] = $value ? Carbon::createFromFormat(config('panel.date_format'), $value)->format('Y-m-d') : null;
    }

    public function getSeleChekoutAttribute($value)
    {
        return $value ? Carbon::parse($value)->format(config('panel.date_format')) : null;
    }

    public function setSeleChekoutAttribute($value)
    {
        $this->attributes['sele_chekout'] = $value ? Carbon::createFromFormat(config('panel.date_format'), $value)->format('Y-m-d') : null;
    }

    public function account_operations()
    {
        return $this->hasMany(AccountOperation::class, 'vehicle_id');
    }

    public function acquisition_operations()
    {
        return $this->hasMany(AccountOperation::class, 'vehicle_id')
            ->whereHas('account_item.account_category', function ($q) {
                $q->where('account_department_id', 1);
            });
    }

    public function client_operations()
    {
        return $this->hasMany(AccountOperation::class)
            ->whereHas('account_item.account_category', function ($q) {
                $q->where('account_department_id', 3);
            });
    }
}
