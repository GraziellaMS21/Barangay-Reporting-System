<?php

// VERIFY with Lupon/city legal office
return [
    /*
     |--------------------------------------------------------------------------
     | Katarungang Pambarangay Statutory Periods & Thresholds
     |--------------------------------------------------------------------------
     | Local Government Code of 1991 (RA 7160), Chapter 7 - Katarungang Pambarangay
     */

    // Sec. 410(b): Mediation by Lupon Chairman / Punong Barangay within 15 working days
    'mediation_period_days' => 15,

    // Day 1 of the 15-working-day period is the date of the first scheduled hearing
    'day_one_is_hearing_date' => true,

    // ISO-8601 weekday numbers (1 = Monday ... 5 = Friday)
    'working_days' => [1, 2, 3, 4, 5],

    // Static/local holidays (YYYY-MM-DD); merged with settings('calendar.holidays', [])
    'holidays' => [],

    // Sec. 410(c): Conciliation by Pangkat ng Tagapagkasundo within 15 working days
    'conciliation_pangkat_days' => 15,

    // Sec. 410(c): Pangkat may extend for another period not exceeding 15 days
    'conciliation_extension_max_days' => 15,

    // Sec. 418: Repudiation of settlement within 10 days from the date thereof
    'repudiation_period_days' => 10,

    // Sec. 419: Transmittal of settlement/arbitration award to MTC within 5 days
    'court_transmittal_days' => 5,

    // Sec. 417: Execution of settlement by Lupon within 6 months from date of settlement
    'execution_period_months' => 6,

    // Presiding officer titles
    'presiding_officers' => [
        'punong_barangay' => 'Punong Barangay',
        'barangay_secretary' => 'Barangay Secretary',
        'lupon_member' => 'Lupon Member / Conciliator',
    ],
];
