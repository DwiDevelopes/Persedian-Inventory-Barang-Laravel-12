<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <script>
            (function () {
                var t = null; try { t = localStorage.getItem('theme'); } catch (e) {}
                document.documentElement.classList.toggle('dark', t !== 'light');
                document.documentElement.setAttribute('data-theme', t === 'light' ? 'light' : 'dark');
            })();
        </script>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>Masuk - Stocku</title>
        <link rel="icon" type="image/png" href="{{ asset('assets/img/stocku-logo.png') }}">
        <link rel="stylesheet" href="{{ asset('assets/css/login.css') }}?v=1">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="login-body font-sans antialiased">
        <div class="login-bg" style="background-image: url('{{ asset('assets/img/login-bg.jpg') }}')"></div>
        <main class="login-wrap">
            <div class="login-card">
                <div class="login-brand">
                    <span class="logo-tile"><img src="{{ asset('assets/img/stocku-logo.png') }}" alt="Stocku"></span>
                    <div>
                        <h1>Stocku</h1>
                        <p>Sistem inventaris gudang</p>
                    </div>
                </div>
                {{ $slot }}
            </div>
            <p class="login-foot">&copy; {{ date('Y') }} Stocku</p>
        </main>
        <button type="button" id="theme-toggle" aria-label="Ganti tema" title="Ganti tema"
            class="fixed top-4 right-4 inline-flex h-9 w-9 items-center justify-center rounded-md border border-gray-300 bg-white text-gray-600 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700">
            <svg class="h-4 w-4 hidden dark:block" viewBox="0 0 20 20" fill="currentColor"><path d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.46 4.95l.71.7a1 1 0 001.41-1.41l-.7-.71a1 1 0 00-1.42 1.42zm2.12-10.6a1 1 0 010 1.41l-.7.71a1 1 0 11-1.42-1.42l.71-.7a1 1 0 011.41 0zM17 11a1 1 0 100-2h-1a1 1 0 100 2h1zm-7 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.05 6.46a1 1 0 10-1.41-1.41l-.71.7a1 1 0 101.42 1.42l.7-.71zm-.7 7.78a1 1 0 001.41 1.42l.71-.71a1 1 0 00-1.42-1.41l-.7.7zM4 10a1 1 0 01-1 1H2a1 1 0 110-2h1a1 1 0 011 1z"/></svg>
            <svg class="h-4 w-4 dark:hidden" viewBox="0 0 20 20" fill="currentColor"><path d="M17.29 13.29A8 8 0 016.71 2.71a8 8 0 1010.58 10.58z"/></svg>
        </button>
        <script>
            document.getElementById('theme-toggle').addEventListener('click', function () {
                var light = document.documentElement.classList.contains('dark');
                document.documentElement.classList.toggle('dark', !light);
                document.documentElement.setAttribute('data-theme', light ? 'light' : 'dark');
                try { localStorage.setItem('theme', light ? 'light' : 'dark'); } catch (e) {}
            });
        </script>
    </body>
</html>
