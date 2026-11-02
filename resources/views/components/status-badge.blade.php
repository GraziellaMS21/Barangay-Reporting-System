@props(['status'])

@php
    $config = match($status) {
        'submitted' => [
            'label' => 'Submitted',
            'icon' => 'fa-paper-plane',
            'class' => 'bg-blue-100 text-blue-800 border-blue-200',
        ],
        'under_review' => [
            'label' => 'Under Review',
            'icon' => 'fa-magnifying-glass',
            'class' => 'bg-yellow-100 text-yellow-800 border-yellow-200',
        ],
        'routed' => [
            'label' => 'Routed',
            'icon' => 'fa-route',
            'class' => 'bg-indigo-100 text-indigo-800 border-indigo-200',
        ],
        'in_progress' => [
            'label' => 'In Progress',
            'icon' => 'fa-gears',
            'class' => 'bg-orange-100 text-orange-800 border-orange-200',
        ],
        'resolved' => [
            'label' => 'Resolved',
            'icon' => 'fa-circle-check',
            'class' => 'bg-green-100 text-green-800 border-green-200',
        ],
        'out_of_scope' => [
            'label' => 'Out of Scope',
            'icon' => 'fa-ban',
            'class' => 'bg-gray-100 text-gray-800 border-gray-200',
        ],
        'merged' => [
            'label' => 'Merged',
            'icon' => 'fa-code-merge',
            'class' => 'bg-purple-100 text-purple-800 border-purple-200',
        ],
        'referred' => [
            'label' => 'Referred',
            'icon' => 'fa-share',
            'class' => 'bg-teal-100 text-teal-800 border-teal-200',
        ],
        'emergency_flagged' => [
            'label' => 'Emergency Flagged',
            'icon' => 'fa-triangle-exclamation',
            'class' => 'bg-red-100 text-red-800 border-red-200',
        ],
        default => [
            'label' => ucfirst(str_replace('_', ' ', $status ?? 'unknown')),
            'icon' => 'fa-circle-question',
            'class' => 'bg-gray-100 text-gray-800 border-gray-200',
        ],
    };
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium border ' . $config['class']]) }}>
    <i class="fa-solid {{ $config['icon'] }} mr-1.5" aria-hidden="true"></i>
    <span>{{ $config['label'] }}</span>
</span>
