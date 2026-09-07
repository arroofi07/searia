<?php

namespace Database\Factories;

use App\Enums\ImportStatus;
use App\Models\Competition;
use App\Models\ImportBatch;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ImportBatch>
 */
class ImportBatchFactory extends Factory
{
    protected $model = ImportBatch::class;

    public function definition(): array
    {
        $name = fake()->unique()->lexify('peserta-??????.xlsx');

        return [
            'competition_id' => Competition::factory(),
            'user_id' => User::factory()->panitia(),
            'original_filename' => $name,
            'stored_path' => 'imports/'.$name,
            'total_rows' => 0,
            'valid_rows' => 0,
            'invalid_rows' => 0,
            'status' => ImportStatus::Uploaded,
            'errors' => null,
            'committed_at' => null,
        ];
    }
}
