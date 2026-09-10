<?php

use App\Models\User;

it('shows a short consistent sidebar of primary features only', function () {
    $meet = openRegistrationMeet();
    $panitia = User::factory()->panitia()->create();
    $labels = [
        'Dasbor',
        'Pendaftaran',
        'Pembagian seri',
        'Buku acara',
        'Hasil',
        'Klub',
        'Atlet',
    ];

    $pages = [
        $this->actingAs($panitia)->get(route('admin.competitions.index')),
        $this->actingAs($panitia)->get(route('admin.competitions.show', $meet['competition'])),
        $this->actingAs($panitia)->get(route('admin.clubs.index')),
    ];

    foreach ($pages as $page) {
        $page->assertOk();
        foreach ($labels as $label) {
            $page->assertSee($label);
        }
    }

    $this->actingAs($panitia)
        ->get(route('admin.clubs.index'))
        ->assertDontSee('Kelompok umur')
        ->assertDontSee('Matriks kelayakan')
        ->assertDontSee('Verifikasi hasil')
        ->assertDontSee('Tugas juri');
});
