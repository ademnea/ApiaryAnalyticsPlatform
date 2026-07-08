<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>AdEMNEA – Beehive Monitoring & Analytics Platform</title>

    {{-- Bootstrap 5 --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    {{-- Bootstrap Icons --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    {{-- Google Fonts --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&family=Space+Grotesk:wght@500;600;700&display=swap" rel="stylesheet">

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

            --font-display: 'Space Grotesk', sans-serif;
            --font-body:    'Inter', sans-serif;
        }

        *, *::before, *::after { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: var(--font-body);
            background: var(--clr-dark);
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='56' height='100'%3E%3Cpath d='M28 66L0 50V18L28 2l28 16v32L28 66zm0 0l28 16v18L28 116 0 100V82l28-16z' fill='none' stroke='%231A3A24' stroke-width='1'/%3E%3C/svg%3E");
            background-size: 56px 100px;
            color: #1a2e1f;
            overflow-x: hidden;
        }

        /* ========== NAVBAR ========== */
        .navbar-landing {
            background: rgba(13, 27, 18, 0.95);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(255,255,255,0.08);
            padding: 1rem 0;
        }

        .navbar-brand-landing {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            text-decoration: none;
        }

        .brand-hex {
            width: 42px;
            height: 42px;
            background: var(--clr-honey);
            clip-path: polygon(50% 0%, 100% 25%, 100% 75%, 50% 100%, 0% 75%, 0% 25%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.35rem;
        }

        .brand-text {
            display: flex;
            flex-direction: column;
            line-height: 1.2;
        }

        .brand-name {
            font-family: var(--font-display);
            font-size: 1.25rem;
            font-weight: 700;
            color: #fff;
            letter-spacing: 0.02em;
        }

        .brand-tagline {
            font-size: 0.65rem;
            color: var(--clr-honey-light);
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        .nav-link-landing {
            color: #c8e6d5 !important;
            font-size: 0.9rem;
            font-weight: 500;
            padding: 0.5rem 1rem !important;
            border-radius: 6px;
            transition: background 0.2s, color 0.2s;
        }

        .nav-link-landing:hover {
            color: #fff !important;
            background: rgba(255,255,255,0.08);
        }

        .btn-login {
            background: transparent;
            border: 1.5px solid var(--clr-forest-light);
            color: #c8e6d5;
            font-size: 0.88rem;
            font-weight: 600;
            padding: 0.5rem 1.5rem;
            border-radius: 8px;
            transition: all 0.2s;
        }

        .btn-login:hover {
            background: var(--clr-forest-mid);
            border-color: var(--clr-forest-mid);
            color: #fff;
        }

        .btn-register {
            background: linear-gradient(135deg, var(--clr-forest-mid) 0%, var(--clr-forest-light) 100%);
            border: none;
            color: #fff;
            font-size: 0.88rem;
            font-weight: 600;
            padding: 0.5rem 1.5rem;
            border-radius: 8px;
            transition: opacity 0.2s, transform 0.15s;
        }

        .btn-register:hover {
            opacity: 0.92;
            transform: translateY(-1px);
            color: #fff;
        }

        /* ========== HERO SECTION ========== */
        .hero {
            padding: 5rem 0 4rem;
            background: linear-gradient(180deg, rgba(13,27,18,1) 0%, rgba(27,67,50,0.85) 100%);
            position: relative;
        }

        .hero::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.1), transparent);
        }

        .hero-title {
            font-family: var(--font-display);
            font-size: 3rem;
            font-weight: 700;
            color: #fff;
            line-height: 1.15;
            margin-bottom: 1.25rem;
        }

        .hero-subtitle {
            font-size: 1.1rem;
            color: #b7d5c4;
            line-height: 1.7;
            margin-bottom: 2rem;
        }

        .hero-highlight {
            color: var(--clr-honey-light);
            font-weight: 600;
        }

        .btn-hero-primary {
            background: linear-gradient(135deg, var(--clr-forest-mid) 0%, var(--clr-forest-light) 100%);
            border: none;
            color: #fff;
            font-size: 1rem;
            font-weight: 600;
            padding: 0.85rem 2.5rem;
            border-radius: 10px;
            transition: opacity 0.2s, transform 0.15s;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-hero-primary:hover {
            opacity: 0.92;
            transform: translateY(-2px);
            color: #fff;
        }

        .btn-hero-secondary {
            background: transparent;
            border: 2px solid var(--clr-forest-light);
            color: #c8e6d5;
            font-size: 1rem;
            font-weight: 600;
            padding: 0.85rem 2.5rem;
            border-radius: 10px;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-hero-secondary:hover {
            background: var(--clr-forest-mid);
            border-color: var(--clr-forest-mid);
            color: #fff;
        }

        /* ========== FEATURES SECTION ========== */
        .features {
            padding: 4rem 0;
            background: var(--clr-canvas);
        }

        .section-title {
            font-family: var(--font-display);
            font-size: 2rem;
            font-weight: 700;
            color: var(--clr-dark);
            text-align: center;
            margin-bottom: 0.5rem;
        }

        .section-subtitle {
            font-size: 1rem;
            color: #6B7F74;
            text-align: center;
            margin-bottom: 3rem;
        }

        .feature-card {
            background: #fff;
            border: 1px solid #E0EDE5;
            border-radius: 12px;
            padding: 2rem 1.5rem;
            text-align: center;
            transition: box-shadow 0.3s, transform 0.3s;
            height: 100%;
        }

        .feature-card:hover {
            box-shadow: 0 8px 32px rgba(27,67,50,0.12);
            transform: translateY(-4px);
        }

        .feature-icon {
            width: 64px;
            height: 64px;
            margin: 0 auto 1.25rem;
            background: var(--clr-forest-pale);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.75rem;
            color: var(--clr-forest-mid);
        }

        .feature-title {
            font-family: var(--font-display);
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--clr-dark);
            margin-bottom: 0.75rem;
        }

        .feature-desc {
            font-size: 0.9rem;
            color: #6B7F74;
            line-height: 1.6;
        }

        /* ========== FOOTER ========== */
        .footer {
            background: var(--clr-dark);
            color: #8db8a0;
            padding: 2rem 0 1.5rem;
            border-top: 1px solid rgba(255,255,255,0.08);
        }

        .footer-logo {
            display: flex;
            align-items: center;
            gap: 0.6rem;
            margin-bottom: 1rem;
        }

        .footer-hex {
            width: 32px;
            height: 32px;
            background: var(--clr-honey);
            clip-path: polygon(50% 0%, 100% 25%, 100% 75%, 50% 100%, 0% 75%, 0% 25%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
        }

        .footer-brand-name {
            font-family: var(--font-display);
            font-size: 1rem;
            font-weight: 700;
            color: #fff;
        }

        .footer-text {
            font-size: 0.82rem;
            color: #8db8a0;
            line-height: 1.6;
        }

        .footer-heading {
            font-family: var(--font-display);
            font-size: 0.9rem;
            font-weight: 600;
            color: #c8e6d5;
            margin-bottom: 1rem;
        }

        .footer-link {
            display: block;
            font-size: 0.85rem;
            color: #8db8a0;
            text-decoration: none;
            margin-bottom: 0.5rem;
            transition: color 0.2s;
        }

        .footer-link:hover {
            color: var(--clr-honey-light);
        }

        .footer-bottom {
            border-top: 1px solid rgba(255,255,255,0.08);
            padding-top: 1.5rem;
            margin-top: 2rem;
            font-size: 0.78rem;
            color: #6B7F74;
            text-align: center;
        }

        @media (max-width: 768px) {
            .hero-title { font-size: 2rem; }
            .hero-subtitle { font-size: 1rem; }
            .section-title { font-size: 1.5rem; }
        }
    </style>
</head>
<body>

    {{-- ========== NAVBAR ========== --}}
    <nav class="navbar-landing">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center w-100">
                {{-- Brand --}}
                <a href="{{ url('/') }}" class="navbar-brand-landing">
                    <div class="brand-hex">🐝</div>
                    <div class="brand-text">
                        <span class="brand-name">AdEMNEA</span>
                        <span class="brand-tagline">Beehive Analytics</span>
                    </div>
                </a>

                {{-- Nav Links --}}
                <div class="d-none d-md-flex align-items-center gap-1">
                    <a href="#features" class="nav-link-landing">Features</a>
                    <a href="#about" class="nav-link-landing">About</a>
                    <a href="#contact" class="nav-link-landing">Contact</a>
                </div>

                {{-- Auth Buttons --}}
                <div class="d-flex align-items-center gap-2">
                    <a href="{{ route('admin.login') }}" class="btn btn-login">
                        <i class="bi bi-box-arrow-in-right me-1"></i> Login
                    </a>
                </div>
            </div>
        </div>
    </nav>

    {{-- ========== HERO SECTION ========== --}}
    <section class="hero">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-7">
                    <h1 class="hero-title">
                        Monitor, Analyze & Optimize Your <span class="hero-highlight">Beehive Operations</span>
                    </h1>
                    <p class="hero-subtitle">
                        Real-time IoT monitoring, AI-powered anomaly detection, and comprehensive analytics 
                        for modern apiculture. Maximize honey production, ensure colony health, and make 
                        data-driven decisions with AdEMNEA's intelligent beehive management system.
                    </p>
                    <div class="d-flex flex-wrap gap-3">
                        <a href="{{ route('admin.login') }}" class="btn btn-hero-primary">
                            <i class="bi bi-box-arrow-in-right"></i> Get Started
                        </a>
                        <a href="#features" class="btn btn-hero-secondary">
                            <i class="bi bi-info-circle"></i> Learn More
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ========== FEATURES SECTION ========== --}}
    <section id="features" class="features">
        <div class="container">
            <h2 class="section-title">Platform Features</h2>
            <p class="section-subtitle">
                Everything you need to manage, monitor, and optimize your beehive operations
            </p>

            <div class="row g-4">
                {{-- Feature 1 --}}
                <div class="col-md-6 col-lg-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="bi bi-activity"></i>
                        </div>
                        <h3 class="feature-title">Real-Time Monitoring</h3>
                        <p class="feature-desc">
                            Track temperature, humidity, hive weight, CO₂ levels, and audio/video feeds 
                            in real-time from IoT-enabled sensors deployed across your apiaries.
                        </p>
                    </div>
                </div>

                {{-- Feature 2 --}}
                <div class="col-md-6 col-lg-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="bi bi-graph-up-arrow"></i>
                        </div>
                        <h3 class="feature-title">Anomaly Detection</h3>
                        <p class="feature-desc">
                            AI-powered algorithms automatically detect unusual patterns and alert you 
                            to potential threats before they impact colony health or production.
                        </p>
                    </div>
                </div>

                {{-- Feature 3 --}}
                <div class="col-md-6 col-lg-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="bi bi-geo-alt-fill"></i>
                        </div>
                        <h3 class="feature-title">Apiary Management</h3>
                        <p class="feature-desc">
                            Register and manage multiple apiaries, hives, and devices with geospatial 
                            mapping, inspection logs, and harvest tracking all in one place.
                        </p>
                    </div>
                </div>

                {{-- Feature 4 --}}
                <div class="col-md-6 col-lg-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="bi bi-file-earmark-bar-graph"></i>
                        </div>
                        <h3 class="feature-title">Advanced Reporting</h3>
                        <p class="feature-desc">
                            Generate comprehensive reports on colony health, honey production trends, 
                            sensor analytics, and operational KPIs for data-driven insights.
                        </p>
                    </div>
                </div>

                {{-- Feature 5 --}}
                <div class="col-md-6 col-lg-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="bi bi-people-fill"></i>
                        </div>
                        <h3 class="feature-title">Multi-Role Access</h3>
                        <p class="feature-desc">
                            Role-based permissions for admins, farmers, field officers, and researchers 
                            ensure secure, granular access control across your organization.
                        </p>
                    </div>
                </div>

                {{-- Feature 6 --}}
                <div class="col-md-6 col-lg-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="bi bi-bell-fill"></i>
                        </div>
                        <h3 class="feature-title">Smart Alerts</h3>
                        <p class="feature-desc">
                            Configure custom threshold-based alerts for critical events. Receive 
                            notifications via email, SMS, or push notifications on mobile devices.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ========== FOOTER ========== --}}
    <footer class="footer">
        <div class="container">
            <div class="row">
                {{-- Brand Column --}}
                <div class="col-md-4 mb-4 mb-md-0">
                    <div class="footer-logo">
                        <div class="footer-hex">🐝</div>
                        <span class="footer-brand-name">AdEMNEA</span>
                    </div>
                    <p class="footer-text">
                        Advanced beehive monitoring and analytics platform powered by IoT, 
                        AI, and modern web technologies. Built for sustainable apiculture 
                        and honey production optimization.
                    </p>
                </div>

                {{-- Quick Links --}}
                <div class="col-md-4 mb-4 mb-md-0">
                    <h4 class="footer-heading">Quick Links</h4>
                    <a href="{{ route('admin.login') }}" class="footer-link">Login</a>
                    <a href="#features" class="footer-link">Features</a>
                    <a href="#about" class="footer-link">About AdEMNEA</a>
                </div>

                {{-- Contact --}}
                <div class="col-md-4">
                    <h4 class="footer-heading">Contact</h4>
                    <p class="footer-text mb-2">
                        <i class="bi bi-envelope me-2"></i> info@ademnea.ac.ug
                    </p>
                    <p class="footer-text mb-2">
                        <i class="bi bi-telephone me-2"></i> +256 XXX XXXXXX
                    </p>
                    <p class="footer-text">
                        <i class="bi bi-geo-alt me-2"></i> Kampala, Uganda
                    </p>
                </div>
            </div>

            <div class="footer-bottom">
                AdEMNEA &copy; {{ date('Y') }} &nbsp;|&nbsp; Funded by Norad · NORHED II Programme
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
