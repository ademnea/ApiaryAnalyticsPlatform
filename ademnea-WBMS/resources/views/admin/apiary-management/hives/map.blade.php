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
            const marker = L.circleMarker([hive.latitude, hive.longitude], {
                radius: 6,
                fillColor: color,
                color: '#fff',
                weight: 1,
                opacity: 1,
                fillOpacity: 0.85,
            });

            const apiaryLabel = hive.apiary
                ? `${hive.apiary.name} <span style="color:#6B7F74">(${hive.apiary.apiary_code})</span>`
                : 'Unassigned';

            marker.bindPopup(`
                <strong>${hive.hybrid_identifier}</strong><br>
                ${hive.display_name}<br>
                <span style="color:#6B7F74">${apiaryLabel}</span><br>
                <span style="color:${color};font-weight:600;">${hive.current_status}</span>
            `);

            marker.addTo(markerLayer);
        });
    }

    loadHives();
    map.on('moveend', loadHives);
});
</script>
@endpush
