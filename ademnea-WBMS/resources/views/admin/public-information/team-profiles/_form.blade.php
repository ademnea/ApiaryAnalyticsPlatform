@csrf

<div class="card border-0 shadow-sm rounded-4">

    <div class="card-body p-4">

        <div class="row g-4">

            {{-- Full Name --}}
            <div class="col-md-6">
                <label class="form-label fw-semibold">
                    Full Name <span class="text-danger">*</span>
                </label>

                <input
                    type="text"
                    name="full_name"
                    class="form-control @error('full_name') is-invalid @enderror"
                    value="{{ old('full_name', $teamProfile->full_name ?? '') }}"
                    placeholder="Enter full name">

                @error('full_name')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>


            {{-- Role --}}
            <div class="col-md-6">
                <label class="form-label fw-semibold">
                    Role <span class="text-danger">*</span>
                </label>

                <input
                    type="text"
                    name="role"
                    class="form-control @error('role') is-invalid @enderror"
                    value="{{ old('role', $teamProfile->role ?? '') }}"
                    placeholder="Project Coordinator">

                @error('role')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>


            {{-- Institution --}}
            <div class="col-md-6">
                <label class="form-label fw-semibold">
                    Institution
                </label>

                <input
                    type="text"
                    name="institution"
                    class="form-control @error('institution') is-invalid @enderror"
                    value="{{ old('institution', $teamProfile->institution ?? '') }}"
                    placeholder="Makerere University">

                @error('institution')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>


            {{-- Email --}}
            <div class="col-md-6">
                <label class="form-label fw-semibold">
                    Email
                </label>

                <input
                    type="email"
                    name="email"
                    class="form-control @error('email') is-invalid @enderror"
                    value="{{ old('email', $teamProfile->email ?? '') }}"
                    placeholder="example@email.com">

                @error('email')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>


            {{-- Phone --}}
            <div class="col-md-6">
                <label class="form-label fw-semibold">
                    Phone
                </label>

                <input
                    type="text"
                    name="phone"
                    class="form-control @error('phone') is-invalid @enderror"
                    value="{{ old('phone', $teamProfile->phone ?? '') }}"
                    placeholder="+256...">

                @error('phone')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>


            {{-- Display Order --}}
            <div class="col-md-6">
                <label class="form-label fw-semibold">
                    Display Order
                </label>

                <input
                    type="number"
                    name="display_order"
                    class="form-control @error('display_order') is-invalid @enderror"
                    value="{{ old('display_order', $teamProfile->display_order ?? 0) }}">

                @error('display_order')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>


            {{-- Research Interests --}}
            <div class="col-12">

                <label class="form-label fw-semibold">
                    Research Interests
                </label>

                <textarea
                    name="research_interests"
                    rows="3"
                    class="form-control @error('research_interests') is-invalid @enderror"
                    placeholder="Artificial Intelligence, IoT, Embedded Systems...">{{ old('research_interests', $teamProfile->research_interests ?? '') }}</textarea>

                @error('research_interests')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror

            </div>


            {{-- Biography --}}
            <div class="col-12">

                <label class="form-label fw-semibold">
                    Biography
                </label>

                <textarea
                    name="biography"
                    rows="6"
                    class="form-control @error('biography') is-invalid @enderror"
                    placeholder="Write a short biography...">{{ old('biography', $teamProfile->biography ?? '') }}</textarea>

                @error('biography')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror

            </div>


            {{-- Status --}}
            <div class="col-md-6">

                <label class="form-label fw-semibold">
                    Publication Status
                </label>

                <select
                    name="status"
                    class="form-select @error('status') is-invalid @enderror">

                    <option value="Draft"
                        @selected(old('status', $teamProfile->status ?? '')=='Draft')>
                        Draft
                    </option>

                    <option value="Published"
                        @selected(old('status', $teamProfile->status ?? '')=='Published')>
                        Published
                    </option>

                    <option value="Archived"
                        @selected(old('status', $teamProfile->status ?? '')=='Archived')>
                        Archived
                    </option>

                </select>

                @error('status')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror

            </div>


            {{-- Photo --}}
            <div class="col-md-6">

                <label class="form-label fw-semibold">
                    Profile Photograph
                </label>

                <input
                    type="file"
                    name="profile_photo"
                    class="form-control @error('profile_photo') is-invalid @enderror">

                @error('profile_photo')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror

                @isset($teamProfile)

                    @if($teamProfile->profile_photo)

                        <div class="mt-3">

                            <img
                                src="{{ $teamProfile->profile_photo_url }}"
                                width="120"
                                class="rounded shadow">

                        </div>

                    @endif

                @endisset

            </div>

        </div>

    </div>

</div>


<div class="d-flex justify-content-end mt-4">

    <a
        href="{{ route('admin.team-profiles.index') }}"
        class="btn btn-light border me-2">

        Cancel

    </a>

    <button
        type="submit"
        class="btn btn-success px-4">

        <i class="bi bi-check-circle me-2"></i>

        Save Team Member

    </button>

</div>