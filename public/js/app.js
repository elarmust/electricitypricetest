(function ($) {
    'use strict';

    let chart = null;
    let current = null;

    function effCents(baseEurMwh) {
        return baseEurMwh / 10;
    }

    function inWindow(index, win) {
        return !!win && index >= win.startIndex && index < win.startIndex + win.length;
    }

    function renderCards(payload) {
        const el = document.getElementById('cards');
        if (!el) {
            return;
        }

        if (payload.isEmpty || payload.points.length === 0) {
            el.innerHTML = '';
            return;
        }

        const vat = payload.vatMultiplier || 1;
        const card = (title, eurMwh) => {
            const noVat = effCents(eurMwh);
            const withVat = noVat * vat;
            return `<div class="card"><span>${title}</span>` +
                `<strong>${withVat.toFixed(2)} snt/kWh</strong>` +
                `<small>ilma KM ${noVat.toFixed(2)}</small></div>`;
        };

        el.innerHTML = card('Keskmine', payload.average) +
            card('Miinimum', payload.min) +
            card('Maksimum', payload.max);
    }

    function renderWindows(payload) {
        const el = document.getElementById('windows');
        if (!el) {
            return;
        }

        if (payload.isEmpty || !payload.cheapestWindow) {
            el.innerHTML = '';
            return;
        }

        const vat = payload.vatMultiplier || 1;
        const box = (kind, title, w) => {
            if (!w) {
                return `<div class="window-card ${kind}"><span class="window-title">${title}</span><strong>n/a</strong></div>`;
            }
            const start = payload.points[w.startIndex] ? payload.points[w.startIndex].label : '';
            const endIdx = w.startIndex + w.length - 1;
            const end = payload.points[endIdx] ? payload.points[endIdx].label : start;
            const noVat = effCents(w.average);
            const withVat = noVat * vat;
            return `<div class="window-card ${kind}">` +
                `<span class="window-title">${title}</span>` +
                `<strong>Kell ${start}–${end} (${w.lengthHours} h)</strong>` +
                `<span>Keskmine: ${withVat.toFixed(2)} s/kWh (KM-ga)</span>` +
                `<small>ilma KM ${noVat.toFixed(2)}</small></div>`;
        };

        el.innerHTML = box('cheapest', 'Odavaim aken', payload.cheapestWindow) +
            box('expensive', 'Kalleim aken', payload.mostExpensiveWindow);
    }

    function barColor(point, payload) {
        if (point.adjustedBase < payload.average) {
            return '#34d399';
        }

        if (point.adjustedBase > payload.average) {
            return '#f87171';
        }

        return '#9ca3af';
    }

    const windowBoxPlugin = {
        id: 'windowBox',
        beforeDatasetsDraw(chart, args, opts) {
            const windows = opts && opts.windows;
            if (!windows) {
                return;
            }

            const { ctx, chartArea } = chart;
            const meta = chart.getDatasetMeta(0);
            if (!meta || !meta.data || meta.data.length === 0) {
                return;
            }

            const draw = (win, color) => {
                if (!win) {
                    return;
                }

                const start = Math.max(0, win.startIndex);
                const end = Math.min(meta.data.length - 1, win.startIndex + win.length - 1);
                const first = meta.data[start];
                const last = meta.data[end];
                if (!first || !last) {
                    return;
                }

                const xStart = first.x - first.width / 2;
                const xEnd = last.x + last.width / 2;
                ctx.save();
                ctx.fillStyle = color;
                ctx.fillRect(xStart, chartArea.top, xEnd - xStart, chartArea.bottom - chartArea.top);
                ctx.restore();
            };
            draw(windows.cheapest, 'rgba(22,163,74,0.15)');
            draw(windows.expensive, 'rgba(220,38,38,0.15)');
        },
    };

    function renderChart(payload) {
        const canvas = document.getElementById('priceChart');
        const message = document.getElementById('chart-message');
        if (!canvas) {
            return;
        }

        const empty = payload.isEmpty || payload.points.length === 0;
        if (empty) {
            canvas.hidden = true;
            if (message) {
                message.hidden = false;
                message.textContent = payload.message
                    || 'Andmeid pole saadaval. Tulevasi päevi ei pruugi enne kella 14:00 avaldada.';
            }

            if (chart) {
                chart.destroy();
                chart = null;
            }

            return;
        }

        if (message) {
            message.hidden = true;
        }

        canvas.hidden = false;

        const labels = payload.points.map(p => p.label);
        const centsVat = payload.points.map(p => p.adjustedWithVat / 10);
        const colors = payload.points.map(p => barColor(p, payload));

        const config = {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [
                    {
                        type: 'bar',
                        label: 'KM-ga (snt/kWh)',
                        data: centsVat,
                        backgroundColor: colors,
                        borderRadius: 3,
                        order: 1,
                    }
                ]
            },
            options: {
                responsive: true,
                animation: false,
                plugins: {
                    legend: {
                        display: true,
                        onClick: () => {},
                    },
                    windowBox: {
                        windows: {
                            cheapest: payload.cheapestWindow,
                            expensive: payload.mostExpensiveWindow,
                        },
                    },
                    tooltip: {
                        callbacks: {
                            label: (ctx) => ctx.dataset.label + ': ' + ctx.parsed.y.toFixed(2) + ' snt/kWh'
                        }
                    }
                },
                scales: {
                    y: { beginAtZero: true, title: { display: true, text: 'snt/kWh' } },
                    x: { ticks: { maxTicksLimit: 24 } }
                }
            },
            plugins: [windowBoxPlugin],
        };

        if (chart) {
            chart.data = config.data;
            chart.options = config.options;
            chart.update();
        } else {
            chart = new Chart(canvas, config);
        }
    }

    function rowClass(index, point, payload) {
        if (inWindow(index, payload.cheapestWindow)) {
            return 'row-cheapest';
        }

        if (inWindow(index, payload.mostExpensiveWindow)) {
            return 'row-expensive';
        }

        if (point.adjustedBase < payload.average) {
            return 'row-below';
        }

        if (point.adjustedBase > payload.average) {
            return 'row-above';
        }

        return 'row-avg';
    }

    function renderTable(payload) {
        const tbody = $('.prices tbody');
        if (!tbody.length) {
            return;
        }

        if (payload.isEmpty || payload.points.length === 0) {
            tbody.html('<tr><td colspan="4">Andmeid pole saadaval (tulevasi hindu ei pruugi veel olla avaldatud).</td></tr>');
            return;
        }

        let rows = '';
        payload.points.forEach(function (point, index) {
            const noVat = point.adjustedBase / 10;
            const withVat = point.adjustedWithVat / 10;
            rows += `<tr class="${rowClass(index, point, payload)}">` +
                `<td>${point.label}</td>` +
                `<td>${point.realBase.toFixed(2)}</td>` +
                `<td>${noVat.toFixed(2)}</td>` +
                `<td>${withVat.toFixed(2)}</td>` +
                `</tr>`;
        });
        tbody.html(rows);
    }

    function labelFor(unixSec) {
        const d = new Date(unixSec * 1000);
        return d.toLocaleTimeString('et-EE', {
            timeZone: 'Europe/Tallinn',
            hour: '2-digit',
            minute: '2-digit',
            hour12: false,
        });
    }

    function recomputeWindows(payload, windowHours) {
        const arr = payload.points || [];
        const count = arr.length;
        if (count === 0) {
            payload.cheapestWindow = null;
            payload.mostExpensiveWindow = null;
            return;
        }

        const periodSeconds = count >= 2 ? (arr[1].timestamp - arr[0].timestamp) : 3600;
        const windowPeriods = Math.max(1, Math.min(Math.round((windowHours * 3600) / periodSeconds), count));

        const find = (cheapest) => {
            if (windowPeriods < 1 || windowPeriods > count) {
                return null;
            }

            let bestIndex = null;
            let bestAverage = null;
            for (let i = 0; i + windowPeriods <= count; i++) {
                let sum = 0;
                for (let j = i; j < i + windowPeriods; j++) {
                    sum += arr[j].adjustedBase;
                }

                const average = sum / windowPeriods;
                if (bestAverage === null || (cheapest ? average < bestAverage : average > bestAverage)) {
                    bestAverage = average;
                    bestIndex = i;
                }
            }

            if (bestIndex === null) {
                return null;
            }

            return {
                startIndex: bestIndex,
                length: windowPeriods,
                lengthHours: Math.max(1, Math.round(windowPeriods * periodSeconds / 3600)),
                average: bestAverage,
            };
        };

        payload.cheapestWindow = find(true);
        payload.mostExpensiveWindow = find(false);
    }

    function applyPayload(payload) {
        if (!payload || !payload.points) {
            return;
        }

        let raw = payload.points;
        let arr;
        if (Array.isArray(raw)) {
            arr = raw.map((pt, i) => ({
                timestamp: (payload.startUtc ?? 0) + i * (payload.periodSeconds || 3600),
                realBase: pt.realBase,
                adjustedBase: pt.adjustedBase,
                adjustedWithVat: pt.adjustedWithVat,
            }));
        } else {
            arr = Object.entries(raw).map(([ts, pt]) => ({
                timestamp: Number(ts),
                realBase: pt.realBase,
                adjustedBase: pt.adjustedBase,
                adjustedWithVat: pt.adjustedWithVat,
            }));
        }

        arr.sort((a, b) => a.timestamp - b.timestamp);
        arr.forEach((p) => { p.label = labelFor(p.timestamp); });

        payload.points = arr;
        payload.windowHours = parseInt($('#window').val(), 10) || payload.windowHours || 1;

        recomputeWindows(payload, payload.windowHours);

        const empty = payload.isEmpty || arr.length === 0;
        ['cards', 'windows', 'submit', 'prices-table'].forEach((id) => {
            const el = document.getElementById(id);
            if (el) {
                el.hidden = empty;
            }
        });

        current = payload;
        renderCards(payload);
        renderWindows(payload);
        renderChart(payload);
        renderTable(payload);
    }

    function refreshWindows() {
        if (!current) {
            return;
        }

        current.windowHours = parseInt($('#window').val(), 10) || current.windowHours || 1;
        recomputeWindows(current, current.windowHours);
        renderWindows(current);
        renderChart(current);
        renderTable(current);
    }

    function fetchReport() {
        const date = $('#date').val();
        const windowHours = $('#window').val();
        $.getJSON('/api/prices', { date: date, window: windowHours }, applyPayload);
    }

    function syncSubmitFields() {
        $('#submit-date').val($('#date').val());
        $('#submit-window').val($('#window').val());
    }

    function updateUrlParams() {
        const params = new URLSearchParams(window.location.search);
        params.set('date', $('#date').val());
        params.set('window', $('#window').val());
        window.history.replaceState(null, '', window.location.pathname + '?' + params.toString());
    }

    function togglePriceTable() {
        const $body = $('#prices-table-body');
        const nowExpanded = $body.prop('hidden');
        $body.prop('hidden', !nowExpanded);
        $('.table-toggle')
            .attr('aria-expanded', String(nowExpanded))
            .find('.arrow')
            .text(nowExpanded ? '▾' : '▸');
        $('.table-toggle-close').prop('hidden', !nowExpanded);
    }

    function handleSubmit(e) {
        e.preventDefault();
        const $form = $('#submitForm');
        const $status = $('#submit-status');
        const $error = $('#submit-error');

        $status.prop('hidden', true).text('');
        $error.prop('hidden', true).text('');
        $('#err-name, #err-email, #err-phone').prop('hidden', true).text('');

        $.ajax({
            url: $form.attr('action'),
            method: 'POST',
            data: $form.serialize(),
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            success(resp) {
                $status.text(resp.status || 'Tulemus saadetud.').prop('hidden', false);
                $form[0].reset();
                syncSubmitFields();
            },
            error(xhr) {
                const json = xhr.responseJSON || {};
                if (xhr.status === 422 && json.errors) {
                    ['name', 'email', 'phone'].forEach((field) => {
                        if (json.errors[field]) {
                            $('#err-' + field).text(json.errors[field][0]).prop('hidden', false);
                        }
                    });
                } else {
                    $error.text(json.error || 'Saatmine ebaõnnestus.').prop('hidden', false);
                }
            },
        });
    }

    $(function () {
        syncSubmitFields();
        fetchReport();

        $('#date').on('change', function () {
            syncSubmitFields();
            updateUrlParams();
            fetchReport();
        });
        $('#window').on('change', function () {
            syncSubmitFields();
            updateUrlParams();
            refreshWindows();
        });
        $('#controls').on('submit', function (e) {
            e.preventDefault();
            fetchReport();
        });
        $('.table-toggle').on('click', togglePriceTable);
        $('#submitForm').on('submit', handleSubmit);
    });
})(jQuery);
