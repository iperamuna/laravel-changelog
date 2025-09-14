<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

    <!-- Styles / Scripts -->
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/changelog.css', 'resources/js/app.js'])
    @endif
    <link rel="stylesheet" href="{{ asset('vendor/iperamuna/laravel-changelog/app.css') }}">
    <script type="module" src="{{ asset('vendor/iperamuna/laravel-changelog/app2.js') }}"></script>
</head>
<body x-cloak x-data="{darkMode: $persist(false)}" :class="{'dark': darkMode === true }" class="antialiased">
    {{ $slot }}
    <div class="flex justify-end text-sm m-4">
        <span class="text-gray-700 font-stretch-50% dark:text-gray-100">
            Version: 2.3.3,
        </span>
        <span class="ml-2 text-gray-900 dark:text-gray-100">
            Powered by
            <a href="#" class="font-bold text-blue-600 dark:text-blue-500 hover:underline">
                Laravel Changelog
            </a>
        </span>
    </div>
</body>
</html>
