<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Ambang kemiripan nama atlet
    |--------------------------------------------------------------------------
    |
    | Dipakai AthleteMatcher untuk peringatan W-03. Nilai antara 0 dan 1.
    | Sistem tidak pernah menggabungkan otomatis; panitia yang memutuskan.
    |
    */
    'athlete_name_similarity_threshold' => (float) env('ATHLETE_NAME_SIMILARITY_THRESHOLD', 0.78),

    'import' => [
        'max_bytes' => 5 * 1024 * 1024,
        'max_rows' => 2000,
        'queue_after_rows' => 200,
        'chunk_size' => 500,
        'file_retention_days' => 90,
        'memory_limit' => '512M',
        'time_limit' => 120,
    ],

    'swim_time' => [
        'fast_input' => (bool) env('SWIM_TIME_FAST_INPUT', true),
        'no_time_format' => env('SWIM_TIME_NO_TIME_FORMAT', 'NT'),
        'bounds' => [
            25 => ['min_ms' => 10_000, 'max_ms' => 300_000],
            50 => ['min_ms' => 20_000, 'max_ms' => 480_000],
            100 => ['min_ms' => 45_000, 'max_ms' => 900_000],
        ],
    ],

];
