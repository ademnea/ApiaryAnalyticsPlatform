@extends('layouts.app')

@section('page-title', 'Hive Map')
@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.hives.index') }}">Hives</a></li>
    <li class="breadcrumb-item active" aria-current="page">Map</li>
@endsection

@section('content')
<div class="card">
    <div class="card-body">
        <div id="hive-map" style="height: 75vh; width: 100%; border-radius: 8px;"></div>
    </div>
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.css"
      integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
<style>
.hive-map-label {
    background: transparent !important;
    border: none !important;
    box-shadow: none !important;
    padding: 0 !important;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    font-size: 12px;
    font-weight: 600;
    color: #1a2e1f;
    text-shadow:
        -1px -1px 0 #fff,
         1px -1px 0 #fff,
        -1px  1px 0 #fff,
         1px  1px 0 #fff,
         0px 0px 3px rgba(255,255,255,0.9);
    white-space: nowrap;
}
.hive-map-label::before {
    display: none !important;
}
.leaflet-tooltip-top:before {
    display: none !important;
}
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.js"
        integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const map = L.map('hive-map').setView([1.3733, 32.2903], 7);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
        maxZoom: 19,
    }).addTo(map);

    const statusColors = {
        'Active': '#2D6A4F',
        'Inactive': '#6B7F74',
        'Under Inspection': '#D4A017',
        'Queenless': '#b30000',
        'Absconded': '#4a7a5d',
        'Decommissioned': '#1a2e1f',
    };

    const markerLayer = L.layerGroup().addTo(map);

    function beeIcon(color) {
        const html = `
            <div style="
                width: 36px; height: 36px;
                background: ${color || '#2D6A4F'};
                border: 2px solid #fff;
                border-radius: 50% 50% 50% 0;
                transform: rotate(-45deg);
                box-shadow: 0 2px 6px rgba(0,0,0,0.3);
                display: flex;
                align-items: center;
                justify-content: center;
            ">
                <span style="transform: rotate(45deg); font-size: 18px; line-height: 1;">🐝</span>
            </div>
        `;
        return L.divIcon({
            html: html,
            className: 'bee-hive-marker',
            iconSize: [36, 36],
            iconAnchor: [18, 18],
            popupAnchor: [0, -20],
        });
    }

    async function loadHives() {
        const bounds = map.getBounds();
        const params = new URLSearchParams({
            sw_lat: bounds.getSouthWest().lat,
            sw_lng: bounds.getSouthWest().lng,
            ne_lat: bounds.getNorthEast().lat,
            ne_lng: bounds.getNorthEast().lng,
        });

        const response = await fetch(`{{ url('admin/hives/map-data') }}?${params.toString()}`);
        const payload = await response.json();
        const hives = payload.data || [];

        markerLayer.clearLayers();

        hives.forEach(function (hive) {
            const color = statusColors[hive.current_status] || '#1B4332';
            const marker = L.marker([hive.latitude, hive.longitude], {
                icon: beeIcon(color),
            });

            marker.bindTooltip(hive.display_name, {
                permanent: true,
                direction: 'top',
                offset: [0, -24],
                className: 'hive-map-label',
            });

            const apiaryLabel = hive.apiary
                ? `${hive.apiary.name} (${hive.apiary.apiary_code})`
                : 'Unassigned';

            marker.bindPopup(`
                <div style="min-width:160px">
                    <div style="font-size:20px; margin-bottom:4px;">🐝</div>
                    <strong style="font-size:14px;">${hive.hybrid_identifier}</strong><br>
                    <span style="color:#333;">${hive.display_name}</span><br>
                    <span style="color:#6B7F74; font-size:12px;">${apiaryLabel}</span><br>
                    <span style="color:${color}; font-weight:600; font-size:12px;">${hive.current_status}</span>
                </div>
            `);

            marker.addTo(markerLayer);
        });
    }

    loadHives();
    map.on('moveend', loadHives);
});
</script>
@endpush
