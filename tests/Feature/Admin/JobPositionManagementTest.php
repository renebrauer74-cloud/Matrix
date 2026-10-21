<?php

use App\Models\JobPosition;

it('starts with the bar, cloakroom and box office positions', function () {
    actingAsAdmin()->get(route('admin.job-positions.index'))
        ->assertOk()
        ->assertSeeInOrder(['Service an der Theke', 'Garderobe', 'Kasse']);
});

it('creates a position', function () {
    actingAsAdmin()->post(route('admin.job-positions.store'), [
        'title' => 'Lichttechnik',
        'summary' => 'Licht an!',
        'description' => "Licht stellen\nShows fahren",
        'requirements' => 'Erfahrung mit Pulten',
        'employment' => 'Minijob',
        'sort_order' => 40,
        'is_active' => '1',
    ])->assertRedirect(route('admin.job-positions.index'));

    expect(JobPosition::query()->where('title', 'Lichttechnik')->sole())
        ->is_active->toBeTrue()
        ->sort_order->toBe(40);
});

it('requires a title', function () {
    actingAsAdmin()->post(route('admin.job-positions.store'), ['title' => ''])->assertSessionHasErrors('title');
});

it('updates a position and hides it when the switch is off', function () {
    $position = JobPosition::factory()->create();

    actingAsAdmin()->put(route('admin.job-positions.update', $position), ['title' => 'Kasse & Einlass', 'sort_order' => 5])
        ->assertRedirect(route('admin.job-positions.index'));

    expect($position->fresh())
        ->title->toBe('Kasse & Einlass')
        ->is_active->toBeFalse();
});

it('toggles the visibility of a position', function () {
    $position = JobPosition::factory()->create();

    actingAsAdmin()->post(route('admin.job-positions.toggle', $position));
    expect($position->fresh()->is_active)->toBeFalse();

    $this->post(route('admin.job-positions.toggle', $position));
    expect($position->fresh()->is_active)->toBeTrue();
});

it('deletes a position', function () {
    $position = JobPosition::factory()->create();

    actingAsAdmin()->delete(route('admin.job-positions.destroy', $position))->assertRedirect(route('admin.job-positions.index'));

    expect(JobPosition::query()->find($position->id))->toBeNull();
});
