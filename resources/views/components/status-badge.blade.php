@props(['status', 'showIcon' => true])

@php
    $config = match($status) {
        'submitted' => [
            'label' => 'Submitted',
            'icon' => 'fa-paper-plane',
            'class' => 'b-gold',
        ],
        'under_review' => [
            'label' => 'Under Review',
            'icon' => 'fa-magnifying-glass',
            'class' => 'b-gold',
        ],
        'routed' => [
            'label' => 'Routed',
            'icon' => 'fa-route',
            'class' => 'b-blue',
        ],
        'in_progress' => [
            'label' => 'In Progress',
            'icon' => 'fa-gears',
            'class' => 'b-blue',
        ],
        'resolved' => [
            'label' => 'Resolved',
            'icon' => 'fa-circle-check',
            'class' => 'b-green',
        ],
        'no_action' => [
            'label' => 'No Action',
            'icon' => 'fa-circle-xmark',
            'class' => 'b-gray',
        ],
        'merged' => [
            'label' => 'Merged',
            'icon' => 'fa-code-merge',
            'class' => 'b-gray',
        ],
        'referred' => [
            'label' => 'Referred',
            'icon' => 'fa-share',
            'class' => 'b-gray',
        ],
        'emergency_flagged' => [
            'label' => 'Emergency Flagged',
            'icon' => 'fa-triangle-exclamation',
            'class' => 'b-red',
        ],
        'needs_info' => [
            'label' => 'Needs Info',
            'icon' => 'fa-clipboard-question',
            'class' => 'b-gold',
        ],
        'awaiting_confirmation' => [
            'label' => 'Awaiting Confirmation',
            'icon' => 'fa-clock',
            'class' => 'b-blue',
        ],
        'reopened' => [
            'label' => 'Reopened',
            'icon' => 'fa-arrow-rotate-left',
            'class' => 'b-red',
        ],
        default => [
            'label' => ucfirst(str_replace('_', ' ', $status ?? 'unknown')),
            'icon' => 'fa-circle-question',
            'class' => 'b-gray',
        ],
    };
@endphp

<span {{ $attributes->merge(['class' => 'status-badge ' . $config['class']]) }}>
    @if($showIcon)
        <i class="fa-solid {{ $config['icon'] }} mr-1.5" aria-hidden="true"></i>
    @endif
    <span>{{ $config['label'] }}</span>
</span>
