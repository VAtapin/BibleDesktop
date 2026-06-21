<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ config('app.name', 'Bible Desktop') }}</title>
        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/brand/favicon-32.png" type="image/png">
        <link rel="apple-touch-icon" href="/brand/favicon-192.png">
        @vite(['resources/js/app.ts'])
    </head>
    <body>
        <div id="app"></div>
    </body>
</html>
