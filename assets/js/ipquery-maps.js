/* IpQuery WP – Leaflet heatmap */
(function ($) {
    'use strict';

    var map, heatLayer;

    function initMap() {
        var el = document.getElementById('ipquery-map');
        if (!el) return;

        map = L.map('ipquery-map', { scrollWheelZoom: false }).setView([20, 0], 2);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
            maxZoom: 18
        }).addTo(map);

        // Flex layout may not have settled when Leaflet initialises, so tiles
        // render at wrong size. Force a recalculation once layout is stable.
        setTimeout(function () { map.invalidateSize(); }, 150);

        loadHeatmap();
    }

    function loadHeatmap() {
        $.ajax({
            url: IpQueryData.ajaxUrl,
            method: 'POST',
            data: {
                action: IpQueryData.heatmapAction,
                nonce: IpQueryData.nonce
            },
            success: function (res) {
                if (!res.success || !res.data || !res.data.length) return;

                // Find max intensity for normalisation
                var maxIntensity = 1;
                res.data.forEach(function (p) {
                    if (parseInt(p.intensity, 10) > maxIntensity) {
                        maxIntensity = parseInt(p.intensity, 10);
                    }
                });

                var points = res.data.map(function (p) {
                    return [
                        parseFloat(p.latitude),
                        parseFloat(p.longitude),
                        parseInt(p.intensity, 10) / maxIntensity  // 0–1
                    ];
                });

                if (heatLayer) {
                    map.removeLayer(heatLayer);
                }

                heatLayer = L.heatLayer(points, {
                    radius: 22,
                    blur: 18,
                    maxZoom: 5,
                    gradient: { 0.3: '#2271b1', 0.6: '#dba617', 1.0: '#d63638' }
                }).addTo(map);
            }
        });
    }

    $(document).ready(function () {
        initMap();
    });

}(jQuery));
