<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ $title ?? config('app.name', 'SurveyApp') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen bg-accent-50">
            <!-- Simple header -->
            <header class="bg-white border-b border-gray-100">
                <div class="max-w-3xl mx-auto py-4 px-4 sm:px-6">
                    <a href="{{ route('home') }}" class="text-lg font-bold text-primary-600">
                        📋 SurveyApp
                    </a>
                </div>
            </header>

            <!-- Content -->
            <main class="py-8">
                <div class="max-w-3xl mx-auto px-4 sm:px-6">
                    {{ $slot }}
                </div>
            </main>
        </div>

        @stack('scripts')
    </body>
</html>
