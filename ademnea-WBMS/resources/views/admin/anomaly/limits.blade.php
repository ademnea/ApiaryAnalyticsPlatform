@extends('layouts.app')

@section('title', 'Detection Limits')
@section('page-title', 'Detection Limits')
@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.anomaly.dashboard') }}">Anomaly Dashboard</a></li>
    <li class="breadcrumb-item active" aria-current="page">Detection Limits</li>
@endsection

@php
    // Limits with a validation error, per group, so a tab can say it needs attention.
    $groupErrors = collect($groups)->mapWithKeys(fn ($group) => [
        $group['id'] => collect($group['rules'])->flatMap(fn ($rule) => array_keys($rule['fields']))->filter(fn ($key) => $errors->has("limits.{$key}"))->count(),
    ]);
    $activeGroup = $groupErrors->filter()->keys()->first() ?? $groups[0]['id'];
@endphp

@push('styles')
    <style>
        .limits-page { --limits-text-muted: #52635A; --limits-field-border: #7A8F84; max-width: 68rem; }
        .limits-page .visually-hidden-focusable:not(:focus) { position: absolute; }

        /* ---- Toolbar: scope, save and the group tabs stay in reach while the page scrolls ---- */
        .limits-toolbar {
            position: sticky; top: var(--topbar-height); z-index: 20;
            background: var(--clr-card); border: 1px solid var(--clr-border); border-radius: 12px;
            box-shadow: 0 4px 14px rgba(27, 67, 50, 0.08); margin-bottom: 1rem;
        }
        .limits-toolbar-main { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 0.75rem 1.5rem; padding: 0.75rem 1rem; }
        .limits-scope { display: flex; align-items: center; gap: 0.6rem; }
        .limits-scope label { font-size: 0.875rem; font-weight: 600; white-space: nowrap; margin: 0; }
        .limits-scope select { min-width: 15rem; min-height: 2.5rem; font-size: 0.9rem; border-color: var(--limits-field-border); }
        .limits-save { display: flex; flex-wrap: wrap; align-items: center; gap: 0.6rem; }
        .limits-save .btn { min-height: 2.5rem; font-size: 0.9rem; }
        .limits-status { font-size: 0.875rem; font-weight: 600; color: var(--limits-text-muted); margin-right: 0.25rem; }
        .limits-status.is-dirty { color: #7A4D00; }
        .limits-status.is-dirty::before { content: ""; display: inline-block; width: 0.55rem; height: 0.55rem; margin-right: 0.4rem; border-radius: 50%; background: #B7791F; }

        .limits-tabs { display: flex; flex-wrap: wrap; gap: 0.25rem; padding: 0 0.6rem; border-top: 1px solid var(--clr-border); }
        .limits-tab {
            display: inline-flex; align-items: center; gap: 0.45rem; min-height: 2.75rem; padding: 0.5rem 0.9rem;
            border: 0; border-bottom: 3px solid transparent; background: none;
            font-size: 0.9rem; font-weight: 600; color: var(--limits-text-muted);
        }
        .limits-tab:hover { color: var(--clr-forest); }
        .limits-tab[aria-selected="true"] { color: var(--clr-forest); border-bottom-color: var(--clr-forest); }
        .limits-tab-flag { padding: 0.1rem 0.45rem; border-radius: 999px; font-size: 0.75rem; font-weight: 700; }
        .limits-tab-flag.is-changed { background: #FFF3CD; color: #664D03; }
        .limits-tab-flag.is-error { background: #FFE0E0; color: #7F1D1D; }

        .limits-page :is(.limits-tab, .limits-panel, .limit-item input, .limit-row-state button, select, .btn):focus-visible {
            outline: 3px solid #0B5ED7; outline-offset: 2px;
        }

        /* ---- Notes above the form ---- */
        .limits-note {
            display: flex; flex-wrap: wrap; align-items: center; gap: 0.4rem 0.6rem;
            padding: 0.65rem 0.9rem; margin-bottom: 1rem; font-size: 0.875rem;
            border: 1px solid var(--clr-border); border-radius: 10px; background: var(--clr-card);
        }
        .limits-note.is-hive { background: #EFF6FF; border-color: #93C5FD; }

        /* ---- A tab panel: one card per rule ---- */
        .limits-panel-summary { font-size: 0.875rem; color: var(--limits-text-muted); margin: 0 0 0.85rem; }
        .limits-rules { display: grid; grid-template-columns: repeat(auto-fit, minmax(min(100%, 26rem), 1fr)); gap: 1rem; align-items: start; }
        .limit-rows { display: flex; flex-wrap: wrap; gap: 1rem 2rem; }
        .limits-rule { padding: 1rem 1.1rem; scroll-margin-top: 11rem; }
        .limits-rule.is-highlighted { box-shadow: 0 0 0 3px #F8C93A; }
        .limits-rule h3 { margin: 0; font-size: 1rem; font-weight: 700; color: var(--clr-forest); }
        .limits-rule-desc { margin: 0.2rem 0 0.9rem; font-size: 0.85rem; color: var(--limits-text-muted); }
        .limits-rule-note { margin: 0.75rem 0 0; font-size: 0.8125rem; color: var(--limits-text-muted); }

        .limit-row { border: 0; padding: 0; margin: 0; min-width: 0; }
        .limit-row legend { float: none; width: auto; margin: 0 0 0.4rem; padding: 0; font-size: 0.8125rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.03em; color: var(--limits-text-muted); }
        .limit-items { display: flex; flex-wrap: wrap; gap: 0.75rem 1.25rem; }
        .limit-item label { display: block; margin-bottom: 0.25rem; font-size: 0.875rem; font-weight: 600; }
        .limit-input { display: flex; align-items: stretch; }
        .limit-input input {
            width: 6.5rem; min-height: 2.5rem; padding: 0.35rem 0.6rem; font-size: 1rem;
            border: 1px solid var(--limits-field-border); border-radius: 8px; background: #fff; color: var(--clr-dark);
        }
        .limit-input input[type="text"] { width: 9rem; }
        .limit-input input:has(+ .limit-unit) { border-top-right-radius: 0; border-bottom-right-radius: 0; }
        .limit-unit {
            display: flex; align-items: center; padding: 0 0.65rem; font-size: 0.875rem; white-space: nowrap;
            border: 1px solid var(--limits-field-border); border-left: 0; border-radius: 0 8px 8px 0;
            background: var(--clr-canvas); color: var(--limits-text-muted);
        }
        .limit-input input:disabled { background: #EEF2EF; color: var(--limits-text-muted); }
        .limit-input input::placeholder { color: #6B7F74; }
        .limit-input input.is-changed { border-color: #B7791F; background: #FFFBEB; box-shadow: inset 3px 0 0 #B7791F; }
        .limit-input input[aria-invalid="true"] { border-color: #B30000; background: #FFF5F5; box-shadow: inset 3px 0 0 #B30000; }
        .limit-inherit { margin-top: 0.25rem; font-size: 0.8125rem; color: var(--limits-text-muted); }

        .limit-row-state { display: flex; flex-wrap: wrap; align-items: center; gap: 0.5rem 0.75rem; margin-top: 0.5rem; font-size: 0.8125rem; color: var(--limits-text-muted); }
        .limit-row-state:not(:has(> :not([hidden]))) { display: none; }
        .limit-row-state button { min-height: 1.75rem; padding: 0 0.15rem; border: 0; background: none; font-size: 0.8125rem; font-weight: 600; color: #14532D; text-decoration: underline; }
        .limit-row-error { margin: 0.5rem 0 0; font-size: 0.875rem; font-weight: 600; color: #9B1C1C; }
        .limit-row-error i { margin-right: 0.3rem; }
    </style>
@endpush

@section('content')

    @include('admin.anomaly._subnav')

    <div class="limits-page">

        {{-- The hive picker is its own form; its control sits in the toolbar. --}}
        <form method="GET" action="{{ route('admin.anomaly.limits') }}" id="limits-scope"></form>

        <div class="limits-toolbar">
            <div class="limits-toolbar-main">
                <div class="limits-scope">
                    <label for="scope-hive">Limits for</label>
                    <select name="hive_id" id="scope-hive" form="limits-scope" class="form-select" onchange="this.form.submit()">
                        <option value="">All hives</option>
                        @foreach($hives as $option)
                            <option value="{{ $option->id }}" @selected($hive?->id === $option->id)>Hive {{ $option->display_name ?? $option->hive_code }}</option>
                        @endforeach
                    </select>
                    <noscript><button type="submit" form="limits-scope" class="btn btn-outline-forest">Show</button></noscript>
                </div>

                @if($canEdit)
                    <div class="limits-save">
                        <span class="limits-status" id="limits-status" role="status" aria-live="polite">No unsaved changes</span>
                        <button type="button" class="btn btn-outline-forest" id="limits-discard" hidden>Discard</button>
                        <button type="submit" form="limits-form" class="btn btn-primary">
                            <i class="bi bi-check-circle me-1" aria-hidden="true"></i>Save changes
                        </button>
                    </div>
                @else
                    <span class="limits-status"><i class="bi bi-lock me-1" aria-hidden="true"></i>View only. Changing limits needs the "manage hives" permission.</span>
                @endif
            </div>

            <div class="limits-tabs" role="tablist" aria-label="Groups of limits">
                @foreach($groups as $group)
                    <button type="button" class="limits-tab" role="tab" id="tab-{{ $group['id'] }}" aria-controls="panel-{{ $group['id'] }}"
                            aria-selected="{{ $group['id'] === $activeGroup ? 'true' : 'false' }}" @if($group['id'] !== $activeGroup) tabindex="-1" @endif>
                        <i class="bi {{ $group['icon'] }}" aria-hidden="true"></i>
                        {{ $group['title'] }}
                        @if($groupErrors[$group['id']] > 0)
                            <span class="limits-tab-flag is-error">{{ $groupErrors[$group['id']] }} to fix</span>
                        @endif
                        <span class="limits-tab-flag is-changed" data-changed-count hidden></span>
                    </button>
                @endforeach
            </div>
        </div>

        @if($errors->any())
            <div class="alert alert-danger" role="alert" style="font-size:0.9rem;">
                <strong>Nothing was saved.</strong>
                {{ $errors->count() === 1 ? 'One limit needs fixing. It is marked below.' : $errors->count().' limits need fixing. Each is marked below, and the tabs show where.' }}
            </div>
        @endif

        @if($hive)
            <div class="limits-note is-hive">
                <i class="bi bi-hexagon" aria-hidden="true"></i>
                <span>
                    These are the limits for hive <strong>{{ $hive->display_name ?? $hive->hive_code }}</strong> only.
                    Leave a field empty to keep the limit set for all hives.
                </span>
                <a href="{{ route('admin.anomaly.limits') }}" class="ms-auto">Back to all hives</a>
            </div>
        @elseif($hivesWithOverrides->isNotEmpty())
            <div class="limits-note">
                <span>{{ $hivesWithOverrides->count() === 1 ? '1 hive has its own limits:' : $hivesWithOverrides->count().' hives have their own limits:' }}</span>
                @foreach($hivesWithOverrides as $overridden)
                    <a href="{{ route('admin.anomaly.limits', ['hive_id' => $overridden->id]) }}">{{ $overridden->display_name ?? $overridden->hive_code }} ({{ $overridden->override_count }})</a>@if(! $loop->last),@endif
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('admin.anomaly.limits.update') }}" id="limits-form" novalidate>
            @csrf
            @method('PUT')
            <input type="hidden" name="hive_id" value="{{ $hive?->id }}">
            <input type="hidden" name="tab" id="limits-tab-field" value="tab-{{ $activeGroup }}">

            @foreach($groups as $group)
                {{-- Every panel is visible until the script turns them into tabs, so the page works without it. --}}
                <section class="limits-panel mb-4" role="tabpanel" id="panel-{{ $group['id'] }}" aria-labelledby="tab-{{ $group['id'] }}" tabindex="0">
                    <p class="limits-panel-summary">{{ $group['summary'] }}</p>

                    <div class="limits-rules">
                        @foreach($group['rules'] as $rule)
                            <div class="card limits-rule" id="rule-{{ $rule['type'] }}">
                                <h3><i class="bi {{ $rule['icon'] }} me-1" aria-hidden="true"></i>{{ $rule['label'] }}</h3>
                                <p class="limits-rule-desc">{{ $rule['description'] }}</p>

                                <div class="limit-rows">
                                @foreach($rule['rows'] as $row)
                                    @php
                                        $rowFields = collect($row['items'])->map(fn ($key) => $rule['fields'][$key]);
                                        $fleetOnly = $hive && $rowFields->every(fn ($field) => $field['fleetOnly']);
                                        $editable = $canEdit && ! $fleetOnly;
                                        $ownLimit = $hive && $rowFields->contains(fn ($field) => $field['value'] !== null);
                                        // What the row's reset button puts back: the standard values, or for a hive, nothing.
                                        $resetTo = $rowFields->mapWithKeys(fn ($field) => ["limits[{$field['key']}]" => $hive ? '' : $field['default']]);
                                        $rowErrors = $rowFields->flatMap(fn ($field) => $errors->get("limits.{$field['key']}"));
                                        $errorId = 'limit-error-'.$rowFields->first()['key'];
                                    @endphp
                                    <fieldset class="limit-row">
                                        <legend @class(['visually-hidden' => count($rule['rows']) === 1])>{{ $row['label'] }}</legend>

                                        <div class="limit-items">
                                            @foreach($row['items'] as $itemLabel => $key)
                                                @php
                                                    $field = $rule['fields'][$key];
                                                    $inputId = 'limit-'.$key;
                                                    $saved = $fleetOnly ? $field['fleetValue'] : ($field['value'] ?? '');
                                                    $describedBy = collect([
                                                        $field['unit'] ? $inputId.'-unit' : null,
                                                        $inputId.'-hint',
                                                        $errors->has('limits.'.$key) ? $errorId : null,
                                                    ])->filter()->implode(' ');
                                                @endphp
                                                <div class="limit-item">
                                                    <label for="{{ $inputId }}">{{ $itemLabel }}</label>
                                                    <div class="limit-input">
                                                        <input id="{{ $inputId }}" name="limits[{{ $key }}]"
                                                               @if($field['type'] === 'number')
                                                                   type="number" inputmode="decimal" min="{{ $field['min'] }}" max="{{ $field['max'] }}" step="{{ $field['step'] }}"
                                                               @else
                                                                   type="text" maxlength="30" autocomplete="off"
                                                               @endif
                                                               value="{{ old('limits.'.$key, $saved) }}"
                                                               data-saved="{{ $saved }}"
                                                               aria-describedby="{{ $describedBy }}"
                                                               @if($hive && ! $fleetOnly) placeholder="{{ $field['fleetValue'] }}" @endif
                                                               @if($errors->has('limits.'.$key)) aria-invalid="true" @endif
                                                               @required(! $hive)
                                                               @disabled(! $editable)>
                                                        @if($field['unit'])<span class="limit-unit" id="{{ $inputId }}-unit">{{ $field['unit'] }}</span>@endif
                                                    </div>
                                                    @if($hive && ! $fleetOnly)
                                                        <div class="limit-inherit" id="{{ $inputId }}-hint">All hives: {{ $field['fleetValue'] }}</div>
                                                    @elseif($field['type'] === 'number')
                                                        <span class="visually-hidden" id="{{ $inputId }}-hint">Allowed {{ $field['min'] }} to {{ $field['max'] }}. Standard value {{ $field['default'] }}.</span>
                                                    @else
                                                        <span class="visually-hidden" id="{{ $inputId }}-hint">{{ $fleetOnly ? 'Set for all hives, not per hive.' : '' }}</span>
                                                    @endif
                                                </div>
                                            @endforeach
                                        </div>

                                        @if($rowErrors->isNotEmpty())
                                            <p class="limit-row-error" id="{{ $errorId }}"><i class="bi bi-exclamation-circle-fill" aria-hidden="true"></i>{{ $rowErrors->implode(' ') }}</p>
                                        @endif

                                        <div class="limit-row-state">@if($fleetOnly)<span>Set for all hives, not per hive.</span>@elseif($hive)<span class="badge badge-info" data-when="own" @if(! $ownLimit) hidden @endif>Own limit</span>@endif @if($editable)<button type="button" data-set="{{ $resetTo->toJson() }}" hidden>{{ $hive ? 'Use the all-hives limit' : 'Reset to standard ('.$rowFields->pluck('default')->implode(' and ').')' }}</button>@endif</div>
                                    </fieldset>
                                @endforeach
                                </div>

                                @if($rule['note'])
                                    <p class="limits-rule-note"><i class="bi bi-info-circle me-1" aria-hidden="true"></i>{{ $rule['note'] }}</p>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </section>
            @endforeach
        </form>
    </div>

@endsection

@push('scripts')
    <script>
        (function () {
            const form = document.getElementById('limits-form');
            const tabs = Array.from(document.querySelectorAll('.limits-tab'));
            const panels = tabs.map((tab) => document.getElementById(tab.getAttribute('aria-controls')));
            const tabField = document.getElementById('limits-tab-field');

            /* ---- Tabs: arrow keys move between them, the address remembers which one is open ---- */
            function select(tab, { focus = false, remember = true } = {}) {
                tabs.forEach((other, i) => {
                    const selected = other === tab;
                    other.setAttribute('aria-selected', selected ? 'true' : 'false');
                    other.tabIndex = selected ? 0 : -1;
                    panels[i].hidden = !selected;
                });

                tabField.value = tab.id;

                if (remember) {
                    history.replaceState(null, '', '#' + tab.id);
                }

                if (focus) {
                    tab.focus();
                }
            }

            tabs.forEach((tab, i) => {
                tab.addEventListener('click', () => select(tab));
                tab.addEventListener('keydown', (event) => {
                    const target = { ArrowRight: tabs[(i + 1) % tabs.length], ArrowLeft: tabs[(i - 1 + tabs.length) % tabs.length], Home: tabs[0], End: tabs[tabs.length - 1] }[event.key];

                    if (target) {
                        event.preventDefault();
                        select(target, { focus: true });
                    }
                });
            });

            // Open the tab named in the address, or the one holding the rule it points at.
            function openFromAddress() {
                const target = location.hash ? document.getElementById(location.hash.slice(1)) : null;
                const rule = target && target.classList.contains('limits-rule') ? target : null;
                const tab = rule ? document.getElementById(rule.closest('.limits-panel').getAttribute('aria-labelledby')) : (tabs.includes(target) ? target : null);

                select(tab || tabs.find((candidate) => candidate.getAttribute('aria-selected') === 'true'), { remember: false });

                if (rule) {
                    rule.classList.add('is-highlighted');
                    rule.scrollIntoView({ block: 'center' });
                    setTimeout(() => rule.classList.remove('is-highlighted'), 2500);
                }
            }

            // A server-side error decides the tab, ahead of whatever the address says.
            if (document.querySelector('.limits-tab-flag.is-error')) {
                select(tabs.find((tab) => tab.querySelector('.is-error')), { remember: false });
            } else {
                openFromAddress();
            }

            window.addEventListener('hashchange', openFromAddress);

            /* ---- Unsaved changes ---- */
            const status = document.getElementById('limits-status');
            const discard = document.getElementById('limits-discard');

            if (!discard) {
                return; // view only
            }

            const inputs = Array.from(form.querySelectorAll('input[data-saved]:not(:disabled)'));
            let submitting = false;

            // "60", "60.0" and " 60 " are the same limit.
            const normalise = (value) => {
                const text = String(value).trim();
                return text !== '' && !isNaN(text) ? String(Number(text)) : text;
            };

            function refresh() {
                let changed = 0;
                const perPanel = new Map(panels.map((panel) => [panel, 0]));

                inputs.forEach((input) => {
                    const isChanged = normalise(input.value) !== normalise(input.dataset.saved);
                    input.classList.toggle('is-changed', isChanged);

                    if (isChanged) {
                        changed += 1;
                        const panel = input.closest('.limits-panel');
                        perPanel.set(panel, perPanel.get(panel) + 1);
                    }
                });

                tabs.forEach((tab, i) => {
                    const flag = tab.querySelector('[data-changed-count]');
                    const count = perPanel.get(panels[i]);
                    flag.hidden = count === 0;
                    flag.textContent = count + ' changed';
                });

                form.querySelectorAll('.limit-row').forEach((row) => {
                    // A reset button only shows while it would change something.
                    row.querySelectorAll('[data-set]').forEach((button) => {
                        button.hidden = Object.entries(JSON.parse(button.dataset.set))
                            .every(([name, value]) => normalise(form.elements[name].value) === normalise(value));
                    });

                    // For a hive, a row with anything typed in it is the hive's own limit.
                    const own = Array.from(row.querySelectorAll('input[data-saved]')).some((input) => input.value.trim() !== '');
                    row.querySelectorAll('[data-when]').forEach((note) => { note.hidden = (note.dataset.when === 'own') !== own; });
                });

                status.textContent = changed === 0 ? 'No unsaved changes' : (changed === 1 ? '1 unsaved change' : changed + ' unsaved changes');
                status.classList.toggle('is-dirty', changed > 0);
                discard.hidden = changed === 0;

                return changed;
            }

            form.addEventListener('input', refresh);

            form.addEventListener('click', (event) => {
                const button = event.target.closest('[data-set]');

                if (button) {
                    const values = Object.entries(JSON.parse(button.dataset.set));
                    values.forEach(([name, value]) => { form.elements[name].value = value; });
                    form.elements[values[0][0]].focus(); // the button is about to hide
                    refresh();
                }
            });

            discard.addEventListener('click', () => {
                inputs.forEach((input) => { input.value = input.dataset.saved; });
                refresh();
                status.focus?.();
            });

            form.addEventListener('submit', () => { submitting = true; });

            // Switching hive or leaving would silently drop edits.
            window.addEventListener('beforeunload', (event) => {
                if (!submitting && refresh() > 0) {
                    event.preventDefault();
                    event.returnValue = '';
                }
            });

            refresh();
        })();
    </script>
@endpush
