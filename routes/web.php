<?php

use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\Admin\AgeGroupController;
use App\Http\Controllers\Admin\AthleteMergeController;
use App\Http\Controllers\Admin\ClubController;
use App\Http\Controllers\Admin\CompetitionController;
use App\Http\Controllers\Admin\CompetitionReadinessController;
use App\Http\Controllers\Admin\CompetitionStatusController;
use App\Http\Controllers\Admin\EligibilityMatrixController;
use App\Http\Controllers\Admin\EventController;
use App\Http\Controllers\Admin\ExportController;
use App\Http\Controllers\Admin\HeatLaneController;
use App\Http\Controllers\Admin\ImportController;
use App\Http\Controllers\Admin\ImportTemplateController;
use App\Http\Controllers\Admin\JudgeAssignmentController;
use App\Http\Controllers\Admin\RegistrationEntryController;
use App\Http\Controllers\Admin\RegistrationVerificationController;
use App\Http\Controllers\Admin\ResultCorrectionController;
use App\Http\Controllers\Admin\ResultOverviewController;
use App\Http\Controllers\Admin\ResultVerificationController;
use App\Http\Controllers\Admin\SeedingController;
use App\Http\Controllers\Admin\SitePageController;
use App\Http\Controllers\Admin\StartListPdfController;
use App\Http\Controllers\Admin\SubmissionController;
use App\Http\Controllers\AthleteController;
use App\Http\Controllers\AthleteResultController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\CertificateController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\Judge\HeatLaneResultController;
use App\Http\Controllers\Judge\HeatResultController;
use App\Http\Controllers\Judge\TaskListController;
use App\Http\Controllers\Public\ArchiveController;
use App\Http\Controllers\Public\AthleteSearchController;
use App\Http\Controllers\Public\CertificateVerificationController;
use App\Http\Controllers\Public\CompetitionInfoController;
use App\Http\Controllers\Public\HomeController;
use App\Http\Controllers\Public\PageController;
use App\Http\Controllers\Public\RegistrationController;
use App\Http\Controllers\Public\SitemapController;
use App\Http\Controllers\ResultController;
use App\Http\Controllers\ResultsPdfController;
use App\Http\Controllers\SecureFileController;
use App\Http\Controllers\StartListController;
use App\Http\Controllers\StartListPdfDownloadController;
use App\Http\Middleware\CachePublicPages;
use Illuminate\Support\Facades\Route;

Route::middleware(CachePublicPages::class.':300')->group(function (): void {
    Route::get('/', HomeController::class)->name('home');
    Route::get('/about', [PageController::class, 'about'])->name('about');
    Route::get('/terms', [PageController::class, 'terms'])->name('terms');
    Route::get('/archive', [ArchiveController::class, 'index'])->name('archive.index');
    Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');

    Route::get('competitions/{competition}/schedule', [CompetitionInfoController::class, 'schedule'])->name('public.competitions.schedule');
    Route::get('competitions/{competition}/start-list', [StartListController::class, 'show'])->name('start-list.show');
    Route::get('competitions/{competition}/start-list/pdf', [StartListPdfDownloadController::class, 'download'])->name('start-list.pdf');
    Route::get('competitions/{competition}/results', [ResultController::class, 'index'])->name('results.index');
    Route::get('competitions/{competition}/results/pdf', [ResultsPdfController::class, 'download'])->name('results.pdf');
    Route::get('competitions/{competition}/results/medals', [ResultController::class, 'medals'])->name('results.medals');
    Route::get('competitions/{competition}/results/standings', [ResultController::class, 'standings'])->name('results.standings');
    Route::get('competitions/{competition}/results/events/{event}/age-groups/{ageGroup}', [ResultController::class, 'show'])->name('results.show');
    Route::get('competitions/{competition}/athletes/{athlete}/results', [AthleteResultController::class, 'show'])->name('results.athlete');
});

Route::get('sertifikat/verifikasi/{code}', [CertificateVerificationController::class, 'show'])->name('certificates.verify');
Route::get('sertifikat/arsip/{token}', [CertificateController::class, 'downloadArchive'])->name('certificates.archives.download');
Route::get('sertifikat/{certificate}/pdf', [CertificateController::class, 'download'])->name('certificates.download');

Route::middleware('throttle:athlete-search')->group(function (): void {
    Route::get('/search/athletes', [AthleteSearchController::class, 'index'])->name('public.athletes.search');
    Route::get('/profil-atlet/{athlete}', [AthleteSearchController::class, 'show'])->name('public.athletes.show');
});

Route::get('/health', HealthController::class)->name('health');

/*
 * Pendaftaran peserta tidak memerlukan akun. Yang login hanya panitia, super admin,
 * dan juri; alur di bawah ini sepenuhnya publik dan dibatasi rate limiter.
 */
Route::prefix('daftar')->name('register.')->group(function (): void {
    Route::get('/', [RegistrationController::class, 'index'])->name('index');
    Route::post('parse-waktu', [RegistrationController::class, 'parseTime'])
        ->middleware('throttle:athlete-search')
        ->name('parse-time');
    Route::get('selesai/{submission:code}', [RegistrationController::class, 'done'])->name('done');

    Route::get('{competition}', [RegistrationController::class, 'create'])->name('create');
    Route::post('{competition}/atlet', [RegistrationController::class, 'storeRegistrant'])
        ->middleware('throttle:public-registration')
        ->name('athlete');
    Route::get('{competition}/nomor', [RegistrationController::class, 'events'])->name('events');
    Route::post('{competition}/nomor', [RegistrationController::class, 'storeEvents'])->name('events.store');
    Route::get('{competition}/tinjau', [RegistrationController::class, 'review'])->name('review');
    Route::post('{competition}/kirim', [RegistrationController::class, 'store'])
        ->middleware('throttle:public-registration')
        ->name('store');
});

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:login');
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

        Route::get('competitions/{competition}/submissions', [SubmissionController::class, 'index'])->name('submissions.index');
        Route::get('submissions/{submission}', [SubmissionController::class, 'show'])->name('submissions.show');

        Route::get('competitions/{competition}/registrations/create', [RegistrationEntryController::class, 'create'])->name('registrations.create');
        Route::post('competitions/{competition}/registrations', [RegistrationEntryController::class, 'store'])->name('registrations.store');
        Route::put('registrations/{registration}', [RegistrationEntryController::class, 'update'])->name('registrations.update');
        Route::delete('registrations/{registration}', [RegistrationEntryController::class, 'destroy'])->name('registrations.destroy');

        Route::get('competitions/{competition}/registrations', [RegistrationVerificationController::class, 'index'])->name('registrations.index');
        Route::patch('registrations/{registration}/approve', [RegistrationVerificationController::class, 'approve'])->name('registrations.approve');
        Route::patch('registrations/{registration}/reject', [RegistrationVerificationController::class, 'reject'])->name('registrations.reject');
        Route::post('competitions/{competition}/registrations/approve', [RegistrationVerificationController::class, 'bulkApprove'])->name('registrations.bulk-approve');
        Route::post('competitions/{competition}/registrations/reject', [RegistrationVerificationController::class, 'bulkReject'])->name('registrations.bulk-reject');

        Route::get('competitions/{competition}/imports', [ImportController::class, 'index'])->name('imports.index');
        Route::post('competitions/{competition}/imports', [ImportController::class, 'store'])
            ->middleware('throttle:uploads')
            ->name('imports.store');
        Route::get('competitions/{competition}/imports/template', ImportTemplateController::class)->name('imports.template');
        Route::get('imports/{importBatch}', [ImportController::class, 'show'])->name('imports.show');
        Route::get('imports/{importBatch}/progress', [ImportController::class, 'progress'])->name('imports.progress');
        Route::get('imports/{importBatch}/errors', [ImportController::class, 'errors'])->name('imports.errors');
        Route::patch('imports/{importBatch}/rows/{excelRow}', [ImportController::class, 'updateRow'])->name('imports.rows.update');
        Route::post('imports/{importBatch}/commit', [ImportController::class, 'commit'])->name('imports.commit');
        Route::delete('imports/{importBatch}', [ImportController::class, 'destroy'])->name('imports.destroy');

        Route::get('competitions/{competition}/seeding', [SeedingController::class, 'index'])->name('seeding.index');
        Route::post('competitions/{competition}/seeding', [SeedingController::class, 'run'])->name('seeding.run');
        Route::post('competitions/{competition}/seeding/lock', [SeedingController::class, 'lock'])->name('seeding.lock');
        Route::get('competitions/{competition}/seeding/events/{event}/age-groups/{ageGroup}', [SeedingController::class, 'show'])->name('seeding.show');
        Route::post('heat-lanes/swap', [HeatLaneController::class, 'swap'])->name('heat-lanes.swap');
        Route::post('heat-lanes/{heatLane}/move', [HeatLaneController::class, 'move'])->name('heat-lanes.move');
        Route::delete('heat-lanes/{heatLane}', [HeatLaneController::class, 'withdraw'])->name('heat-lanes.withdraw');

        Route::get('competitions/{competition}/start-list', [StartListPdfController::class, 'index'])->name('start-list.index');
        Route::get('competitions/{competition}/start-list/pdf', [StartListPdfController::class, 'book'])->name('start-list.pdf');
        Route::get('competitions/{competition}/start-list/results', [StartListPdfController::class, 'resultSheets'])->name('start-list.results');
        Route::get('competitions/{competition}/results/book-pdf', [ResultsPdfController::class, 'download'])->name('results.book-pdf');

        Route::get('competitions/{competition}/judges', [JudgeAssignmentController::class, 'edit'])->name('judges.edit');
        Route::put('competitions/{competition}/judges', [JudgeAssignmentController::class, 'update'])->name('judges.update');
        Route::get('competitions/{competition}/results', [ResultOverviewController::class, 'index'])->name('results.index');
        Route::get('competitions/{competition}/results/verify', [ResultVerificationController::class, 'index'])->name('results.verify');
        Route::post('competitions/{competition}/events/{event}/results/verify', [ResultVerificationController::class, 'verifyEvent'])->name('results.verify-event');
        Route::post('heats/{heat}/verify-results', [ResultVerificationController::class, 'verifyHeat'])->name('results.verify-heat');
        Route::get('competitions/{competition}/events/{event}/results', [ResultCorrectionController::class, 'index'])->name('results.show');
        Route::put('results/{result}', [ResultCorrectionController::class, 'update'])->name('results.correct');
        Route::post('heats/{heat}/unlock-results', [ResultCorrectionController::class, 'unlock'])->name('heats.unlock');

        Route::get('competitions/{competition}/exports', [ExportController::class, 'index'])->name('exports.index');
        Route::get('competitions/{competition}/exports/participants', [ExportController::class, 'participants'])->name('exports.participants');
        Route::get('competitions/{competition}/exports/start-list', [ExportController::class, 'startList'])->name('exports.start-list');
        Route::get('competitions/{competition}/exports/results', [ExportController::class, 'results'])->name('exports.results');
        Route::get('competitions/{competition}/exports/medals', [ExportController::class, 'medals'])->name('exports.medals');
        Route::get('competitions/{competition}/exports/blank-results', [ExportController::class, 'blankResults'])->name('exports.blank-results');
        Route::post('competitions/{competition}/exports/blank-results', [ExportController::class, 'importBlankResults'])->name('exports.blank-results.import');
        Route::post('competitions/{competition}/certificates/settings', [CertificateController::class, 'settings'])->name('certificates.settings');

        Route::get('site-pages', [SitePageController::class, 'index'])->name('site-pages.index');
        Route::get('site-pages/{sitePage}/edit', [SitePageController::class, 'edit'])->name('site-pages.edit');
        Route::put('site-pages/{sitePage}', [SitePageController::class, 'update'])->name('site-pages.update');

        Route::get('activity-logs', [ActivityLogController::class, 'index'])->name('activity-logs.index');
        Route::get('activity-logs/for-subject', [ActivityLogController::class, 'forSubject'])->name('activity-logs.subject');
        Route::get('activity-logs/{activityLog}', [ActivityLogController::class, 'show'])->name('activity-logs.show');

        Route::resource('competitions', CompetitionController::class);
    });

    Route::get('judge/tasks', [TaskListController::class, 'index'])->name('judge.tasks');
    Route::get('judge/heats/{heat}', [HeatResultController::class, 'show'])->name('judge.heats.show');
    Route::post('judge/heats/{heat}/lock', [HeatResultController::class, 'lock'])->name('judge.heats.lock');
    Route::put('judge/lanes/{heatLane}/result', [HeatLaneResultController::class, 'upsert'])->name('judge.lanes.results.upsert');

    Route::get('files/athletes/{athlete}/photo', [SecureFileController::class, 'athletePhoto'])->name('files.athletes.photo');

    Route::get('competitions/{competition}/certificates', [CertificateController::class, 'index'])->name('certificates.index');
    Route::post('competitions/{competition}/certificates/archive', [CertificateController::class, 'requestArchive'])->name('certificates.archive');

    Route::resource('athletes', AthleteController::class);
});
