<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }} - {{ __('Service Unavailable') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased">
    <div class="min-h-screen bg-gray-100">
        <!-- Основной контент -->
        <div class="max-w-7xl mx-auto py-10 sm:px-6 lg:px-8">
            <div class="bg-white shadow rounded-lg p-8 sm:p-12">
                <div class="text-center">
                    <!-- Иконка для 503 -->
                    <div class="text-8xl sm:text-9xl mb-4">
                        🔧
                    </div>

                    <!-- Код ошибки -->
                    <div class="text-8xl sm:text-9xl font-black text-gray-900 leading-none mb-2 tracking-tight">
                        503
                    </div>

                    <!-- Заголовок -->
                    <h1 class="text-2xl sm:text-3xl font-semibold text-gray-900 mb-4">
                        {{ __('Service Unavailable') }}
                    </h1>

                    <!-- Сообщение для 503 -->
                    <div class="text-sm text-gray-600 max-w-md mx-auto mb-8">
                        <p>
                            {{ __('The server is currently unable to handle the request due to temporary overload or maintenance. Please try again later.') }}
                        </p>
                    </div>

                    <!-- Поиск (как в твоём стиле) -->
                    <form action="{{ url('/search') }}" method="GET" class="max-w-md mx-auto mb-8">
                        <div class="flex gap-2">
                            <input
                                type="text"
                                name="q"
                                placeholder="{{ __('Search...') }}"
                                class="flex-1 rounded-lg border-gray-300 focus:border-gray-900 focus:ring-gray-900 text-sm"
                                aria-label="{{ __('Find') }}"
                            >
                            <button type="submit" class="px-4 py-2 bg-gray-900 text-white rounded-lg hover:bg-gray-800 transition text-sm whitespace-nowrap">
                                🔍
                            </button>
                        </div>
                    </form>

                    <!-- Кнопки действий -->
                    <div class="flex flex-wrap items-center justify-center gap-3">
                        <a href="{{ url('/') }}" class="px-4 py-2 bg-gray-900 text-white rounded-lg hover:bg-gray-800 transition text-sm">
                            🏠 {{ __('Dashboard') }}
                        </a>
                        <a href="javascript:location.reload()" class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition text-sm text-gray-700">
                            🔄 {{ __('Try Again') }}
                        </a>
                        <a href="javascript:history.back()" class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition text-sm text-gray-700">
                            ← {{ __('Back') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>