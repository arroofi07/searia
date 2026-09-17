<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="robots" content="noindex, nofollow">
    <meta name="theme-color" content="#00A3E0">
    <title>@yield('title', 'Terjadi kesalahan') · SeaRIA</title>
    <link rel="icon" href="{{ url('/brand/favicon') }}" type="image/svg+xml">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet">
    <style>
        :root {
            --ink: #e2e8f0;
            --muted: #94a3b8;
            --teal: #5eead4;
            --btn: #f8fafc;
            --btn-text: #042f2e;
        }
        * { box-sizing: border-box; }
        html, body { margin: 0; min-height: 100%; }
        body {
            font-family: "Instrument Sans", ui-sans-serif, system-ui, sans-serif;
            color: var(--ink);
            background: #020617;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }
        .lanes {
            position: fixed;
            inset: 0;
            pointer-events: none;
            opacity: .07;
            background-image: repeating-linear-gradient(90deg, transparent 0, transparent 11.5%, #fff 11.5%, #fff 12%);
        }
        .orb {
            position: fixed;
            width: 22rem;
            height: 22rem;
            border-radius: 999px;
            filter: blur(64px);
            pointer-events: none;
        }
        .orb-a { top: -6rem; left: -5rem; background: rgba(45, 212, 191, .22); }
        .orb-b { right: -4rem; top: 8rem; background: rgba(14, 165, 233, .18); }
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
            background: #fff;
            border-radius: 1.5rem;
        }
        .brand img { height: 4.5rem; width: auto; display: block; }
        .code {
            margin: 0;
            font-size: clamp(4.5rem, 16vw, 7rem);
            font-weight: 700;
            letter-spacing: -.06em;
            line-height: 1;
            color: rgba(94, 234, 212, .22);
        }
        h1 {
            margin: .75rem 0 0;
            padding: 0 .25rem;
            font-size: clamp(1.35rem, 6vw, 2rem);
            font-weight: 600;
            letter-spacing: -.03em;
            line-height: 1.2;
            color: #fff;
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
            border-radius: 999px;
            font-size: 1rem;
            font-weight: 600;
            text-decoration: none;
        }
        .btn { background: var(--btn); color: var(--btn-text); }
        .btn:hover { background: #ccfbf1; }
        .btn-ghost {
            border: 1px solid rgba(255,255,255,.2);
            color: #fff;
            background: rgba(255,255,255,.06);
        }
        .btn-ghost:hover { background: rgba(255,255,255,.12); }
        .links {
            margin-top: 1.5rem;
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: .9rem 1.25rem;
            font-size: .95rem;
        }
        .links a { color: #99f6e4; text-decoration: none; }
        .links a:hover { text-decoration: underline; }
        footer {
            position: relative;
            z-index: 1;
            padding: 1.25rem;
            text-align: center;
            color: #64748b;
            font-size: .8rem;
        }
    </style>
</head>
<body>
    <div class="lanes" aria-hidden="true"></div>
    <div class="orb orb-a" aria-hidden="true"></div>
    <div class="orb orb-b" aria-hidden="true"></div>
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
