{{--
    Chart helpers shared by every monitoring page that draws charts. Include
    it from a page; pushOnce keeps it to one copy however often it is included.

    Chart data lives in JSON <script> blocks inside the live fragment, so an
    auto-refresh brings new data with the rest of the page. mount() draws a
    chart and redraws it after each refresh, keeping any series the user hid
    through the legend hidden.
--}}
@pushOnce('scripts')
    <script>
        window.MonitoringCharts = (function () {
            const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

            const withAlpha = (hex, alpha) => {
                const n = parseInt(hex.slice(1), 16);
                return `rgba(${(n >> 16) & 255}, ${(n >> 8) & 255}, ${n & 255}, ${alpha})`;
            };

            const format = (value, unit, precision) => value === null || value === undefined
                ? '—'
                : Number(value).toFixed(precision) + (unit ? ' ' + unit : '');

            const payload = (selector) => {
                const source = document.querySelector(selector);
                return source ? JSON.parse(source.textContent) : null;
            };

            /** A y-axis range fitted to the values, padded, and rounded to a readable step. */
            function fitRange(values, precision) {
                const present = values.filter((v) => v !== null && v !== undefined);

                if (present.length === 0) {
                    return {};
                }

                const low = Math.min(...present);
                const high = Math.max(...present);
                const padding = Math.max((high - low) * 0.15, Math.abs(high) * 0.02, Math.pow(10, -precision));
                const step = Math.pow(10, Math.floor(Math.log10(high - low + padding * 2)));

                return { min: Math.floor((low - padding) / step) * step, max: Math.ceil((high + padding) / step) * step };
            }

            /**
             * Two datasets drawing a shaded horizontal band: the top edge, then
             * the bottom edge filled up to it. Both carry role 'band' so legends
             * and tooltips can skip them.
             */
            function bandDatasets(band, length, color, yAxisID = 'y') {
                const edge = { role: 'band', yAxisID, pointRadius: 0, borderWidth: 1, borderDash: [3, 3], borderColor: withAlpha(color, 0.55) };

                return [
                    { ...edge, label: band.label + ' (max)', data: Array(length).fill(band.max), fill: false },
                    { ...edge, label: band.label, data: Array(length).fill(band.min), fill: '-1', backgroundColor: withAlpha(color, 0.12) },
                ];
            }

            /** Markers only on sparse charts, or for a point with gaps on both sides that would otherwise vanish. */
            const pointRadius = (length) => (context) => {
                const data = context.dataset.data;
                const i = context.dataIndex;
                const isolated = data[i] !== null && (data[i - 1] ?? null) === null && (data[i + 1] ?? null) === null;
                return isolated || length <= 48 ? 2 : 0;
            };

            /** build(canvas) returns a Chart.js config, or null to draw nothing. */
            function mount(canvasId, build) {
                let chart = null;

                function draw(animate) {
                    const hidden = chart
                        ? chart.data.datasets.filter((dataset, i) => !chart.isDatasetVisible(i)).map((dataset) => dataset.label)
                        : [];

                    chart?.destroy();
                    chart = null;

                    const canvas = document.getElementById(canvasId);
                    const config = canvas && typeof Chart !== 'undefined' ? build(canvas) : null;

                    if (!config) {
                        return;
                    }

                    config.options = config.options || {};
                    config.options.animation = animate && !reduceMotion ? { duration: 350 } : false; // refresh redraws do not animate
                    chart = new Chart(canvas, config);

                    if (hidden.length > 0) {
                        chart.data.datasets.forEach((dataset, i) => chart.setDatasetVisibility(i, !hidden.includes(dataset.label)));
                        chart.update('none');
                    }
                }

                draw(true);
                document.addEventListener('monitoring:refreshed', () => draw(false));
            }

            return { withAlpha, format, payload, fitRange, bandDatasets, pointRadius, mount };
        })();
    </script>
@endPushOnce
