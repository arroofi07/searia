<?php

namespace App\Services\Import;

use App\Actions\CommitImportBatch;
use App\Actions\ImportEventProgram;
use App\Enums\CompetitionStatus;
use App\Enums\ImportStatus;
use App\Exceptions\ImportLimitExceededException;
use App\Exceptions\MissingImportColumnsException;
use App\Models\Competition;
use App\Models\ImportBatch;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class WorkbookImportService
{
    public function __construct(
        private readonly ImportEventProgram $programImport,
        private readonly WorkbookTimeNormalizer $normalizer,
        private readonly ParticipantFileReader $reader,
        private readonly CommitImportBatch $commit,
    ) {}

    /**
     * @return array{
     *     batch: ImportBatch|null,
     *     program: array{created: int, updated: int, groups_created: int, errors: list<string>}|null,
     *     program_skipped: bool,
     *     participants_skipped: bool,
     *     registration_opened: bool,
     *     committed_rows: int,
     *     participant_error: string|null
     * }
     */
    public function handle(Competition $competition, User $user, UploadedFile $file): array
    {
        $extension = strtolower($file->getClientOriginalExtension() ?: 'xlsx');
        $storedPath = \App\Support\UploadedFileGuard::storePrivate(
            $file,
            'imports/'.$competition->id,
            [
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'application/vnd.ms-excel',
                'application/zip',
                'application/octet-stream',
                'text/csv',
                'text/plain',
                'application/csv',
            ],
            (int) config('searia.import.max_bytes', 5 * 1024 * 1024),
        );

        $absolute = Storage::disk('local')->path($storedPath);
        $program = null;
        $programSkipped = strtolower($extension) === 'csv';
        $registrationOpened = false;

        if (! $programSkipped) {
            try {
                $program = $this->programImport->handle($competition->fresh(), $absolute, $extension);
            } catch (MissingImportColumnsException) {
                $program = [
                    'created' => 0,
                    'updated' => 0,
                    'groups_created' => 0,
                    'errors' => [],
                ];
            }
        }

        $competition = $competition->fresh();

        if ($competition->status === CompetitionStatus::Draft) {
            $competition->update(['status' => CompetitionStatus::Registration]);
            $competition = $competition->fresh();
            $registrationOpened = true;
        }

        $participantsSkipped = ! $competition->isOpenForRegistration();
        $participantError = null;
        $batch = null;
        $committedRows = 0;

        if ($participantsSkipped) {
            return $this->result($batch, $program, $programSkipped, true, $registrationOpened, 0, null);
        }

        $normalizedAbsolute = $absolute;

        if (strtolower($extension) !== 'csv') {
            $normalizedRelative = preg_replace('/\.xlsx$/i', '-normalized.xlsx', $storedPath) ?? $storedPath.'-normalized.xlsx';
            $normalizedAbsolute = $this->normalizer->normalize(
                $absolute,
                $extension,
                Storage::disk('local')->path($normalizedRelative),
            );
            $storedPath = $normalizedRelative;
        }

        try {
            $batch = $this->createParticipantBatch(
                $competition,
                $user,
                $file->getClientOriginalName(),
                $storedPath,
                $normalizedAbsolute,
                $extension,
            );

            if ($batch->status === ImportStatus::Validated && $batch->valid_rows > 0) {
                ini_set('memory_limit', (string) config('searia.import.memory_limit', '512M'));
                set_time_limit((int) config('searia.import.time_limit', 120));
                $this->commit->handle($batch);
                $batch = $batch->fresh();
                $committedRows = (int) $batch->valid_rows;
            }
        } catch (MissingImportColumnsException $exception) {
            $participantError = $exception->getMessage();
        } catch (ImportLimitExceededException $exception) {
            $participantError = $exception->getMessage();
        }

        return $this->result($batch, $program, $programSkipped, false, $registrationOpened, $committedRows, $participantError);
    }

    /**
     * @param  array{created: int, updated: int, groups_created: int, errors: list<string>}|null  $program
     * @return array{
     *     batch: ImportBatch|null,
     *     program: array{created: int, updated: int, groups_created: int, errors: list<string>}|null,
     *     program_skipped: bool,
     *     participants_skipped: bool,
     *     registration_opened: bool,
     *     committed_rows: int,
     *     participant_error: string|null
     * }
     */
    private function result(
        ?ImportBatch $batch,
        ?array $program,
        bool $programSkipped,
        bool $participantsSkipped,
        bool $registrationOpened,
        int $committedRows,
        ?string $participantError,
    ): array {
        return [
            'batch' => $batch,
            'program' => $program,
            'program_skipped' => $programSkipped,
            'participants_skipped' => $participantsSkipped,
            'registration_opened' => $registrationOpened,
            'committed_rows' => $committedRows,
            'participant_error' => $participantError,
        ];
    }

    private function createParticipantBatch(
        Competition $competition,
        User $user,
        string $originalFilename,
        string $storedPath,
        string $absolutePath,
        string $extension,
    ): ImportBatch {
        ini_set('memory_limit', (string) config('searia.import.memory_limit', '512M'));
        set_time_limit((int) config('searia.import.time_limit', 120));

        $batch = ImportBatch::query()->create([
            'competition_id' => $competition->id,
            'user_id' => $user->id,
            'original_filename' => $originalFilename,
            'stored_path' => $storedPath,
            'status' => ImportStatus::Uploaded,
        ]);

        // Workbook one-shot: always validate synchronously so peserta tersimpan tanpa queue worker.
        $result = $this->reader->readAndValidate($absolutePath, $extension, $competition->fresh());
        $batch->storeResult($result);

        return $batch->fresh();
    }
}
