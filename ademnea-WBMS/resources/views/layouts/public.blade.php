<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>@yield('title', 'AdEMNEA')</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Space+Grotesk:wght@500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --clr-forest:       #1B4332;
            --clr-forest-mid:   #2D6A4F;
            --clr-forest-light: #40916C;
            --clr-forest-pale:  #D8F3DC;
            --clr-honey:        #D4A017;
            --clr-honey-light:  #F8C93A;
            --clr-dark:         #0D1B12;
            --clr-canvas:       #F8FAF7;
            --clr-text:         #1f2d24;
            --clr-muted:        #64776a;
            --font-display: 'Space Grotesk', sans-serif;
            --font-body:    'Inter', sans-serif;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: var(--font-body);
            background: var(--clr-canvas);
            color: var(--clr-text);
        }

        .public-topbar {
            background: rgba(13, 27, 18, 0.96);
            border-bottom: 1px solid rgba(255,255,255,0.08);
            backdrop-filter: blur(10px);
            position: sticky;
            top: 0;
            z-index: 1020;
        }

        .public-brand {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            text-decoration: none;
            color: #fff;
        }

        .public-brand-hex {
            width: 42px;
            height: 42px;
            background: var(--clr-honey);
            clip-path: polygon(50% 0%, 100% 25%, 100% 75%, 50% 100%, 0% 75%, 0% 25%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.35rem;
        }

        .public-brand-name {
            font-family: var(--font-display);
            font-size: 1.15rem;
            font-weight: 700;
            letter-spacing: 0.02em;
            line-height: 1.1;
        }

        .public-brand-tag {
            display: block;
            font-size: 0.62rem;
            color: var(--clr-honey-light);
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .public-nav-link {
            color: #dfeee5 !important;
            text-decoration: none;
            padding: 0.55rem 0.9rem;
            border-radius: 8px;
            font-size: 0.9rem;
            font-weight: 500;
            transition: background 0.2s ease, color 0.2s ease;
        }

        .public-nav-link:hover {
            background: rgba(255,255,255,0.06);
            color: #fff !important;
        }

        .public-login-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            background: transparent;
            border: 1.5px solid var(--clr-forest-light);
            border-radius: 8px;
            color: #dfeee5;
            padding: 0.55rem 1.2rem;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.85rem;
        }

        .public-login-btn:hover {
            background: rgba(64, 145, 108, 0.12);
            color: #fff;
        }

        .public-page {
            min-height: calc(100vh - 78px);
        }

        .public-card {
            background: #fff;
            border: 1px solid rgba(27, 67, 50, 0.08);
            box-shadow: 0 8px 24px rgba(13, 27, 18, 0.04);
            border-radius: 16px;
        }

        .public-section-title {
            font-family: var(--font-display);
            color: var(--clr-dark);
            font-weight: 700;
            margin-bottom: 0.75rem;
        }

        .public-muted {
            color: var(--clr-muted);
        }

        .public-footer {
            background: var(--clr-dark);
            color: #b7d5c4;
            padding: 2rem 0 1.25rem;
            border-top: 1px solid rgba(255,255,255,0.08);
            margin-top: 3rem;
        }

        .public-footer a {
            color: #e7f5eb;
            text-decoration: none;
        }

        .public-footer a:hover {
            color: var(--clr-honey-light);
        }
    </style>
</head>
<body>
    <nav class="public-topbar">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center py-3">
                <a href="{{ url('/') }}" class="public-brand">
                    <div class="public-brand-hex">🐝</div>
                    <div>
                        <span class="public-brand-name">AdEMNEA</span>
                        <span class="public-brand-tag">Beehive Analytics</span>
                    </div>
                </a>

                <div class="d-none d-md-flex align-items-center gap-1">
                    <a href="{{ route('public.work-packages.index') }}" class="public-nav-link">Work Packages</a>
                    <a href="{{ route('public.team.index') }}" class="public-nav-link">Team</a>
                    <a href="{{ route('public.gallery.index') }}" class="public-nav-link">Gallery</a>
                    <a href="{{ route('public.publications.index') }}" class="public-nav-link">Publications</a>
                    <a href="{{ route('public.events.index') }}" class="public-nav-link">Events</a>
                    <a href="{{ route('public.newsletters.index') }}" class="public-nav-link">Newsletters</a>
                    <a href="{{ route('public.scholarships.index') }}" class="public-nav-link">Scholarships</a>
                </div>

                <a href="{{ route('admin.login') }}" class="public-login-btn">
                    <i class="bi bi-box-arrow-in-right"></i> Login
                </a>
            </div>
        </div>
    </nav>

    <main class="public-page">
        @yield('content')
    </main>

    <footer class="public-footer">
        <div class="container">
            <div class="row g-4">
                <div class="col-md-5">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <div class="public-brand-hex" style="width:32px;height:32px;font-size:1rem;">🐝</div>
                        <strong class="text-white">AdEMNEA</strong>
                    </div>
                    <p class="mb-0" style="max-width:420px; color:#b7d5c4; line-height:1.7;">
                        Supporting sustainable apiculture through modern monitoring, research, and community engagement.
                    </p>
                </div>
                <div class="col-md-2">
                    <div class="fw-semibold text-white mb-2">Explore</div>
                    <div class="d-grid gap-2">
                        <a href="{{ route('public.work-packages.index') }}">Work Packages</a>
                        <a href="{{ route('public.publications.index') }}">Publications</a>
                        <a href="{{ route('public.events.index') }}">Events</a>
                        <a href="{{ route('public.newsletters.index') }}">Newsletters</a> 
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="fw-semibold text-white mb-2">About</div>
                    <div class="d-grid gap-2">
                        <a href="{{ route('public.team.index') }}">Team</a>
                        <a href="{{ route('public.gallery.index') }}">Gallery</a>
                        <a href="{{ route('public.scholarships.index') }}">Scholarships</a>
                    </div>
                </div>
                <div class="col-md-3 text-md-end">
                    <div class="fw-semibold text-white mb-2">Contact</div>
                    <div class="d-grid gap-2">
                        <a href="{{ url('/') }}">Home</a>
                        <a href="{{ route('admin.login') }}">Admin Login</a>
                    </div>
                </div>
            </div>
            <div class="text-center mt-4 pt-3" style="border-top:1px solid rgba(255,255,255,0.08); color:#8db8a0; font-size:0.8rem;">
                © {{ date('Y') }} AdEMNEA. All rights reserved.
            </div>
        </div>
    </footer>
</body>
</html>
