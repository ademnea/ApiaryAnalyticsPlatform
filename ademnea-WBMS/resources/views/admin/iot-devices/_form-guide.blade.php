{{-- Side panel for the device forms. Shows next steps when registering, a device summary when editing. --}}
@if(isset($iotDevice))
    <div class="card mb-3">
        <div class="card-header"><i class="bi bi-cpu me-1"></i>{{ $iotDevice->device_code }}</div>
        <div class="card-body">
            <dl class="row mb-0 device-facts" style="font-size:0.82rem;">
                <dt class="col-5">Lifecycle</dt>
                <dd class="col-7">@include('admin.iot-devices._lifecycle-badge', ['status' => $iotDevice->status])</dd>
                <dt class="col-5">Data access</dt>
                <dd class="col-7">
                    @if($iotDevice->active_flag)<span class="badge badge-active">Allowed</span>
                    @else<span class="badge badge-offline">Revoked</span>@endif
                </dd>
                <dt class="col-5">Registered</dt>
                <dd class="col-7 mb-0">{{ $iotDevice->created_at->format('d M Y') }}</dd>
            </dl>
        </div>
    </div>
@else
    <div class="card mb-3">
        <div class="card-header"><i class="bi bi-signpost-2 me-1"></i>What happens next</div>
        <div class="card-body">
            <ol class="next-steps">
                <li>You get the device's <strong>API key</strong>, shown once. Copy it and load it onto the device.</li>
                <li><strong>Assign the device to a hive</strong> so its readings are filed under that hive.</li>
                <li>Once it sends its first data, it shows as <strong>Online</strong> in the registry.</li>
            </ol>
        </div>
    </div>
@endif

<div class="card">
    <div class="card-header"><i class="bi bi-info-circle me-1"></i>Device types</div>
    <div class="card-body">
        <dl class="field-guide mb-0">
            <dt>Numeric Sensor</dt>
            <dd>Sends numbers: temperature, humidity, CO₂ or weight.</dd>
            <dt>Media Capture</dt>
            <dd>Sends audio, video or photos from the hive.</dd>
            <dt>Combo Unit</dt>
            <dd>Does both in one device.</dd>
        </dl>
    </div>
</div>
