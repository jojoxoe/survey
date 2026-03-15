<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'SurveyApp') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-accent-50">
    <div class="min-h-screen flex flex-col">
        <!-- Header -->
        <header class="bg-white border-b border-gray-100">
            <div class="max-w-3xl mx-auto px-4 sm:px-6 py-4 flex items-center justify-between">
                <span class="text-lg font-bold text-primary-600">📋 SurveyApp</span>
                <div class="space-x-4">
                    @auth
                        <a href="{{ route('dashboard') }}" class="text-sm text-gray-600 hover:text-primary-600 transition">Dashboard</a>
                    @else
                        <a href="{{ route('login') }}" class="text-sm text-gray-600 hover:text-primary-600 transition">Log in</a>
                        <a href="{{ route('register') }}" class="btn-primary btn-sm">Register</a>
                    @endauth
                </div>
            </div>
        </header>

        <!-- Hero -->
        <main class="flex-1 flex items-center justify-center px-4">
            <div class="max-w-md w-full text-center py-16">
                <h1 class="text-3xl font-bold text-gray-800 mb-3">Create & Share Surveys</h1>
                <p class="text-gray-500 mb-8">Build surveys with multiple question types. Share with a code or link. Collect responses instantly.</p>

                <!-- Code Entry -->
                <div class="card">
                    <h2 class="text-sm font-medium text-gray-600 mb-4">Have a survey code?</h2>
                    <form action="{{ route('survey.lookup') }}" method="POST" class="flex gap-3">
                        @csrf
                        <input
                            type="text"
                            name="code"
                            placeholder="Enter 8-digit code"
                            maxlength="8"
                            class="input-field flex-1 uppercase text-center tracking-widest"
                            required
                        >
                        <button type="submit" class="btn-primary">Go</button>
                    </form>
                    @error('code')
                        <p class="text-red-500 text-xs mt-2">{{ $message }}</p>
                    @enderror
                </div>

                @guest
                    <p class="text-sm text-gray-400 mt-8">Want to create your own surveys? <a href="{{ route('register') }}" class="text-primary-500 hover:text-primary-600">Sign up free</a></p>
                @endguest
            </div>
        </main>
    </div>
</body>
</html>
