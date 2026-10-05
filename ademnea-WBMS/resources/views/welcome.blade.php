@extends('layouts.public')

@section('title', 'Beehive Monitoring')
@section('main-class', '')

@push('styles')
    <style>
        .band { padding: 4.5rem 0; }
        .band-title { margin-bottom: 0.5rem; font-size: 1.9rem; font-weight: 700; }
        .band-lead { max-width: 40rem; margin-bottom: 2.5rem; font-size: 1.05rem; color: var(--clr-text-muted); }

        /* ---- Hero ---- */
        .hero {
            padding: 5rem 0;
            color: var(--clr-on-dark);
            background-color: var(--clr-dark);
            background-image:
                linear-gradient(180deg, rgba(13, 27, 18, 0.55) 0%, rgba(27, 67, 50, 0.92) 100%),
                url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='56' height='100'%3E%3Cpath d='M28 66L0 50V18L28 2l28 16v32L28 66zm0 0l28 16v18L28 116 0 100V82l28-16z' fill='none' stroke='%23244A31' stroke-width='1'/%3E%3C/svg%3E");
            background-size: auto, 56px 100px;
        }
        .hero-eyebrow { margin-bottom: 1rem; font-size: 0.85rem; font-weight: 600; letter-spacing: 0.08em; text-transform: uppercase; color: var(--clr-honey-light); }
        .hero h1 { margin-bottom: 1.25rem; font-size: clamp(2.1rem, 4.5vw, 3.2rem); font-weight: 700; line-height: 1.12; color: #fff; }
        .hero h1 span { color: var(--clr-honey-light); }
        .hero-lead { max-width: 36rem; margin-bottom: 2rem; font-size: 1.15rem; color: var(--clr-on-dark); }
        .hero-actions { display: flex; flex-wrap: wrap; gap: 0.85rem; }
        .hero-actions .btn { display: inline-flex; align-items: center; gap: 0.5rem; min-height: 3rem; padding: 0.7rem 1.6rem; font-size: 1rem; }
        .btn-hero { background: var(--clr-honey-light); border: 2px solid var(--clr-honey-light); color: var(--clr-dark); }
        .btn-hero:hover { background: #FFD95A; border-color: #FFD95A; color: var(--clr-dark); }
        .btn-hero-outline { background: transparent; border: 2px solid rgba(255, 255, 255, 0.7); color: #fff; }
        .btn-hero-outline:hover { background: rgba(255, 255, 255, 0.12); border-color: #fff; color: #fff; }

        /* What the sensors measure: an illustration, not live data. */
        .hero-sensors { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; max-width: 24rem; margin-left: auto; }
        .hero-sensor {
            padding: 1.25rem; border-radius: 14px; border: 1px solid rgba(255, 255, 255, 0.16);
            background: rgba(255, 255, 255, 0.07); color: #fff;
        }
        .hero-sensor i { display: block; margin-bottom: 0.6rem; font-size: 1.75rem; color: var(--clr-honey-light); }
        .hero-sensor strong { display: block; font-family: var(--font-display); font-size: 1.05rem; }
        .hero-sensor span { font-size: 0.875rem; color: var(--clr-on-dark-muted); }

        /* ---- What it does ---- */
        .feature { height: 100%; padding: 1.5rem; border: 1px solid var(--clr-border); border-radius: 12px; background: var(--clr-card); }
        .feature-icon {
            display: flex; align-items: center; justify-content: center; width: 3rem; height: 3rem; margin-bottom: 1rem;
            border-radius: 10px; font-size: 1.4rem; background: var(--clr-forest-pale); color: var(--clr-forest);
        }
        .feature h3 { margin-bottom: 0.4rem; font-size: 1.1rem; font-weight: 600; }
        .feature p { margin: 0; color: var(--clr-text-muted); }

        /* ---- Explore the project ---- */
        .band-explore { background: #EEF4EF; }
        .explore-card {
            display: flex; align-items: flex-start; gap: 1rem; height: 100%; padding: 1.25rem 1.35rem;
            border: 1px solid var(--clr-border); border-radius: 12px; background: var(--clr-card);
            color: var(--clr-text); text-decoration: none; transition: border-color 0.15s, box-shadow 0.15s;
        }
        .explore-card:hover { border-color: var(--clr-forest-light); box-shadow: 0 6px 22px rgba(27, 67, 50, 0.12); color: var(--clr-text); }
        .explore-card > i { font-size: 1.6rem; color: var(--clr-forest-mid); }
        .explore-card strong { display: block; font-family: var(--font-display); font-size: 1.05rem; }
        .explore-card span { color: var(--clr-text-muted); font-size: 0.95rem; }
        .explore-card .bi-arrow-right { margin-left: auto; font-size: 1.1rem; align-self: center; }

        /* ---- About ---- */
        .about p { max-width: 44rem; font-size: 1.05rem; }

        @media (max-width: 991.98px) {
            .band { padding: 3.25rem 0; }
            .hero { padding: 3.5rem 0; }
            .hero-sensors { margin: 2.5rem 0 0; max-width: none; }
        }
    </style>
@endpush

@section('content')

    <section class="hero" aria-labelledby="hero-title">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-7">
                    <p class="hero-eyebrow">AdEMNEA · NORHED II</p>
                    <h1 id="hero-title">Know how every hive is doing, <span>without opening it</span></h1>
                    <p class="hero-lead">
                        Sensors in the hive report temperature, humidity, weight and CO₂ around the clock.
                        The platform checks each reading, raises an alert when something looks wrong,
                        and keeps the records researchers and beekeepers need.
                    </p>
                    <div class="hero-actions">
                        @auth
                            <a href="{{ route('admin.dashboard') }}" class="btn btn-hero"><i class="bi bi-speedometer2" aria-hidden="true"></i>Open dashboard</a>
                        @else
                            <a href="{{ route('admin.login') }}" class="btn btn-hero"><i class="bi bi-box-arrow-in-right" aria-hidden="true"></i>Sign in</a>
                        @endauth
                        <a href="#features" class="btn btn-hero-outline">See what it does<i class="bi bi-arrow-down" aria-hidden="true"></i></a>
                    </div>
                </div>

                <div class="col-lg-5">
                    <ul class="hero-sensors list-unstyled mb-0" aria-label="What the sensors measure">
                        <li class="hero-sensor"><i class="bi bi-thermometer-half" aria-hidden="true"></i><strong>Temperature</strong><span>Brood, honey super, outside</span></li>
                        <li class="hero-sensor"><i class="bi bi-droplet-half" aria-hidden="true"></i><strong>Humidity</strong><span>Brood, honey super, outside</span></li>
                        <li class="hero-sensor"><i class="bi bi-speedometer" aria-hidden="true"></i><strong>Weight</strong><span>The whole hive, in kilograms</span></li>
                        <li class="hero-sensor"><i class="bi bi-wind" aria-hidden="true"></i><strong>CO₂</strong><span>Air inside the hive</span></li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <section class="band" id="features" aria-labelledby="features-title">
        <div class="container">
            <h2 class="band-title" id="features-title">What the platform does</h2>
            <p class="band-lead">From the sensor in the hive to the person who needs to act.</p>

            <div class="row g-4">
                @foreach([
                    ['bi-activity', 'Live hive monitoring', 'Readings from every hive as they arrive, with charts over hours, days and weeks, plus photos, audio and video from the apiary.'],
                    ['bi-shield-exclamation', 'Condition alerts', 'Each reading is checked on arrival. Impossible values, stuck sensors and unusual changes open an incident and notify the right people.'],
                    ['bi-cpu', 'Device health', 'Battery, signal, storage and connectivity for every unit in the field, so a failing device is found before its data is missed.'],
                    ['bi-geo-alt', 'Apiary and hive records', 'Apiaries, hives, inspections and harvests in one register, with a map of where each hive stands.'],
                    ['bi-file-earmark-bar-graph', 'Reports', 'Colony health, honey production and sensor trends, ready for research and for planning the season.'],
                    ['bi-phone', 'For farmers, on their phone', 'Farmers see their own hives and receive alerts in the mobile app, by push, with email and SMS for the support team.'],
                ] as [$icon, $title, $text])
                    <div class="col-md-6 col-lg-4">
                        <div class="feature">
                            <div class="feature-icon"><i class="bi {{ $icon }}" aria-hidden="true"></i></div>
                            <h3>{{ $title }}</h3>
                            <p>{{ $text }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <section class="band band-explore" id="explore" aria-labelledby="explore-title">
        <div class="container">
            <h2 class="band-title" id="explore-title">Explore the project</h2>
            <p class="band-lead">The work, the people behind it, and how to take part.</p>

            <div class="row g-3">
                @foreach([
                    ['bi-diagram-3', 'Work packages', 'What the project is doing, and how the work is organised.', route('public.work-packages.index')],
                    ['bi-people', 'Team', 'The researchers, engineers and students behind AdEMNEA.', route('public.team.index')],
                    ['bi-images', 'Gallery', 'Photos from the apiaries, the lab and the field.', route('public.gallery.index')],
                    ['bi-mortarboard', 'Scholarships', 'Study opportunities offered through the project.', route('public.scholarships.index')],
                ] as [$icon, $title, $text, $url])
                    <div class="col-md-6">
                        <a href="{{ $url }}" class="explore-card">
                            <i class="bi {{ $icon }}" aria-hidden="true"></i>
                            <span><strong>{{ $title }}</strong><span>{{ $text }}</span></span>
                            <i class="bi bi-arrow-right" aria-hidden="true"></i>
                        </a>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <section class="band about" id="about" aria-labelledby="about-title">
        <div class="container">
            <h2 class="band-title" id="about-title">About AdEMNEA</h2>
            <p>
                AdEMNEA is a research project funded by Norad under the NORHED II programme and hosted at Makerere University.
                This platform is its beehive monitoring system: it collects data from instrumented hives and turns it into
                something a beekeeper, an extension officer or a researcher can act on.
            </p>
            <p class="mb-0">
                Questions or ideas? <a href="{{ route('public.feedback.create') }}">Send us feedback</a>
                or write to <a href="mailto:info@ademnea.ac.ug">info@ademnea.ac.ug</a>.
            </p>
        </div>
    </section>

@endsection
