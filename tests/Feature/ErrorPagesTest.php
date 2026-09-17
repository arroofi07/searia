<?php

use Illuminate\Support\Facades\Route;

it('renders a branded 404 page for missing public urls', function () {
    $this->get('/halaman-yang-tidak-ada-di-searia')
        ->assertNotFound()
        ->assertSee('Halaman tidak ditemukan')
        ->assertSee('Kembali ke beranda')
        ->assertSee('Daftar lomba')
        ->assertSee('/brand/logo', false)
        ->assertDontSee('Not Found');
});

it('renders branded 401, 403, 419, 429, 500, and 503 pages', function (int $status, string $heading) {
    config(['app.debug' => false]);

    Route::middleware('web')->get('/__error-probe-'.$status, fn () => abort($status));

    $this->get('/__error-probe-'.$status)
        ->assertStatus($status)
        ->assertSee($heading)
        ->assertSee('Kembali ke beranda')
        ->assertSee('Aquatic SeaRIA');
})->with([
    [401, 'Sesi belum masuk'],
    [403, 'Anda tidak punya akses'],
    [419, 'Sesi formulir sudah habis'],
    [429, 'Terlalu banyak permintaan'],
    [500, 'Server sedang bermasalah'],
    [503, 'Sedang pemeliharaan'],
]);
