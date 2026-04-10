<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title inertia>{{ config('app.name', 'Laravel') }}</title>

        @php
            $faviconPath = \App\Models\SiteSetting::get('site_favicon_path');
            $faviconUrl  = $faviconPath ? \Illuminate\Support\Facades\Storage::disk('public')->url($faviconPath) : null;
        @endphp
        @if($faviconUrl)
            <link rel="icon" type="image/png" href="{{ $faviconUrl }}">
        @endif

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @routes
        @vite(['resources/js/app.js', "resources/js/Pages/{$page['component']}.vue"])
        @inertiaHead
    </head>
    <body class="font-sans antialiased" data-theme="{{ $page['props']['auth']['user']['theme'] ?? 'light' }}">
        <script>
            (function() {
                var t = localStorage.getItem('theme');
                if (t) document.body.setAttribute('data-theme', t);
            })();
        </script>
        @inertia
    </body>
</html>
