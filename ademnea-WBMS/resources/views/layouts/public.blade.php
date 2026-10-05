<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Beehive Monitoring') — AdEMNEA</title>
    <meta name="description" content="@yield('description', 'AdEMNEA beehive monitoring: sensors in the hive, a dashboard for the people who look after it.')">

    {{-- Served locally, like the admin layout, so the site still works on a poor connection. --}}
    <link rel="stylesheet" href="{{ asset('bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('bootstrap-icons.min.css') }}">
    {{-- Web fonts are an enhancement; the stacks below fall back to system fonts. --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Space+Grotesk:wght@500;600;700&display=swap" rel="stylesheet">

    <style>
        /* Same palette as layouts/app.blade.php. Text colours here are checked for 4.5:1 contrast. */
        :root {
            --clr-forest:       #1B4332;
            --clr-forest-mid:   #2D6A4F;
            --clr-forest-light: #40916C;
            --clr-forest-pale:  #D8F3DC;
            --clr-honey:        #D4A017;
            --clr-honey-light:  #F8C93A;
            --clr-dark:         #0D1B12;
            --clr-canvas:       #F8FAF7;
            --clr-card:         #FFFFFF;
            --clr-border:       #DCE8E0;
            --clr-text:         #16261B;
            --clr-text-muted:   #4F6157;
            --clr-on-dark:      #E6F2EA;
            --clr-on-dark-muted:#B4CFC0;

            --font-display: 'Space Grotesk', system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif;
            --font-body:    'Inter', system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif;
        }

        html { scroll-behavior: smooth; scroll-padding-top: 5rem; }

        body {
            margin: 0; min-height: 100vh; display: flex; flex-direction: column;
            font-family: var(--font-body); font-size: 1rem; line-height: 1.6;
            color: var(--clr-text); background: var(--clr-canvas);
        }

        h1, h2, h3, h4 { font-family: var(--font-display); color: var(--clr-text); }
        a { color: var(--clr-forest-mid); }
        a:hover { color: var(--clr-forest); }
        .text-muted { color: var(--clr-text-muted) !important; }

        :focus-visible { outline: 3px solid #F8C93A; outline-offset: 2px; border-radius: 4px; }
        .site-main :focus-visible { outline-color: #0B5ED7; }

        .skip-link {
            position: absolute; left: 0.75rem; top: -4rem; z-index: 1000;
            padding: 0.6rem 1rem; border-radius: 8px; font-weight: 600;
            background: var(--clr-honey-light); color: var(--clr-dark); text-decoration: none;
        }
        .skip-link:focus { top: 0.75rem; }

        /* ---- Header ---- */
        .site-header { position: sticky; top: 0; z-index: 100; background: var(--clr-dark); border-bottom: 1px solid rgba(255, 255, 255, 0.1); }
        .site-header .navbar { padding: 0.65rem 0; }
        .site-brand { display: flex; align-items: center; gap: 0.7rem; text-decoration: none; }
        .site-brand-mark {
            display: flex; align-items: center; justify-content: center; width: 40px; height: 40px; font-size: 1.25rem;
            background: var(--clr-honey); clip-path: polygon(50% 0%, 100% 25%, 100% 75%, 50% 100%, 0% 75%, 0% 25%);
        }
        .site-brand-name { display: block; font-family: var(--font-display); font-size: 1.2rem; font-weight: 700; line-height: 1.15; color: #fff; }
        .site-brand-tagline { display: block; font-size: 0.72rem; letter-spacing: 0.08em; text-transform: uppercase; color: var(--clr-honey-light); }

        .site-nav .nav-link {
            padding: 0.55rem 0.9rem; border-radius: 8px; font-size: 0.95rem; font-weight: 500; color: var(--clr-on-dark);
        }
        .site-nav .nav-link:hover { color: #fff; background: rgba(255, 255, 255, 0.1); }
        .site-nav .nav-link[aria-current="page"] { color: #fff; background: rgba(255, 255, 255, 0.14); }
        .site-header .navbar-toggler { padding: 0.5rem 0.65rem; border: 1px solid rgba(255, 255, 255, 0.35); color: #fff; }
        .site-header .navbar-toggler i { font-size: 1.4rem; line-height: 1; }

        .btn-signin {
            display: inline-flex; align-items: center; gap: 0.45rem; min-height: 2.75rem; padding: 0.5rem 1.2rem;
            border: 0; border-radius: 8px; font-weight: 600; background: var(--clr-honey-light); color: var(--clr-dark);
        }
        .btn-signin:hover { background: #FFD95A; color: var(--clr-dark); }

        @media (max-width: 991.98px) {
            .site-nav { padding: 0.75rem 0 0.5rem; }
            .site-nav .nav-link { padding: 0.75rem 0.9rem; }
            .btn-signin { width: 100%; justify-content: center; margin-top: 0.5rem; }
        }

        /* ---- Shared components, matching the admin's look ---- */
        .site-main { flex: 1; }
        .btn { border-radius: 8px; font-weight: 600; }
        .btn-primary { background: var(--clr-forest-mid); border-color: var(--clr-forest-mid); }
        .btn-primary:hover, .btn-primary:focus { background: var(--clr-forest); border-color: var(--clr-forest); }
        .btn-outline-primary { color: var(--clr-forest-mid); border-color: var(--clr-forest-mid); }
        .btn-outline-primary:hover { background: var(--clr-forest-mid); border-color: var(--clr-forest-mid); color: #fff; }
        .card { border: 1px solid var(--clr-border); border-radius: 12px; }
        .form-control:focus, .form-select:focus { border-color: var(--clr-forest-light); box-shadow: 0 0 0 0.2rem rgba(64, 145, 108, 0.25); }

        /* ---- Footer ---- */
        .site-footer { background: var(--clr-dark); color: var(--clr-on-dark-muted); padding: 3rem 0 1.5rem; font-size: 0.95rem; }
        .site-footer h2 { margin-bottom: 0.9rem; font-size: 1rem; font-weight: 600; color: #fff; }
        .site-footer p { margin-bottom: 0.6rem; }
        .site-footer ul { margin: 0; padding: 0; list-style: none; }
        .site-footer li { margin-bottom: 0.15rem; }
        .site-footer a { display: inline-block; padding: 0.3rem 0; color: var(--clr-on-dark); text-decoration: none; }
        .site-footer a:hover { color: var(--clr-honey-light); text-decoration: underline; }
        .site-footer-bottom { margin-top: 2rem; padding-top: 1.25rem; border-top: 1px solid rgba(255, 255, 255, 0.12); font-size: 0.875rem; }

        @media (prefers-reduced-motion: reduce) {
            html { scroll-behavior: auto; }
            *, *::before, *::after { transition: none !important; animation: none !important; }
        }
    </style>

    @stack('styles')
</head>
<body>
    <a href="#main" class="skip-link">Skip to content</a>

    @php
        $siteLinks = [
            ['Work Packages', route('public.work-packages.index'), 'public.work-packages.*'],
            ['Team', route('public.team.index'), 'public.team.*'],
            ['Gallery', route('public.gallery.index'), 'public.gallery.*'],
            ['Publications', route('public.publications.index'), 'public.publications.*'],
            ['Events', route('public.events.index'), 'public.events.*'],
            ['Newsletters', route('public.newsletters.index'), 'public.newsletters.*'],
            ['Scholarships', route('public.scholarships.index'), 'public.scholarships.*'],
            ['Feedback', route('public.feedback.create'), 'public.feedback.*'],
        ];
    @endphp

    <header class="site-header">
        <nav class="navbar navbar-expand-lg" aria-label="Main">
            <div class="container">
                <a href="{{ route('home') }}" class="site-brand" @if(request()->routeIs('home')) aria-current="page" @endif>
                    <span class="site-brand-mark" aria-hidden="true">🐝</span>
                    <span>
                        <span class="site-brand-name">AdEMNEA</span>
                        <span class="site-brand-tagline">Beehive Monitoring</span>
                    </span>
                </a>

                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#site-nav"
                        aria-controls="site-nav" aria-expanded="false" aria-label="Menu">
                    <i class="bi bi-list" aria-hidden="true"></i>
                </button>

                <div class="collapse navbar-collapse" id="site-nav">
                    <ul class="navbar-nav site-nav ms-auto me-lg-3">
                        @foreach($siteLinks as [$label, $url, $pattern])
                            <li class="nav-item">
                                <a class="nav-link" href="{{ $url }}" @if(request()->routeIs($pattern)) aria-current="page" @endif>{{ $label }}</a>
                            </li>
                        @endforeach
                    </ul>

                    @auth
                        <a href="{{ route('admin.dashboard') }}" class="btn btn-signin"><i class="bi bi-speedometer2" aria-hidden="true"></i>Open dashboard</a>
                    @else
                        <a href="{{ route('admin.login') }}" class="btn btn-signin"><i class="bi bi-box-arrow-in-right" aria-hidden="true"></i>Sign in</a>
                    @endauth
                </div>
            </div>
        </nav>
    </header>

    {{-- A page that draws its own full-width bands (the welcome page) empties this class. --}}
    <main id="main" class="site-main @yield('main-class', 'container py-5')">
        @yield('content')
    </main>

    <footer class="site-footer" id="contact">
        <div class="container">
            <div class="row g-4">
                <div class="col-md-5">
                    <h2>AdEMNEA</h2>
                    <p>Beehive monitoring for research and beekeeping: sensors in the hive, and a dashboard for the people who look after it.</p>
                    <p>Funded by Norad under the NORHED II programme. Hosted at Makerere University.</p>
                </div>
                <div class="col-6 col-md-3 offset-md-1">
                    <h2>Explore</h2>
                    <ul>
                        @foreach($siteLinks as [$label, $url])
                            <li><a href="{{ $url }}">{{ $label }}</a></li>
                        @endforeach
                    </ul>
                </div>
                <div class="col-6 col-md-3">
                    <h2>Contact</h2>
                    <ul>
                        <li><a href="mailto:info@ademnea.ac.ug"><i class="bi bi-envelope me-2" aria-hidden="true"></i>info@ademnea.ac.ug</a></li>
                        <li><a href="{{ route('public.feedback.create') }}"><i class="bi bi-chat-dots me-2" aria-hidden="true"></i>Send feedback</a></li>
                        <li class="pt-1"><i class="bi bi-geo-alt me-2" aria-hidden="true"></i>Kampala, Uganda</li>
                    </ul>
                </div>
            </div>

            <div class="site-footer-bottom">AdEMNEA &copy; {{ date('Y') }}</div>
        </div>
    </footer>

    <script src="{{ asset('bootstrap.bundle.min.js') }}"></script>
    @stack('scripts')
</body>
</html>
