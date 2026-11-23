<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Summons {{ $summons->summons_number }}</title>
    <style>
        body { font-family: serif; margin: 40px; color: #111; }
        h1 { text-align: center; font-size: 18px; text-transform: uppercase; }
        .meta { margin: 20px 0; }
        .meta p { margin: 4px 0; }
        .body { margin: 24px 0; line-height: 1.6; }
        .sig { margin-top: 60px; }
        .sig-line { border-top: 1px solid #111; width: 240px; margin-top: 40px; }
    </style>
</head>
<body>
    <h1>Summons</h1>
    <p style="text-align:center;">Case No. {{ $case->case_number }}</p>

    <div class="meta">
        <p><strong>Summons No.:</strong> {{ $summons->summons_number }}</p>
        <p><strong>Issued on:</strong> {{ optional($summons->issued_on)->format('F j, Y') }}</p>
        <p><strong>Hearing:</strong> {{ optional($summons->hearing_at)->format('F j, Y g:i A') }}</p>
        <p><strong>Venue:</strong> {{ $summons->venue }}</p>
        <p><strong>Respondent:</strong> {{ optional(optional($summons->respondentCaseParty)->party)->full_name }}</p>
    </div>

    <div class="body">
        <p>You are hereby summoned to appear before the Barangay Conciliation Panel on the date, time, and venue stated above in connection with the above-captioned case. Failure to appear may result in further action as provided by law.</p>
    </div>

    <div class="sig">
        <div class="sig-line"></div>
        <p>Issued by: {{ optional($summons->issuedBy)->name }}</p>
    </div>
</body>
</html>
