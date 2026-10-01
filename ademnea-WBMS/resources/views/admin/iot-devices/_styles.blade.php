{{-- Shared styles for the IoT device pages (registry, team devices, detail, forms). --}}
@once
    @push('styles')
        <style>
            .health-pill {
                display: inline-flex;
                align-items: center;
                gap: 0.35rem;
                font-size: 0.8rem;
                font-weight: 600;
                text-decoration: none;
            }
            .health-pill .dot { width: 8px; height: 8px; border-radius: 50%; flex: 0 0 auto; }
            .health-pill.is-online  { color: #2D6A4F; }
            .health-pill.is-warning { color: #856404; }
            .health-pill.is-offline { color: #7F1D1D; }
            .health-pill.is-silent  { color: #52635A; }
            .health-pill.is-online .dot  { background: #2D6A4F; }
            .health-pill.is-warning .dot { background: #D4A017; }
            .health-pill.is-offline .dot { background: #b30000; }
            .health-pill.is-silent .dot  { background: #fff; border: 2px solid #8A9A91; }
            .health-meta { font-size: 0.74rem; color: #52635A; margin-top: 0.1rem; white-space: nowrap; }

            /* Column headings carry a plain-language hint under the label. */
            .device-table th { white-space: nowrap; vertical-align: top; }
            .device-table th .th-hint {
                display: block;
                font-size: 0.68rem;
                font-weight: 400;
                letter-spacing: 0;
                text-transform: none;
                color: #52635A;
            }
            .device-table td { padding-top: 0.7rem; padding-bottom: 0.7rem; }
            /* Keep codes, hive names and buttons on one line; the table scrolls sideways on small screens instead. */
            .device-table td:not(.cell-wrap), .device-table .btn { white-space: nowrap; }
            .device-table .cell-sub { font-size: 0.74rem; color: #52635A; }
            .device-table .dropdown-item { font-size: 0.82rem; }
            .device-table .dropdown-item i { width: 1.1rem; }

            /* Match the danger outline button to the other buttons on these pages. */
            .page-content .btn-outline-danger { font-size: 0.82rem; font-weight: 500; }

            .badge-info    { background: #DBEAFE; color: #1E3A8A; }
            .badge-retired { background: #E5E9E7; color: #37433D; }

            .device-facts dt { color: #52635A; font-weight: 500; }
            .device-facts dd { margin-bottom: 0.6rem; }
            .device-facts .fact-hint { display: block; font-size: 0.74rem; color: #52635A; }

            .field-guide dt { font-size: 0.8rem; font-weight: 600; color: #1a2e1f; }
            .field-guide dd { font-size: 0.78rem; color: #52635A; margin-bottom: 0.75rem; }
            .field-guide dd:last-child { margin-bottom: 0; }

            .next-steps { list-style: none; padding: 0; margin: 0; counter-reset: step; }
            .next-steps li {
                counter-increment: step;
                position: relative;
                padding-left: 2rem;
                font-size: 0.8rem;
                color: #37433D;
                margin-bottom: 0.7rem;
            }
            .next-steps li:last-child { margin-bottom: 0; }
            .next-steps li::before {
                content: counter(step);
                position: absolute;
                left: 0; top: 0;
                width: 1.35rem; height: 1.35rem;
                border-radius: 50%;
                background: var(--clr-forest-pale);
                color: var(--clr-forest);
                font-size: 0.72rem;
                font-weight: 700;
                display: flex;
                align-items: center;
                justify-content: center;
            }

            @media (min-width: 992px) {
                .sticky-side { position: sticky; top: calc(var(--topbar-height) + 1rem); }
            }
        </style>
    @endpush
@endonce
