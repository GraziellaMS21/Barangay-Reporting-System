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
    ],
];
