<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Wejha') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

    <!-- Styles / Scripts -->
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
    
    <style>
        /* For RTL support when Arabic is selected */
        html[lang="ar"] body {
            direction: rtl;
            text-align: right;
        }
    </style>
</head>
<body>
    <header>
        @include('components.language-switcher')
    </header>
    
    <main>
        @yield('content')
    </main>
    
    <footer>
        <p>&copy; {{ date('Y') }} Wejha</p>
    </footer>
</body>
</html> 