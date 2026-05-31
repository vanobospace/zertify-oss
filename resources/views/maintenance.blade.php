<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow,noarchive">
    <title>Maintenance</title>
    <style>
        :root {
            color-scheme: dark;
            --bg-start: #10183d;
            --bg-mid: #17244d;
            --bg-end: #29195e;
            --surface: rgba(17, 26, 60, 0.64);
            --surface-border: rgba(178, 196, 255, 0.12);
            --surface-shadow: 0 28px 72px rgba(5, 10, 30, 0.42);
            --badge-bg: rgba(255, 255, 255, 0.03);
            --badge-border: rgba(190, 204, 255, 0.14);
            --badge-text: rgba(211, 220, 255, 0.72);
            --text-main: #f1f5ff;
            --text-muted: rgba(221, 228, 255, 0.76);
            --text-soft: rgba(204, 214, 255, 0.62);
            --headline-gradient-start: #f3f6ff;
            --headline-gradient-end: #9bdcff;
        }

        * {
            box-sizing: border-box;
        }

        html,
        body {
            min-height: 100%;
        }

        body {
            margin: 0;
            display: grid;
            place-items: center;
            overflow: hidden;
            font-family: "Inter", "Instrument Sans", system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            color: var(--text-main);
            background:
                radial-gradient(circle at 18% 22%, rgba(76, 122, 255, 0.18), transparent 30%),
                radial-gradient(circle at 82% 22%, rgba(142, 84, 255, 0.14), transparent 28%),
                linear-gradient(135deg, var(--bg-start) 0%, var(--bg-mid) 52%, var(--bg-end) 100%);
            background-size: 130% 130%;
            animation: backgroundShift 22s ease-in-out infinite;
        }

        body::before,
        body::after {
            content: "";
            position: fixed;
            inset: auto;
            width: 24rem;
            height: 24rem;
            border-radius: 999px;
            filter: blur(84px);
            opacity: 0.2;
            pointer-events: none;
        }

        body::before {
            top: -10rem;
            left: -8rem;
            background: rgba(82, 127, 255, 0.26);
        }

        body::after {
            right: -9rem;
            bottom: -10rem;
            background: rgba(164, 98, 255, 0.18);
        }

        main {
            width: min(100%, 43rem);
            padding: 1.5rem;
            position: relative;
            z-index: 1;
        }

        .card {
            position: relative;
            overflow: hidden;
            padding: clamp(2rem, 4vw, 3.15rem);
            border-radius: 1.6rem;
            border: 1px solid var(--surface-border);
            background:
                linear-gradient(180deg, rgba(255, 255, 255, 0.04), rgba(255, 255, 255, 0.01)),
                var(--surface);
            box-shadow: var(--surface-shadow);
            backdrop-filter: blur(18px);
            -webkit-backdrop-filter: blur(18px);
            text-align: center;
        }

        .card::before {
            content: "";
            position: absolute;
            inset: 0;
            border-radius: inherit;
            background: linear-gradient(180deg, rgba(255, 255, 255, 0.05), transparent 36%);
            pointer-events: none;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 2rem;
            padding: 0.52rem 0.95rem;
            border-radius: 999px;
            border: 1px solid var(--badge-border);
            background: var(--badge-bg);
            color: var(--badge-text);
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: 0.14em;
            text-transform: uppercase;
        }

        h1 {
            margin: 1.25rem 0 0;
            font-size: clamp(2.4rem, 6vw, 4rem);
            line-height: 1;
            letter-spacing: -0.06em;
            font-weight: 800;
            color: transparent;
            background: linear-gradient(180deg, var(--headline-gradient-start), var(--headline-gradient-end));
            background-clip: text;
            -webkit-background-clip: text;
        }

        p {
            margin: 0;
        }

        .lead {
            max-width: 34rem;
            margin: 0.9rem auto 0;
            color: var(--text-muted);
            font-size: clamp(1rem, 2vw, 1.12rem);
            line-height: 1.55;
        }

        .foot {
            margin-top: 0.75rem;
            color: var(--text-soft);
            font-size: 0.92rem;
            line-height: 1.5;
        }

        @keyframes backgroundShift {
            0% {
                background-position: 0% 50%;
            }

            50% {
                background-position: 100% 50%;
            }

            100% {
                background-position: 0% 50%;
            }
        }

        @media (max-width: 640px) {
            main {
                padding: 1rem;
            }

            .card {
                border-radius: 1.35rem;
                padding: 1.7rem 1.35rem;
            }

            .badge {
                min-height: 2rem;
                padding-inline: 0.9rem;
                letter-spacing: 0.13em;
            }
        }
    </style>
</head>
<body>
    <main>
        <section class="card" aria-labelledby="maintenance-title">
            <div class="badge">Maintenance Mode</div>
            <h1 id="maintenance-title">We&rsquo;re on a lunch break 🍜</h1>
            <p class="lead">Sorry, this development project is temporarily unavailable.</p>
            <p class="foot">Please check back a little later.</p>
        </section>
    </main>
</body>
</html>
