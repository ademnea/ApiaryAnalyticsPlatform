<div class="card shadow-sm border-0 rounded-4">

    <div class="card-header bg-white border-0 py-3">
        <h5 class="fw-bold mb-0">
            Work Package Information
        </h5>
    </div>

    <div class="card-body">

        <div class="row">

            {{-- LEFT SIDE --}}
            <div class="col-lg-8">

                {{-- Work Package Number --}}
                <div class="mb-4">
                    <label class="form-label fw-semibold">
                        Work Package Number <span class="text-danger">*</span>
                    </label>

                    <input
                        type="text"
                        name="wp_number"
                        class="form-control @error('wp_number') is-invalid @enderror"
                        value="{{ old('wp_number', $workPackage->wp_number ?? '') }}"
                        placeholder="WP1">

                    @error('wp_number')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>


                {{-- Title --}}
                <div class="mb-4">
                    <label class="form-label fw-semibold">
                        Title <span class="text-danger">*</span>
                    </label>

                    <input
                        type="text"
                        name="title"
                        class="form-control @error('title') is-invalid @enderror"
                        value="{{ old('title', $workPackage->title ?? '') }}"
                        placeholder="Enter work package title">

                    @error('title')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>


                {{-- Summary --}}
                <div class="mb-4">

                    <label class="form-label fw-semibold">
                        Summary
                    </label>

                    <textarea
                        name="summary"
                        rows="3"
                        class="form-control @error('summary') is-invalid @enderror"
                        placeholder="Short summary">{{ old('summary', $workPackage->summary ?? '') }}</textarea>

                    @error('summary')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror

                </div>


                {{-- Description --}}
                <div class="mb-4">

                    <label class="form-label fw-semibold">
                        Description <span class="text-danger">*</span>
                    </label>

                    <textarea
                        name="description"
                        rows="6"
                        class="form-control @error('description') is-invalid @enderror"
                        placeholder="Detailed description">{{ old('description', $workPackage->description ?? '') }}</textarea>

                    @error('description')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror

                </div>


                {{-- Objectives --}}
                <div class="mb-4">

                    <label class="form-label fw-semibold">
                        Objectives <span class="text-danger">*</span>
                    </label>

                    <textarea
                        name="objectives"
                        rows="6"
                        class="form-control @error('objectives') is-invalid @enderror"
                        placeholder="Objectives">{{ old('objectives', $workPackage->objectives ?? '') }}</textarea>

                    @error('objectives')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror

                </div>


                {{-- Deliverables --}}
                <div class="mb-4">

                    <label class="form-label fw-semibold">
                        Deliverables <span class="text-danger">*</span>
                    </label>

                    <textarea
                        name="deliverables"
                        rows="6"
                        class="form-control @error('deliverables') is-invalid @enderror"
                        placeholder="Deliverables">{{ old('deliverables', $workPackage->deliverables ?? '') }}</textarea>

                    @error('deliverables')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror

                </div>

            </div>



            {{-- RIGHT SIDE --}}
            <div class="col-lg-4">

                <div class="card border rounded-4">

                    <div class="card-body">

                        {{-- Lead --}}
                        <div class="mb-3">

                            <label class="form-label fw-semibold">
                                Lead Institution / Team
                            </label>

                            <input
                                type="text"
                                name="lead"
                                class="form-control @error('lead') is-invalid @enderror"
                                value="{{ old('lead', $workPackage->lead ?? '') }}">

                            @error('lead')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror

                        </div>


                        {{-- Partners --}}
                        <div class="mb-3">

                            <label class="form-label fw-semibold">
                                Partners
                            </label>

                            <textarea
                                rows="4"
                                name="partners"
                                class="form-control @error('partners') is-invalid @enderror"
                                placeholder="Partner institutions">{{ old('partners', $workPackage->partners ?? '') }}</textarea>

                            @error('partners')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror

                        </div>



                        {{-- Image --}}
                        <div class="mb-3">

                            <label class="form-label fw-semibold">
                                Featured Image
                            </label>

                            <input
                                type="file"
                                class="form-control @error('featured_image') is-invalid @enderror"
                                name="featured_image">

                            @error('featured_image')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror

                        </div>


                        @isset($workPackage)

                            @if($workPackage->featured_image)

                                <div class="mb-4 text-center">

                                    <img
                                        src="{{ $workPackage->featured_image_url }}"
                                        class="img-fluid rounded shadow-sm"
                                        style="max-height:180px;">

                                </div>

                            @endif

                        @endisset



                        {{-- Status --}}
                        <div class="mb-3">

                            <label class="form-label fw-semibold">
                                Status
                            </label>

                            <select
                                class="form-select"
                                name="status">

                                <option value="Draft"
                                    @selected(old('status', $workPackage->status ?? '')=='Draft')>
                                    Draft
                                </option>

                                <option value="Published"
                                    @selected(old('status', $workPackage->status ?? '')=='Published')>
                                    Published
                                </option>

                                <option value="Archived"
                                    @selected(old('status', $workPackage->status ?? '')=='Archived')>
                                    Archived
                                </option>

                            </select>

                        </div>



                        {{-- Display Order --}}
                        <div class="mb-4">

                            <label class="form-label fw-semibold">
                                Display Order
                            </label>

                            <input
                                type="number"
                                name="display_order"
                                class="form-control"
                                value="{{ old('display_order', $workPackage->display_order ?? 0) }}">

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>


    <div class="card-footer bg-white border-0">

        <div class="d-flex justify-content-end gap-3">

            <a
                href="{{ route('admin.work-packages.index') }}"
                class="btn btn-light border px-4">

                Cancel

            </a>

            <button
                class="btn btn-success px-5">

                <i class="bi bi-check-circle me-2"></i>

                {{ isset($workPackage) ? 'Update Work Package' : 'Save Work Package' }}

            </button>

        </div>

    </div>

</div>