<?php

namespace App\Services;

use App\Http\Requests\StoreScholarshipRequest;
use App\Http\Requests\UpdateScholarshipRequest;
use App\Models\Scholarship;
use Illuminate\Support\Facades\Storage;

class ScholarshipService
{
    public function createScholarship(StoreScholarshipRequest $request): Scholarship
    {
        $data = $request->validated();
        $data['is_featured'] = $request->boolean('is_featured');

        if ($request->hasFile('banner_image')) {
            $data['banner_image'] = $request->file('banner_image')->store('scholarships/banners', 'public');
        }

        $scholarship = Scholarship::create($data);

        if ($request->hasFile('attachment_files')) {
            $this->storeAttachments($scholarship, $request->file('attachment_files'));
        }

        return $scholarship;
    }

    public function updateScholarship(UpdateScholarshipRequest $request, Scholarship $scholarship): Scholarship
    {
        $data = $request->validated();
        $data['is_featured'] = $request->boolean('is_featured');

        if ($request->hasFile('banner_image')) {
            if ($scholarship->banner_image) {
                Storage::disk('public')->delete($scholarship->banner_image);
            }
            $data['banner_image'] = $request->file('banner_image')->store('scholarships/banners', 'public');
        }

        $scholarship->update($data);

        if ($request->hasFile('attachment_files')) {
            $this->storeAttachments($scholarship, $request->file('attachment_files'));
        }

        return $scholarship;
    }

    public function deleteScholarship(Scholarship $scholarship): void
    {
        if ($scholarship->banner_image) {
            Storage::disk('public')->delete($scholarship->banner_image);
        }

        foreach ($scholarship->attachments as $attachment) {
            Storage::disk('public')->delete($attachment->file_path);
        }

        $scholarship->delete();
    }

    protected function storeAttachments(Scholarship $scholarship, array $files): void
    {
        foreach ($files as $file) {
            if (! $file) {
                continue;
            }

            $path = $file->store('scholarships/attachments', 'public');
            $scholarship->attachments()->create([
                'file_name' => $file->getClientOriginalName(),
                'file_path' => $path,
                'file_type' => $file->getClientMimeType(),
            ]);
        }
    }
}
