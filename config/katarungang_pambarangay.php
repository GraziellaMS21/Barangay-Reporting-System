<?php

// VERIFY against current KP rules / city legal office
return [
    /*
     |--------------------------------------------------------------------------
     | Katarungang Pambarangay Statutory Periods & Thresholds
     |--------------------------------------------------------------------------
     | Local Government Code of 1991 (RA 7160), Chapter 7 - Katarungang Pambarangay
     */

    // Sec. 410(b): Mediation by Lupon Chairman / Punong Barangay within 15 days
    'mediation_period_days' => 15,

    // Sec. 410(c): Conciliation by Pangkat ng Tagapagkasundo within 15 days
    'conciliation_pangkat_days' => 15,

    // Sec. 410(c): Pangkat may extend for another period not exceeding 15 days
    'conciliation_extension_max_days' => 15,

    // Sec. 418: Repudiation of settlement within 10 days from the date thereof
    'repudiation_period_days' => 10,

    // Sec. 419: Transmittal of settlement/arbitration award to MTC within 5 days
    'court_transmittal_days' => 5,

    // Sec. 417: Execution of settlement by Lupon within 6 months from date of settlement
    'execution_period_months' => 6,

    // Maximum attempts to serve summons before substituted or declaring unserved
    'summons_max_attempts' => 3,

    // Presiding officer titles
    'presiding_officers' => [
        'punong_barangay' => 'Punong Barangay',
        'barangay_secretary' => 'Barangay Secretary',
        'lupon_member' => 'Lupon Member / Conciliator',
    ],
];
