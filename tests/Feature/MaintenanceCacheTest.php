<?php

use App\Models\User;
use Illuminate\Support\Facades\Artisan;

it('lets master data managers clear application cache from the web', function () {
    Artisan::shouldReceive('call')->once()->with('optimize:clear');

    $panitia = User::factory()->panitia()->create();

    $this->actingAs($panitia)
        ->from(route('admin.competitions.index'))
        ->post(route('admin.maintenance.clear-cache'))
        ->assertRedirect(route('admin.competitions.index'))
        ->assertSessionHas('status');
});

it('forbids cache clear for judges', function () {
    $juri = User::factory()->juri()->create();

    $this->actingAs($juri)
        ->post(route('admin.maintenance.clear-cache'))
        ->assertForbidden();
});
