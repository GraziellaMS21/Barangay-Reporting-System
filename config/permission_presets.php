<?php

return [
    'presets' => [
        'staff_full' => [
            'label' => 'Full Barangay Staff',
            'description' => 'Standard staff access across cases, reports, personnel, and referrals without confidential case access or system backups.',
            'permissions' => [
                'settings.manage',
                'fiscal_year.manage',
                'templates.manage',
                'personnel.manage',
                'users.manage',
                'reports_center.view',
                'reports_center.export',
                'cases.view',
                'cases.create',
                'cases.edit',
                'cases.summons',
                'cases.escalate',
                'agencies.manage',
                'referrals.manage',
                'dashboard.view',
                'audit.view',
            ],
        ],
        'case_encoder' => [
            'label' => 'Case Encoder / Lupon Clerk',
            'description' => 'Filing, editing, and managing summons and hearings for non-confidential cases.',
            'permissions' => [
                'cases.view',
                'cases.create',
                'cases.edit',
                'cases.summons',
                'dashboard.view',
            ],
        ],
        'reports_analyst' => [
            'label' => 'Reports Analyst',
            'description' => 'Access to view and export reports from the Reports Center and Dashboard.',
            'permissions' => [
                'reports_center.view',
                'reports_center.export',
                'dashboard.view',
            ],
        ],
        'case_reviewer' => [
            'label' => 'Case Reviewer (Peace & Order / VAWC / BCPC)',
            'description' => 'Reviewing all dispute cases including confidential cases, reports, and dashboard metrics.',
            'permissions' => [
                'cases.view',
                'cases.view_confidential',
                'reports.view',
                'reports_center.view',
                'dashboard.view',
            ],
        ],
    ],
];
