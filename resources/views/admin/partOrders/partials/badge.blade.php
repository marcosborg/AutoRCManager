@php
    $type = $type ?? 'status';
    $value = $value ?? null;
    $statusClass = [
        'delayed' => 'danger',
        'received' => 'success',
        'partially_received' => 'warning',
        'ordered' => 'primary',
        'requesting_quotes' => 'info',
        'cancelled' => 'default',
        'draft' => 'default',
        'pending' => 'default',
        'overdue' => 'danger',
        'paid' => 'success',
        'partially_paid' => 'warning',
    ][$value] ?? 'default';
    $priorityClass = [
        'urgent' => 'danger',
        'normal' => 'primary',
        'low' => 'default',
    ][$value] ?? 'default';
@endphp
<span class="label label-{{ $type === 'priority' ? $priorityClass : $statusClass }}">{{ $label ?? $value ?? '-' }}</span>
