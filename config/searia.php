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
