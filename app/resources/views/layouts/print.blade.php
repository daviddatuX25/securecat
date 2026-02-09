<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Admission slip' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        @media print {
            .no-print { display: none !important; }
            body { background: white; }
        }
    </style>
</head>
<body class="min-h-screen bg-slate-50 p-6 print:bg-white">
    @yield('content')
    <div class="no-print mt-6">
        <button type="button" onclick="window.print()" class="btn btn-primary">Print</button>
        <a href="javascript:window.close()" class="btn btn-ghost ml-2">Close</a>
    </div>
</body>
</html>
