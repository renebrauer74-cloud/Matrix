<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\JobPositionRequest;
use App\Models\JobPosition;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class JobPositionController extends Controller
{
    public function index(): View
    {
        return view('admin.job-positions.index', [
            'positions' => JobPosition::query()->ordered()->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.job-positions.form', [
            'position' => null,
            'nextSortOrder' => (int) JobPosition::query()->max('sort_order') + 10,
        ]);
    }

    public function store(JobPositionRequest $request): RedirectResponse
    {
        $position = JobPosition::query()->create($request->positionAttributes());

        return to_route('admin.job-positions.index')->with('success', '„'.$position->title.'“ wurde angelegt.');
    }

    public function edit(JobPosition $jobPosition): View
    {
        return view('admin.job-positions.form', [
            'position' => $jobPosition,
            'nextSortOrder' => $jobPosition->sort_order,
        ]);
    }

    public function update(JobPositionRequest $request, JobPosition $jobPosition): RedirectResponse
    {
        $jobPosition->update($request->positionAttributes());

        return to_route('admin.job-positions.index')->with('success', 'Änderungen an „'.$jobPosition->title.'“ gespeichert.');
    }

    public function toggle(JobPosition $jobPosition): RedirectResponse
    {
        $jobPosition->update(['is_active' => ! $jobPosition->is_active]);

        return back(fallback: route('admin.job-positions.index'))->with('success', '„'.$jobPosition->title.'“ ist jetzt '
            .($jobPosition->is_active ? 'auf der Website sichtbar.' : 'ausgeblendet.'));
    }

    public function destroy(JobPosition $jobPosition): RedirectResponse
    {
        $jobPosition->delete();

        return to_route('admin.job-positions.index')->with('success', '„'.$jobPosition->title.'“ wurde gelöscht.');
    }
}
