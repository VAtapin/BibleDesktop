@php
    $appUser = auth()->user();
    $appConfig = [
        'user' => $appUser ? [
            'id' => $appUser->id,
            'name' => $appUser->name,
            'email' => $appUser->email,
            'dashboard_url' => route('dashboard'),
            'logout_url' => route('logout'),
        ] : null,
        'auth' => [
            'login_url' => route('login'),
            'register_url' => route('register'),
        ],
        'footer_pages' => $footerPages ?? [],
    ];
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ config('app.name', 'Bible Desktop') }}</title>
        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/brand/favicon-32.png" type="image/png">
        <link rel="apple-touch-icon" href="/brand/favicon-192.png">
        <script>
            window.BibleDesktop = @json($appConfig);
        </script>
        @vite(['resources/js/app.ts'])
    </head>
    <body>
        <div id="app"></div>
    </body>
</html>
