{{-- Side panel for the hardware team forms. Shows next steps when registering, a team summary when editing. --}}
@if(isset($hardwareTeam))
    <div class="card mb-3">
        <div class="card-header"><i class="bi bi-people me-1"></i>{{ $hardwareTeam->name }}</div>
        <div class="card-body">
            <dl class="row mb-0 device-facts" style="font-size:0.82rem;">
                <dt class="col-5">Status</dt>
                <dd class="col-7">
                    @if($hardwareTeam->is_active)<span class="badge badge-active">Active</span>
                    @else<span class="badge badge-offline">Inactive</span>@endif
                </dd>
                <dt class="col-5">Devices</dt>
                <dd class="col-7">{{ $hardwareTeam->devices()->count() }}</dd>
                <dt class="col-5">Members</dt>
                <dd class="col-7 mb-0">{{ $hardwareTeam->members()->count() }}</dd>
            </dl>
        </div>
    </div>
@else
    <div class="card mb-3">
        <div class="card-header"><i class="bi bi-signpost-2 me-1"></i>What happens next</div>
        <div class="card-body">
            <ol class="next-steps">
                <li><strong>Add the team's members</strong>. Active members also receive this team's device alerts.</li>
                <li><strong>Register the team's devices</strong>. Each device belongs to exactly one team.</li>
                <li>When a device has a problem, the team is <strong>alerted</strong> by email, and by SMS when a battery is critical.</li>
            </ol>
        </div>
    </div>
@endif

<div class="card">
    <div class="card-header"><i class="bi bi-info-circle me-1"></i>What a hardware team is</div>
    <div class="card-body" style="font-size:0.8rem;color:#37433D;">
        The group of people who build, install and repair a set of IoT devices. The system uses the team
        to decide who is told when one of those devices goes offline or runs low on battery.
    </div>
</div>
