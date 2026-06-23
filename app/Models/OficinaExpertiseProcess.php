<?php

namespace App\Models;

use App\Traits\Auditable;
use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class OficinaExpertiseProcess extends Model implements HasMedia
{
    use Auditable, HasFactory, InteractsWithMedia, SoftDeletes;

    public const STATUS_RECEIVED = 'received';
    public const STATUS_EXPERTISE_SCHEDULED = 'expertise_scheduled';
    public const STATUS_REPORT_CREATED = 'report_created';
    public const STATUS_AWAITING_APPROVAL = 'awaiting_approval';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_AWAITING_REPAIR = 'awaiting_repair';
    public const STATUS_IN_REPAIR = 'in_repair';
    public const STATUS_REPAIR_COMPLETED = 'repair_completed';
    public const STATUS_INSURANCE_VALIDATION = 'insurance_validation';
    public const STATUS_NOT_COMPLIANT = 'not_compliant';
    public const STATUS_PAYMENT_REQUESTED = 'payment_requested';
    public const STATUS_INVOICE_SENT = 'invoice_sent';
    public const STATUS_PAYMENT_OVERDUE = 'payment_overdue';
    public const STATUS_PAYMENT_RECEIVED = 'payment_received';
    public const STATUS_CLOSED = 'closed';
    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_SELECT = [
        self::STATUS_RECEIVED => 'Recebido',
        self::STATUS_EXPERTISE_SCHEDULED => 'Peritagem agendada',
        self::STATUS_REPORT_CREATED => 'Relatório elaborado',
        self::STATUS_AWAITING_APPROVAL => 'A aguardar aprovação',
        self::STATUS_APPROVED => 'Aprovado',
        self::STATUS_AWAITING_REPAIR => 'A aguardar reparação',
        self::STATUS_IN_REPAIR => 'Em reparação',
        self::STATUS_REPAIR_COMPLETED => 'Reparação concluída',
        self::STATUS_INSURANCE_VALIDATION => 'Validação da seguradora',
        self::STATUS_NOT_COMPLIANT => 'Não conforme',
        self::STATUS_PAYMENT_REQUESTED => 'Pagamento pedido',
        self::STATUS_INVOICE_SENT => 'Fatura enviada',
        self::STATUS_PAYMENT_OVERDUE => 'Pagamento em atraso',
        self::STATUS_PAYMENT_RECEIVED => 'Pagamento recebido',
        self::STATUS_CLOSED => 'Fechado',
        self::STATUS_CANCELLED => 'Cancelado',
    ];

    public const REPAIR_TYPE_SELECT = [
        'workshop' => 'Oficina',
        'painting' => 'Pintura',
        'external' => 'Externo',
    ];

    public const ATTACHMENT_COLLECTIONS = [
        'expertise_report' => 'Relatório de peritagem',
        'proofs' => 'Comprovativos',
        'initial_photos' => 'Fotografias iniciais',
        'final_photos' => 'Fotografias finais',
        'sent_invoice' => 'Fatura enviada',
        'payment_proof' => 'Comprovativo de pagamento',
    ];

    public const DATETIME_FIELDS = [
        'scheduled_expertise_date',
        'approval_date',
        'repair_start_date',
        'expected_repair_date',
        'repair_completed_date',
        'insurance_validation_date',
        'invoice_sent_date',
        'payment_received_date',
    ];

    protected $fillable = [
        'vehicle_id',
        'license',
        'insurance_company',
        'claim_number',
        'process_number',
        'entry_date',
        'scheduled_expertise_date',
        'expert_name',
        'approved_amount',
        'approval_date',
        'repair_start_date',
        'expected_repair_date',
        'repair_completed_date',
        'insurance_validation_date',
        'invoice_sent_date',
        'payment_received_date',
        'closed_at',
        'status',
        'repair_type',
        'notes',
        'rejection_reason',
        'created_by_id',
        'updated_by_id',
    ];

    protected $dates = [
        'entry_date',
        'scheduled_expertise_date',
        'approval_date',
        'repair_start_date',
        'expected_repair_date',
        'repair_completed_date',
        'insurance_validation_date',
        'invoice_sent_date',
        'payment_received_date',
        'closed_at',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $casts = [
        'entry_date' => 'date',
        'scheduled_expertise_date' => 'datetime',
        'approval_date' => 'datetime',
        'repair_start_date' => 'datetime',
        'expected_repair_date' => 'datetime',
        'repair_completed_date' => 'datetime',
        'insurance_validation_date' => 'datetime',
        'invoice_sent_date' => 'datetime',
        'payment_received_date' => 'datetime',
        'closed_at' => 'datetime',
        'approved_amount' => 'float',
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

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class, 'vehicle_id');
    }

    public function created_by()
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function updated_by()
    {
        return $this->belongsTo(User::class, 'updated_by_id');
    }

    public function histories()
    {
        return $this->hasMany(OficinaExpertiseProcessHistory::class, 'process_id');
    }

    public function latest_status_history()
    {
        return $this->hasOne(OficinaExpertiseProcessHistory::class, 'process_id')->latestOfMany();
    }

    public function getLicenseDisplayAttribute(): string
    {
        return $this->vehicle->license ?? $this->vehicle->foreign_license ?? $this->license ?? '-';
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_SELECT[$this->status] ?? $this->status;
    }

    public function getRepairTypeLabelAttribute(): string
    {
        return self::REPAIR_TYPE_SELECT[$this->repair_type] ?? ($this->repair_type ?: '-');
    }

    public function getDaysInCurrentStatusAttribute(): int
    {
        $since = optional($this->latest_status_history)->created_at ?: $this->updated_at ?: $this->created_at;

        return $since ? Carbon::parse($since)->startOfDay()->diffInDays(now()->startOfDay()) : 0;
    }

    public function getNextActionAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_RECEIVED => $this->scheduled_expertise_date ? 'Realizar peritagem' : 'Agendar peritagem',
            self::STATUS_EXPERTISE_SCHEDULED => 'Elaborar relatório',
            self::STATUS_REPORT_CREATED => 'Enviar/aguardar aprovação',
            self::STATUS_AWAITING_APPROVAL => 'Confirmar aprovação da seguradora',
            self::STATUS_APPROVED, self::STATUS_AWAITING_REPAIR => 'Iniciar reparação',
            self::STATUS_IN_REPAIR, self::STATUS_NOT_COMPLIANT => 'Concluir reparação',
            self::STATUS_REPAIR_COMPLETED => 'Validar com seguradora',
            self::STATUS_INSURANCE_VALIDATION => 'Pedir pagamento',
            self::STATUS_PAYMENT_REQUESTED => 'Enviar fatura',
            self::STATUS_INVOICE_SENT, self::STATUS_PAYMENT_OVERDUE => 'Confirmar pagamento',
            self::STATUS_PAYMENT_RECEIVED => 'Fechar processo',
            self::STATUS_CLOSED => 'Processo fechado',
            self::STATUS_CANCELLED => 'Processo cancelado',
            default => 'Atualizar estado',
        };
    }

    public function getIsAlertAttribute(): bool
    {
        return $this->alertReason() !== null;
    }

    public function alertReason(): ?string
    {
        if ($this->status === self::STATUS_RECEIVED && ! $this->scheduled_expertise_date && $this->entry_date && $this->entry_date->diffInDays(now()) > 3) {
            return 'Mais de 3 dias sem peritagem agendada';
        }

        if ($this->status === self::STATUS_AWAITING_APPROVAL && $this->days_in_current_status > 7) {
            return 'Mais de 7 dias à espera de aprovação';
        }

        if ($this->status === self::STATUS_IN_REPAIR && ! $this->repair_completed_date && $this->days_in_current_status > 7) {
            return 'Mais de 7 dias em reparação';
        }

        if (in_array($this->status, [self::STATUS_INVOICE_SENT, self::STATUS_PAYMENT_OVERDUE], true)
            && $this->invoice_sent_date
            && ! $this->payment_received_date
            && $this->invoice_sent_date->diffInDays(now()) > 30) {
            return 'Mais de 30 dias após envio da fatura sem pagamento';
        }

        return null;
    }

    public static function dateFieldForStatus(string $status): ?string
    {
        return [
            self::STATUS_EXPERTISE_SCHEDULED => 'scheduled_expertise_date',
            self::STATUS_APPROVED => 'approval_date',
            self::STATUS_IN_REPAIR => 'repair_start_date',
            self::STATUS_REPAIR_COMPLETED => 'repair_completed_date',
            self::STATUS_INSURANCE_VALIDATION => 'insurance_validation_date',
            self::STATUS_INVOICE_SENT => 'invoice_sent_date',
            self::STATUS_PAYMENT_RECEIVED => 'payment_received_date',
        ][$status] ?? null;
    }

    public static function dateLabelForStatus(string $status): ?string
    {
        return [
            self::STATUS_EXPERTISE_SCHEDULED => 'Data e hora agendada da peritagem',
            self::STATUS_APPROVED => 'Data e hora de aprovação',
            self::STATUS_IN_REPAIR => 'Data e hora de início da reparação',
            self::STATUS_REPAIR_COMPLETED => 'Data e hora de conclusão da reparação',
            self::STATUS_INSURANCE_VALIDATION => 'Data e hora de validação da seguradora',
            self::STATUS_INVOICE_SENT => 'Data e hora de envio da fatura',
            self::STATUS_PAYMENT_RECEIVED => 'Data e hora de pagamento recebido',
        ][$status] ?? null;
    }
}
