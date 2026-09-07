<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class HealthController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $database = 'ok';
        try {
            DB::connection()->getPdo();
            DB::select('select 1');
        } catch (\Throwable) {
            $database = 'fail';
        }

        $queue = 'ok';
        try {
            if (Schema::hasTable('jobs') && Schema::hasTable('failed_jobs')) {
                $pending = DB::table('jobs')->count();
                $failed = DB::table('failed_jobs')->count();
                $queue = [
                    'status' => 'ok',
                    'pending' => $pending,
                    'failed' => $failed,
                ];
            }
        } catch (\Throwable) {
            $queue = 'fail';
        }

        $ok = $database === 'ok' && (is_array($queue) ? $queue['status'] === 'ok' : $queue === 'ok');

        return response()->json([
            'status' => $ok ? 'ok' : 'degraded',
            'database' => $database,
            'queue' => $queue,
            'app' => config('app.name'),
            'env' => config('app.env'),
        ], $ok ? 200 : 503);
    }
}
