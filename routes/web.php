<?php

use App\Http\Controllers\Admin\AgeGroupController;
use App\Http\Controllers\Admin\AthleteMergeController;
use App\Http\Controllers\Admin\ClubController;
use App\Http\Controllers\Admin\CompetitionController;
use App\Http\Controllers\Admin\CompetitionReadinessController;
use App\Http\Controllers\Admin\CompetitionStatusController;
use App\Http\Controllers\Admin\EligibilityMatrixController;
use App\Http\Controllers\Admin\EventController;
use App\Http\Controllers\Admin\RegistrationVerificationController;
use App\Http\Controllers\AthleteController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Coach\ClubProfileController;
use App\Http\Controllers\Coach\RegistrationSummaryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\RegistrationController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store']);
});

Route::middleware('auth')->group(function (): void {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::prefix('admin')->name('admin.')->group(function (): void {
        Route::resource('clubs', ClubController::class);
        Route::patch('clubs/{club}/verify', [ClubController::class, 'verify'])->name('clubs.verify');
        Route::patch('clubs/{club}/reject', [ClubController::class, 'reject'])->name('clubs.reject');

        Route::get('athletes/{athlete}/merge', [AthleteMergeController::class, 'create'])->name('athletes.merge.create');
        Route::post('athletes/{athlete}/merge', [AthleteMergeController::class, 'store'])->name('athletes.merge.store');

        Route::post('competitions/{competition}/duplicate', [CompetitionController::class, 'duplicate'])->name('competitions.duplicate');
        Route::patch('competitions/{competition}/status', CompetitionStatusController::class)->name('competitions.status');
        Route::get('competitions/{competition}/readiness', CompetitionReadinessController::class)->name('competitions.readiness');
        Route::get('competitions/{competition}/eligibility', [EligibilityMatrixController::class, 'show'])->name('competitions.eligibility');
        Route::put('competitions/{competition}/eligibility', [EligibilityMatrixController::class, 'update'])->name('competitions.eligibility.update');

        Route::post('competitions/{competition}/age-groups/quick-fill', [AgeGroupController::class, 'quickFill'])->name('competitions.age-groups.quick-fill');
        Route::resource('competitions.age-groups', AgeGroupController::class)->except(['show', 'create', 'edit']);

        Route::post('competitions/{competition}/events/reorder', [EventController::class, 'reorder'])->name('competitions.events.reorder');
        Route::resource('competitions.events', EventController::class)->except(['show', 'create', 'edit']);

        Route::get('competitions/{competition}/registrations', [RegistrationVerificationController::class, 'index'])->name('registrations.index');
        Route::patch('registrations/{registration}/approve', [RegistrationVerificationController::class, 'approve'])->name('registrations.approve');
        Route::patch('registrations/{registration}/reject', [RegistrationVerificationController::class, 'reject'])->name('registrations.reject');
        Route::post('competitions/{competition}/registrations/approve', [RegistrationVerificationController::class, 'bulkApprove'])->name('registrations.bulk-approve');
        Route::post('competitions/{competition}/registrations/reject', [RegistrationVerificationController::class, 'bulkReject'])->name('registrations.bulk-reject');

        Route::resource('competitions', CompetitionController::class);
    });

    Route::get('coach/clubs/{club}', [ClubProfileController::class, 'show'])->name('coach.club.show');
    Route::get('coach/clubs/{club}/edit', [ClubProfileController::class, 'edit'])->name('coach.club.edit');
    Route::put('coach/clubs/{club}', [ClubProfileController::class, 'update'])->name('coach.club.update');

    Route::get('coach/competitions/{competition}/registrations', [RegistrationSummaryController::class, 'index'])->name('coach.registrations.index');
    Route::put('registrations/{registration}', [RegistrationSummaryController::class, 'update'])->name('registrations.update');
    Route::delete('registrations/{registration}', [RegistrationSummaryController::class, 'destroy'])->name('registrations.destroy');

    Route::get('register', [RegistrationController::class, 'index'])->name('registrations.index');
    Route::post('register/parse-time', [RegistrationController::class, 'parseTime'])->name('registrations.parse-time');
    Route::get('competitions/{competition}/register', [RegistrationController::class, 'create'])->name('registrations.create');
    Route::post('competitions/{competition}/register/athlete', [RegistrationController::class, 'storeAthlete'])->name('registrations.athlete');
    Route::get('competitions/{competition}/register/events', [RegistrationController::class, 'events'])->name('registrations.events');
    Route::post('competitions/{competition}/register/events', [RegistrationController::class, 'storeEvents'])->name('registrations.events.store');
    Route::get('competitions/{competition}/register/review', [RegistrationController::class, 'review'])->name('registrations.review');
    Route::post('competitions/{competition}/register', [RegistrationController::class, 'store'])->name('registrations.store');
    Route::get('competitions/{competition}/events/{event}/seed-suggestion', [RegistrationController::class, 'suggestSeedTime'])->name('registrations.suggest');

    Route::resource('athletes', AthleteController::class);
});
