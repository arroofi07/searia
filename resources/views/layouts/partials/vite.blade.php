{{-- Hanya load Vite jika build ada. Di cPanel tanpa npm build, pakai CSS/JS statis. --}}
@php
    $viteReady = file_exists(public_path('hot')) || file_exists(public_path('build/manifest.json'));
@endphp
@if ($viteReady)
    @vite($entrypoints ?? ['resources/css/app.css', 'resources/js/app.js'])
@endif
