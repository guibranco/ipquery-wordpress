/* IpQuery WP – Chart.js charts */
(function ($) {
    'use strict';

    var countryChart, riskChart;

    var PALETTE = [
        '#2271b1','#00a32a','#dba617','#d63638','#3582c4',
        '#68b868','#f0c33c','#e06c75','#5abcb9','#a855f7',
        '#f97316','#06b6d4','#84cc16','#ec4899','#14b8a6'
    ];

    function buildCountryChart(data) {
        var el = document.getElementById('ipquery-country-chart');
        if (!el || !data || !data.length) return;

        var labels     = data.map(function (r) { return r.country || r.country_code || 'Unknown'; });
        var visits     = data.map(function (r) { return parseInt(r.visits, 10); });
        var colors     = labels.map(function (_, i) { return PALETTE[i % PALETTE.length]; });

        if (countryChart) countryChart.destroy();

        countryChart = new Chart(el, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Visits',
                    data: visits,
                    backgroundColor: colors,
                    borderRadius: 4,
                    borderSkipped: false
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function (ctx) {
                                return ' ' + ctx.formattedValue + ' visits';
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        ticks: { precision: 0 }
                    }
                }
            }
        });
    }

    function buildRiskChart(risk) {
        var el = document.getElementById('ipquery-risk-chart');
        if (!el || !risk) return;

        var labels = ['VPN', 'Proxy', 'Tor', 'Datacenter', 'Mobile'];
        var values = [risk.vpn, risk.proxy, risk.tor, risk.datacenter, risk.mobile];
        var colors = ['#dba617', '#d63638', '#8b0000', '#2271b1', '#00a32a'];

        if (riskChart) riskChart.destroy();

        riskChart = new Chart(el, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: values,
                    backgroundColor: colors,
                    hoverOffset: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom' },
                    tooltip: {
                        callbacks: {
                            label: function (ctx) {
                                var total = ctx.dataset.data.reduce(function (a, b) { return a + b; }, 0);
                                var pct   = total > 0 ? ((ctx.parsed / total) * 100).toFixed(1) : 0;
                                return ' ' + ctx.label + ': ' + ctx.formattedValue + ' (' + pct + '%)';
                            }
                        }
                    }
                }
            }
        });
    }

    function loadCharts() {
        $.ajax({
            url: IpQueryData.ajaxUrl,
            method: 'POST',
            data: {
                action: IpQueryData.chartAction,
                nonce: IpQueryData.nonce
            },
            success: function (res) {
                if (!res.success || !res.data) return;
                buildCountryChart(res.data.countries);
                buildRiskChart(res.data.risk);
            }
        });
    }

    $(document).ready(function () {
        loadCharts();
    });

}(jQuery));
