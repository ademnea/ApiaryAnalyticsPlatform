<?php

namespace App\Services;

use App\Http\Requests\WorkPackageRequest;
use App\Models\WorkPackage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class WorkPackageService
{
    public function create(WorkPackageRequest $request): WorkPackage
    {
        $data = $request->validated();

        if ($request->hasFile('featured_image')) {
            $data['featured_image'] = $request
                ->file('featured_image')
                ->store('work-packages', 'public');
        }

        return WorkPackage::create($data);
    }

    public function update(
        WorkPackageRequest $request,
        WorkPackage $workPackage
    ): WorkPackage {

        $data = $request->validated();

        if ($request->hasFile('featured_image')) {

            if (
                $workPackage->featured_image &&
                Storage::disk('public')->exists($workPackage->featured_image)
            ) {
                Storage::disk('public')->delete($workPackage->featured_image);
            }

            $data['featured_image'] = $request
                ->file('featured_image')
                ->store('work-packages', 'public');
        }

        $workPackage->update($data);

        return $workPackage;
    }

    public function delete(WorkPackage $workPackage): void
    {
        if (
            $workPackage->featured_image &&
            Storage::disk('public')->exists($workPackage->featured_image)
        ) {
            Storage::disk('public')->delete($workPackage->featured_image);
        }

        $workPackage->delete();
    }
}