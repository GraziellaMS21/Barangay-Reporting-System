<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>@yield('title', 'Document') | Official Barangay Record</title>
    <style>
        body {
            font-family: Arial, Helvetica, sans-serif;
            color: #0f172a;
            line-height: 1.5;
            margin: 0;
            padding: 2rem;
            background: #fff;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #0f172a;
            padding-bottom: 1rem;
            margin-bottom: 1.5rem;
        }
        .header h1 {
            font-size: 1.25rem;
            margin: 0;
            text-transform: uppercase;
        }
        .header p {
            margin: 0.25rem 0 0;
            font-size: 0.875rem;
            color: #475569;
        }
        .content {
            margin-bottom: 2rem;
        }
        @media print {
            body { padding: 0; }
            .no-print { display: none; }
        }
    </style>
    @stack('styles')
</head>
<body>
    <div class="header">
        <p>Republic of the Philippines · City of Zamboanga</p>
        <h1>BARANGAY CALARIAN</h1>
        <p>Office of the Lupong Tagapamayapa / Barangay Government</p>
    </div>

    <div class="content">
        @yield('content')
    </div>

    @yield('signatories')

    @stack('scripts')
</body>
</html>
