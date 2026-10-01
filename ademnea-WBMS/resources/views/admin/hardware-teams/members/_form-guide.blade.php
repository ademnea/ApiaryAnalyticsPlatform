{{-- Side panel for the team member forms. Expects: $hardwareTeam. --}}
<div class="card mb-3">
    <div class="card-header"><i class="bi bi-people me-1"></i>{{ $hardwareTeam->name }}</div>
    <div class="card-body">
        <dl class="row mb-0 device-facts" style="font-size:0.82rem;">
            <dt class="col-5">Country</dt>
            <dd class="col-7">{{ $hardwareTeam->country }}</dd>
            <dt class="col-5">Members</dt>
            <dd class="col-7 mb-0">{{ $hardwareTeam->members()->count() }}</dd>
        </dl>
    </div>
</div>

<div class="card">
    <div class="card-header"><i class="bi bi-info-circle me-1"></i>About team members</div>
    <div class="card-body" style="font-size:0.8rem;color:#37433D;">
        <p class="mb-2">
            Members are the people responsible for this team's devices. Adding someone here does not
            give them a login to this system.
        </p>
        <p class="mb-0">
            An active member with an email receives this team's device alerts. One with a phone number
            also gets an SMS when a device's battery is critical.
        </p>
    </div>
</div>
