@if(is_null($apiary))
    <div class="alert alert-warning" style="font-size:0.82rem;">Apiary not found.</div>
@else
    <p class="text-muted mb-3" style="font-size:0.8rem;">
        Hives at <strong>{{ $apiary->name }}</strong> without a {{ str_replace('_', ' ', $iotDevice->device_type) }} device assigned:
    </p>

    <form action="{{ route('admin.iot-devices.assign.store', $iotDevice) }}" method="POST">
        @csrf
        <input type="hidden" name="apiary_id" value="{{ $apiaryId }}">

        @forelse($hives as $hive)
            <div class="form-check border rounded p-2 mb-2" style="border-color:var(--clr-border) !important;">
                <input class="form-check-input" type="radio" name="hive_id" id="hive-{{ $hive->id }}" value="{{ $hive->id }}" required>
                <label class="form-check-label w-100" for="hive-{{ $hive->id }}">
                    <span class="fw-medium">{{ $hive->display_name }}</span>
                    <span class="text-muted" style="font-size:0.75rem;"> — {{ $hive->hybrid_code }}</span>
                </label>
            </div>
        @empty
            <div class="text-muted text-center py-4" style="font-size:0.82rem;">
                Every hive at this apiary already has a {{ str_replace('_', ' ', $iotDevice->device_type) }} device assigned,
                or no hives are registered here yet.
            </div>
        @endforelse

        @if($hives->isNotEmpty())
            <button type="submit" class="btn btn-primary mt-2"><i class="bi bi-check-circle me-1"></i>Assign Device</button>
        @endif
    </form>
@endif