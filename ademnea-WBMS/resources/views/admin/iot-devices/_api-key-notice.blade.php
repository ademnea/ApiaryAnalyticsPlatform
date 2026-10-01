{{-- One-time API key shown right after a device is provisioned. --}}
@if(session('plaintext_api_key'))
    <div class="alert-ademnea mb-3" role="alert">
        <strong><i class="bi bi-key me-1"></i>Device API key — copy it now</strong>
        <p class="mb-2 mt-1">This is the only time the key is shown. Install it on the device as its <code>X-Api-Key</code> so it can send data.</p>
        <div class="d-flex flex-wrap align-items-center gap-2">
            <code id="device-api-key" style="font-size:0.9rem;background:#fff;padding:0.4rem 0.65rem;border-radius:6px;word-break:break-all;">{{ session('plaintext_api_key') }}</code>
            <button type="button" class="btn btn-sm btn-outline-forest" id="copy-api-key">
                <i class="bi bi-clipboard me-1"></i><span>Copy key</span>
            </button>
        </div>
    </div>

    @push('scripts')
        <script>
            document.getElementById('copy-api-key').addEventListener('click', function () {
                var label = this.querySelector('span');
                var key = document.getElementById('device-api-key').textContent.trim();
                navigator.clipboard.writeText(key).then(function () {
                    label.textContent = 'Copied';
                    setTimeout(function () { label.textContent = 'Copy key'; }, 2000);
                }).catch(function () {
                    label.textContent = 'Select the key and copy it manually';
                });
            });
        </script>
    @endpush
@endif
