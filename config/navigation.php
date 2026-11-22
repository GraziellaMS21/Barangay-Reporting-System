<?php

return [
    'admin' => [
        [
            'section' => 'Dashboard',
            'items' => [
                [
                    'label' => 'Dashboard',
                    'route' => 'admin.dashboard',
                    'icon' => 'fa-solid fa-gauge-high',
                    'permission' => null,
                    'active_pattern' => 'admin.dashboard*',
                ],
                [
                    'label' => 'Secretary Dashboard',
                    'route' => 'admin.secretary',
                    'icon' => 'fa-solid fa-clipboard-check',
                    'permission' => 'cases.view',
                    'active_pattern' => 'admin.secretary*',
                ],
            ],
        ],
        [
            'section' => 'Intake & Cases',
            'items' => [
                [
                    'label' => 'Report Queue',
                    'route' => 'admin.reports.index',
                    'icon' => 'fa-solid fa-inbox',
                    'permission' => null,
                    'active_pattern' => 'admin.reports.index',
                ],
                [
                    'label' => 'My Cases',
                    'route' => 'admin.reports.my-cases',
                    'icon' => 'fa-solid fa-user-check',
                    'permission' => null,
                    'active_pattern' => 'admin.reports.my-cases',
                ],
                [
                    'label' => 'Barangay Cases',
                    'route' => 'admin.cases.index',
                    'icon' => 'fa-solid fa-scale-balanced',
                    'permission' => 'cases.view',
                    'active_pattern' => 'admin.cases.*',
                ],
                [
                    'label' => 'Case Parties',
                    'route' => 'admin.parties.index',
                    'icon' => 'fa-solid fa-users',
                    'permission' => 'cases.view',
                    'active_pattern' => 'admin.parties.*',
                ],
                [
                    'label' => 'Conciliation Hearings',
                    'route' => 'admin.hearings.index',
                    'icon' => 'fa-solid fa-calendar-days',
                    'permission' => 'cases.summons',
                    'active_pattern' => 'admin.hearings.*',
                ],
                [
                    'label' => 'Referrals & Routing',
                    'route' => 'admin.referrals.index',
                    'icon' => 'fa-solid fa-clipboard-list',
                    'permission' => null,
                    'active_pattern' => 'admin.referrals.*',
                ],
                [
                    'label' => 'Staff Map',
                    'route' => 'admin.map',
                    'icon' => 'fa-solid fa-map-location-dot',
                    'permission' => null,
                    'active_pattern' => 'admin.map*',
                ],
            ],
        ],
        [
            'section' => 'Operations',
            'items' => [
                [
                    'label' => 'Personnel',
                    'route' => 'admin.personnel.index',
                    'icon' => 'fa-solid fa-id-badge',
                    'permission' => 'personnel.manage',
                    'active_pattern' => 'admin.personnel.*',
                ],
                [
                    'label' => 'Positions',
                    'route' => 'admin.positions.index',
                    'icon' => 'fa-solid fa-user-tie',
                    'permission' => 'personnel.manage',
                    'active_pattern' => 'admin.positions.*',
                ],
                [
                    'label' => 'Signatories',
                    'route' => 'admin.signatories.edit',
                    'icon' => 'fa-solid fa-signature',
                    'permission' => 'personnel.manage',
                    'active_pattern' => 'admin.signatories.*',
                ],
                [
                    'label' => 'User Accounts',
                    'route' => 'admin.users.index',
                    'icon' => 'fa-solid fa-users-gear',
                    'permission' => 'users.manage',
                    'active_pattern' => 'admin.users.*',
                ],
                [
                    'label' => 'Partner Agencies',
                    'route' => 'admin.agencies.index',
                    'icon' => 'fa-solid fa-building-shield',
                    'permission' => 'agencies.manage',
                    'active_pattern' => 'admin.agencies.*',
                ],
            ],
        ],
        [
            'section' => 'Reports & Analytics',
            'items' => [
                [
                    'label' => 'Reports Center',
                    'route' => 'admin.reports-center.index',
                    'icon' => 'fa-solid fa-chart-column',
                    'permission' => 'reports_center.view',
                    'active_pattern' => 'admin.reports-center.*',
                ],
                [
                    'label' => 'Audit Log',
                    'route' => 'admin.audit-log.index',
                    'icon' => 'fa-solid fa-clock-rotate-left',
                    'permission' => 'audit.view',
                    'active_pattern' => 'admin.audit-log.*',
                ],
            ],
        ],
        [
            'section' => 'Administration',
            'items' => [
                [
                    'label' => 'Emergency Contacts',
                    'route' => 'admin.settings.emergency-contacts.index',
                    'icon' => 'fa-solid fa-phone',
                    'permission' => 'settings.manage',
                    'active_pattern' => 'admin.settings.emergency-contacts.*',
                ],
                [
                    'label' => 'General Settings',
                    'route' => 'admin.settings.general.edit',
                    'icon' => 'fa-solid fa-sliders',
                    'permission' => 'settings.manage',
                    'active_pattern' => 'admin.settings.general.*',
                ],
                [
                    'label' => 'Fiscal Years',
                    'route' => 'admin.fiscal-years.index',
                    'icon' => 'fa-solid fa-calendar-check',
                    'permission' => 'fiscal_year.manage',
                    'active_pattern' => 'admin.fiscal-years.*',
                ],
                [
                    'label' => 'Numbering Schemes',
                    'route' => 'admin.numbering.edit',
                    'icon' => 'fa-solid fa-hashtag',
                    'permission' => 'settings.manage',
                    'active_pattern' => 'admin.numbering.*',
                ],
                [
                    'label' => 'Response Templates',
                    'route' => 'admin.templates.index',
                    'icon' => 'fa-solid fa-file-lines',
                    'permission' => 'templates.manage',
                    'active_pattern' => 'admin.templates.*',
                ],
                [
                    'label' => 'Case Types',
                    'route' => 'admin.case-types.index',
                    'icon' => 'fa-solid fa-tags',
                    'permission' => 'settings.manage',
                    'active_pattern' => 'admin.case-types.*',
                ],
                [
                    'label' => 'Case Form Fields',
                    'route' => 'admin.case-fields.index',
                    'icon' => 'fa-solid fa-list-check',
                    'permission' => 'settings.manage',
                    'active_pattern' => 'admin.case-fields.*',
                ],
                [
                    'label' => 'Escalation Destinations',
                    'route' => 'admin.escalation-destinations.index',
                    'icon' => 'fa-solid fa-share-from-square',
                    'permission' => 'settings.manage',
                    'active_pattern' => 'admin.escalation-destinations.*',
                ],
                [
                    'label' => 'Backups',
                    'route' => 'admin.backups.index',
                    'icon' => 'fa-solid fa-database',
                    'permission' => 'backups.manage',
                    'active_pattern' => 'admin.backups.*',
                ],
                [
                    'label' => 'System Preferences',
                    'route' => 'admin.settings.index',
                    'icon' => 'fa-solid fa-gear',
                    'permission' => 'settings.manage',
                    'active_pattern' => 'admin.settings.index',
                ],
            ],
        ],
    ],
    'agency' => [
        [
            'section' => 'Agency Portal',
            'items' => [
                [
                    'label' => 'Assigned Referrals',
                    'route' => 'agency.referrals.index',
                    'icon' => 'fa-solid fa-inbox',
                    'active_pattern' => 'agency.referrals.*',
                ],
                [
                    'label' => 'Agency Team',
                    'route' => 'agency.team.index',
                    'icon' => 'fa-solid fa-users',
                    'active_pattern' => 'agency.team.*',
                    'office_admin_only' => true,
                ],
            ],
        ],
    ],
];
