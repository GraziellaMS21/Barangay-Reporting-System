<?php

return [
    'types' => [
        'summons' => [
            'label' => 'Summons',
            'default_roles' => ['Prepared by', 'Presided by'],
        ],
        'case_endorsement' => [
            'label' => 'Case Endorsement Letter',
            'default_roles' => ['Prepared by', 'Approved by'],
        ],
        'referral_letter' => [
            'label' => 'External Referral Letter',
            'default_roles' => ['Prepared by', 'Approved by'],
        ],
        'settlement_agreement' => [
            'label' => 'Amicable Settlement Agreement',
            'default_roles' => ['Attested by', 'Approved by'],
        ],
        'certificate_to_file_action' => [
            'label' => 'Certification to File Action (CFA)',
            'default_roles' => ['Prepared by', 'Approved by'],
        ],
        'blotter_extract' => [
            'label' => 'Blotter Certified Extract',
            'default_roles' => ['Prepared by', 'Approved by'],
        ],
        'complaint' => [
            'label' => 'Formal Complaint (KP Form 1)',
            'default_roles' => ['Prepared by', 'Approved by'],
        ],
        'conciliation_notice' => [
            'label' => 'Notice of Conciliation Hearing (KP Form 8/9)',
            'default_roles' => ['Prepared by', 'Approved by'],
        ],
    ],
];
