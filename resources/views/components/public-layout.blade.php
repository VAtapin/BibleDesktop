<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $title ?? config('app.name', 'Bible Desktop') }}</title>
        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/brand/favicon-32.png" type="image/png">
        @vite(['resources/js/app.ts'])
    </head>
    <body class="public-page">
        <main class="public-shell">
            <a class="public-brand" href="/">
                <img src="/brand/bible-desktop-mark.png" alt="">
                <span>Bible Desktop</span>
            </a>
            {{ $slot }}
        </main>
    </body>
</html>
