<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="theme-color" content="#0a2e39">
        <title>@yield('title', 'Masuk') — SeaRIA</title>
        @include('layouts.partials.favicon')
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=poppins:400,500,600,700" rel="stylesheet">
        @include('layouts.partials.vite', ['entrypoints' => ['resources/css/app.css', 'resources/js/app.js']])
        <link rel="stylesheet" href="{{ \App\Support\PublicAssets::cssUrl() }}">
        <link rel="stylesheet" href="{{ rtrim(request()->getBasePath(), '/') }}/css/public.css?v={{ is_file(public_path('css/public.css')) ? filemtime(public_path('css/public.css')) : '1' }}">
    </head>
    <body class="deck">
        <main class="gate">
            <a class="back" href="{{ route('home') }}">Ke beranda</a>
            <div class="panel">
                @yield('content')
            </div>
        </main>
    </body>
</html>
