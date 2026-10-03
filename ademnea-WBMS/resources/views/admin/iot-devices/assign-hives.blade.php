@if(is_null($apiary))
    <div class="alert alert-warning mb-0" style="font-size:0.82rem;">
        <i class="bi bi-exclamation-triangle me-1"></i>That apiary could not be found. Choose another one on the left.
    </div>
@elseif($hives->isEmpty())
    <div class="text-muted text-center py-5" style="font-size:0.82rem;">
        <i class="bi bi-inbox d-block mb-2" style="font-size:1.3rem;"></i>
        <strong class="d-block text-dark mb-1">No hives available at {{ $apiary->name }}</strong>
        Either no hives are registered here yet, or every hive already has a
        {{ str_replace('_', ' ', $iotDevice->device_type) }} device.
    </div>
@else
    <p class="mb-3" style="font-size:0.82rem;">
        <strong>{{ $hives->count() }}</strong> {{ Str::plural('hive', $hives->count()) }} at
        <strong>{{ $apiary->name }}</strong> can take this device. Choose one:
    </p>

    <form action="{{ route('admin.iot-devices.assign.store', $iotDevice) }}" method="POST">
        @csrf
        <input type="hidden" name="apiary_id" value="{{ $apiaryId }}">

        <div style="max-height:360px;overflow-y:auto;">
            @foreach($hives as $hive)
                <div class="form-check hive-option">
                    <input class="form-check-input" type="radio" name="hive_id" id="hive-{{ $hive->id }}" value="{{ $hive->id }}" required>
                    <label class="form-check-label w-100" for="hive-{{ $hive->id }}">
                        <span class="fw-medium">{{ $hive->display_name ?: $hive->hybrid_code }}</span>
                        @if($hive->display_name)
                            <span class="d-block text-muted" style="font-size:0.75rem;">{{ $hive->hybrid_code }}</span>
                        @endif
                    </label>
                </div>
            @endforeach
        </div>

        <button type="submit" class="btn btn-primary mt-3">
            <i class="bi bi-check-circle me-1"></i>Assign {{ $iotDevice->device_code }} to this hive
        </button>
    </form>
@endif
