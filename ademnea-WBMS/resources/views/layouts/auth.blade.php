<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <title>@yield('title', 'AdEMNEA – Login')</title>

    {{-- Bootstrap 5 --}}
    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH"
          crossorigin="anonymous" />
    {{-- Bootstrap Icons --}}
    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css" />

    <style>
        /* ============================================================
           AdEMNEA Design System — Auth Layer
           Palette:
             Deep Forest Night  #0B1E12   → page & sidebar bg
             Forest Green       #1A3A24   → card border accent
             Leaf Green         #2D6A4F   → primary actions
             Honey Amber        #F59E0B   → active / highlight
             Pale Honey         #FEF3C7   → text on dark
             Ash White          #F1F8F4   → light input bg
           Rationale:
             Green family = nature / beekeeping / sustainability (project core)
             Amber / Honey = bees / hive product (signature visual anchor)
             Dark bg = professional depth, readability, reduced eye strain on data dashboards
        ============================================================ */

        :root {
            /* Use the shared AdEMNEA palette from layouts.app for consistency */
            --bee-dark:    #0D1B12; /* near-black */
            --bee-forest:  #1B4332; /* forest */
            --bee-green:   #2D6A4F; /* forest mid */
            --bee-mid:     #40916C; /* forest light */
            --bee-amber:   #D4A017; /* honey */
            --bee-amber-d: #F8C93A; /* honey light */
            --bee-pale:    #F8FAF7; /* canvas */
            --bee-mint:    #F8FAF7; /* canvas / mint */
            --bee-text:    #1a2e1f; /* content text */
        }

        *, *::before, *::after { box-sizing: border-box; }

        body {
            margin: 0;
            min-height: 100vh;
            background-color: var(--bee-dark);
            background-image:
                /* Honeycomb SVG pattern — subtle texture referencing hive structure */
                url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='56' height='100'%3E%3Cpath d='M28 66L0 50V18L28 2l28 16v32L28 66zm0 0l28 16v18L28 116 0 100V82l28-16z' fill='none' stroke='%231A3A24' stroke-width='1'/%3E%3C/svg%3E");
            background-size: 56px 100px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
        }

        .auth-card {
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 24px 64px rgba(0,0,0,.55), 0 0 0 1px rgba(255,255,255,.04);
            width: 100%;
            max-width: 440px;
            overflow: hidden;
        }

        .auth-header {
            background: linear-gradient(135deg, var(--bee-dark) 0%, var(--bee-forest) 100%);
            padding: 2rem 2rem 1.5rem;
            text-align: center;
            position: relative;
        }

        /* Hexagon logo mark */
        .hex-logo {
            width: 64px;
            height: 64px;
            background: var(--bee-amber);
            clip-path: polygon(50% 0%, 100% 25%, 100% 75%, 50% 100%, 0% 75%, 0% 25%);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            font-size: 1.75rem;
        }

        .auth-brand-name {
            color: #ffffff;
            font-size: 1.5rem;
            font-weight: 700;
            letter-spacing: .04em;
            margin: 0;
        }

        .auth-brand-sub {
            color: var(--bee-pale);
            font-size: .78rem;
            letter-spacing: .08em;
            text-transform: uppercase;
            opacity: .85;
            margin-top: .25rem;
        }

        .auth-body {
            padding: 2rem 2rem 1.75rem;
        }

        .auth-body h2 {
            font-size: 1.15rem;
            font-weight: 600;
            color: #1a2e1a;
            margin-bottom: 1.5rem;
        }

        /* Input styling */
        .form-control:focus {
            border-color: var(--bee-green);
            box-shadow: 0 0 0 .2rem rgba(45,106,79,.18);
        }

        .form-label {
            font-size: .84rem;
            font-weight: 600;
            color: #374151;
        }

        .input-group-text {
            background: var(--bee-mint);
            border-color: #dee2e6;
            color: var(--bee-green);
        }

        /* Primary button */
        .btn-bee-primary {
            background: linear-gradient(135deg, var(--bee-green) 0%, var(--bee-mid) 100%);
            color: #fff;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            letter-spacing: .02em;
            padding: .65rem 1.5rem;
            width: 100%;
            transition: opacity .2s, transform .15s;
        }
        .btn-bee-primary:hover {
            opacity: .92;
            transform: translateY(-1px);
            color: #fff;
        }
        .btn-bee-primary:active { transform: translateY(0); }

        .auth-footer {
            background: var(--bee-mint);
            border-top: 1px solid #e5e7eb;
            padding: .9rem 2rem;
            font-size: .78rem;
            color: #6b7280;
            text-align: center;
        }

        .alert-bee-error {
            background: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: 8px;
            color: #991b1b;
            font-size: .85rem;
        }
    </style>
</head>
<body>

    <div class="auth-card">
        {{-- Brand header --}}
        <div class="auth-header">
            <div class="hex-logo">🐝</div>
            <p class="auth-brand-name">AdEMNEA</p>
            <p class="auth-brand-sub">Beehive Monitoring System</p>
        </div>

        {{-- Page content --}}
        <div class="auth-body">
            @yield('content')
        </div>

        <div class="auth-footer">
            AdEMNEA &copy; {{ date('Y') }} &nbsp;|&nbsp;
            Funded by Norad · NORHED II Programme
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
            integrity="sha384-YvpcrYf0tY3lHB60NNkmXc4s9bIOgUxi8T/jzmB5VbGkADeiGBNQYZLMt7Ui4KQ"
            crossorigin="anonymous"></script>
    @stack('scripts')
</body>
</html>