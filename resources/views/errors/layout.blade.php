<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="robots" content="noindex, nofollow">
    <meta name="theme-color" content="#07151d">
    <title>@yield('title', 'Terjadi kesalahan') · SeaRIA</title>
    <link rel="icon" href="{{ url('/brand/favicon') }}" type="image/svg+xml">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=poppins:400,500,600,700" rel="stylesheet">
    <style>
        :root {
            --ink: #12343f;
            --paper: #f5f8fa;
            --signal: #ff6a3d;
            --muted: #5b6f78;
            --live: #ff6a3d;
        }
        * { box-sizing: border-box; }
        html, body { margin: 0; min-height: 100%; }
        body {
            font-family: Poppins, ui-sans-serif, system-ui, sans-serif;
            color: var(--ink);
            background: var(--paper);
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }
        .lanes {
            position: fixed;
            inset: 0;
            pointer-events: none;
            opacity: .2;
            background-image: repeating-linear-gradient(90deg, transparent 0, transparent calc(12.5% - 2px), #5ce1ff calc(12.5% - 2px), #5ce1ff 12.5%);
        }
        main {
            position: relative;
            z-index: 1;
            width: min(40rem, calc(100% - 2rem));
            margin: auto;
            padding: 3rem 0 4rem;
            text-align: center;
        }
        .brand {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1.75rem;
            padding: 1rem 1.25rem;
            background: var(--paper);
            border: 3px solid var(--ink);
        }
        .brand img { height: 4.5rem; width: auto; display: block; }
        .code {
            margin: 0;
            font-size: clamp(4.5rem, 16vw, 7rem);
            font-weight: 700;
            letter-spacing: -.06em;
            line-height: 1;
            color: transparent;
            -webkit-text-stroke: 2px var(--live);
        }
        h1 {
            margin: .75rem 0 0;
            padding: 0 .25rem;
            font-size: clamp(1.35rem, 6vw, 2rem);
            font-weight: 600;
            letter-spacing: -.03em;
            line-height: 1.2;
            color: var(--ink);
        }
        .lead {
            margin: .85rem auto 0;
            max-width: 32rem;
            font-size: 1.05rem;
            line-height: 1.65;
            color: var(--muted);
        }
        .actions {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: .75rem;
            margin-top: 2rem;
        }
        .btn, .btn-ghost {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 3rem;
            padding: .75rem 1.35rem;
            border: 2px solid var(--ink);
            font-size: 1rem;
            font-weight: 700;
            text-decoration: none;
        }
        .btn { background: var(--signal); color: #fff; border-radius: 999px; }
        .btn:hover { background: #e85a30; }
        .btn-ghost { background: transparent; color: var(--ink); border-radius: 999px; }
        .btn-ghost:hover { background: #0a2e39; color: #fff; }
        .links {
            margin-top: 1.5rem;
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: .9rem 1.25rem;
            font-size: .95rem;
        }
        .links a { color: var(--ink); text-decoration: none; border-bottom: 2px solid var(--signal); }
        .links a:hover { text-decoration: underline; }
        footer {
            position: relative;
            z-index: 1;
            padding: 1.25rem;
            text-align: center;
            color: var(--muted);
            font-size: .8rem;
        }
    </style>
</head>
<body>
    <div class="lanes" aria-hidden="true"></div>
    <main>
        <a class="brand" href="{{ url('/') }}">
            <img src="{{ url('/brand/logo') }}" alt="Aquatic SeaRIA" width="160" height="104" onerror="this.parentNode.style.display='none'">
        </a>
        <p class="code">@yield('code')</p>
        <h1>@yield('heading')</h1>
        <p class="lead">@yield('message')</p>
        <div class="actions">
            <a class="btn" href="{{ url('/') }}">Kembali ke beranda</a>
            @yield('extra-actions')
        </div>
        <nav class="links" aria-label="Tautan bantuan">
            <a href="{{ url('/daftar') }}">Daftar lomba</a>
            <a href="{{ url('/archive') }}">Arsip hasil</a>
            <a href="{{ url('/search/athletes') }}">Cari atlet</a>
            <a href="{{ url('/login') }}">Masuk panitia</a>
        </nav>
    </main>
    <footer>Aquatic SeaRIA · Sistem informasi kejuaraan renang</footer>
</body>
</html>
