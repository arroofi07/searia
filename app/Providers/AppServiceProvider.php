<?php

namespace App\Providers;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Carbon::setLocale(config('app.locale', 'id'));

        Gate::before(function (User $user, string $ability): ?bool {
            if ($user->isSuperAdmin()) {
                return true;
            }

            return null;
        });

        RateLimiter::for('login', function (Request $request) {
            $email = strtolower((string) $request->input('email'));

            return [
                Limit::perMinute(5)->by($request->ip()),
                Limit::perMinute(5)->by($email.'|'.$request->ip()),
            ];
        });

        RateLimiter::for('athlete-search', function (Request $request) {
            return Limit::perMinute(30)->by($request->ip());
        });

        // Form pendaftaran terbuka tanpa akun, jadi hanya alamat IP yang bisa dipakai
        // sebagai pembatas. Angkanya longgar agar satu orang tetap bisa mendaftarkan
        // beberapa atlet berturut-turut dari jaringan yang sama.
        RateLimiter::for('public-registration', function (Request $request) {
            return [
                Limit::perMinute(10)->by($request->ip()),
                Limit::perHour(60)->by($request->ip()),
            ];
        });

        RateLimiter::for('uploads', function (Request $request) {
            return Limit::perHour(30)->by((string) ($request->user()?->id ?: $request->ip()));
        });

        RateLimiter::for('password-reset', function (Request $request) {
            return Limit::perMinute(3)->by(strtolower((string) $request->input('email')).'|'.$request->ip());
        });
    }
}
