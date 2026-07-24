<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Inova Hub')</title>
    <style>
        :root {
            --bg: #0f1419;
            --surface: #1a222c;
            --text: #eef2f6;
            --muted: #9aa8b5;
            --accent: #2bb673;
            --danger: #e35d6a;
            --radius: 12px;
            --space: clamp(1rem, 3vw, 1.5rem);
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100dvh;
            font-family: "Segoe UI", system-ui, sans-serif;
            background: radial-gradient(1200px 600px at 10% -10%, #1e3a2f 0%, var(--bg) 55%);
            color: var(--text);
        }
        a { color: var(--accent); }
        .shell {
            width: min(100% - 2rem, 28rem);
            margin: 0 auto;
            padding: calc(var(--space) * 2) 0;
        }
        .brand {
            font-size: clamp(1.6rem, 5vw, 2rem);
            font-weight: 700;
            letter-spacing: -0.02em;
            margin: 0 0 0.35rem;
        }
        .sub { color: var(--muted); margin: 0 0 var(--space); }
        .card {
            background: color-mix(in srgb, var(--surface) 92%, white 8%);
            border: 1px solid color-mix(in srgb, var(--text) 12%, transparent);
            border-radius: var(--radius);
            padding: var(--space);
        }
        label { display: block; font-size: 0.9rem; margin: 0.75rem 0 0.35rem; color: var(--muted); }
        input {
            width: 100%;
            min-height: 44px;
            border-radius: 10px;
            border: 1px solid color-mix(in srgb, var(--text) 18%, transparent);
            background: #0c1116;
            color: var(--text);
            padding: 0.65rem 0.8rem;
            font-size: 1rem;
        }
        button, .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 44px;
            width: 100%;
            margin-top: 1rem;
            border: 0;
            border-radius: 10px;
            background: var(--accent);
            color: #062015;
            font-weight: 700;
            font-size: 1rem;
            cursor: pointer;
            text-decoration: none;
        }
        .errors { color: var(--danger); margin: 0 0 1rem; padding: 0; list-style: none; }
        .errors li { margin: 0.25rem 0; }
        .footer-link { margin-top: 1rem; text-align: center; color: var(--muted); }
        .topbar {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
            justify-content: space-between;
            align-items: center;
            margin-bottom: var(--space);
        }
        .topbar form { margin: 0; width: auto; }
        .topbar button { width: auto; margin: 0; padding: 0 1rem; background: transparent; color: var(--muted); border: 1px solid color-mix(in srgb, var(--text) 20%, transparent); }
        @media (min-width: 768px) {
            .shell { width: min(100% - 2rem, 36rem); }
        }
    </style>
</head>
<body>
    <main class="shell">
        @yield('content')
    </main>
</body>
</html>
