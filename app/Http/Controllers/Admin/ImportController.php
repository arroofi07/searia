<?php

namespace App\Http\Controllers\Admin;

use App\Actions\CommitImportBatch;
use App\DataTransferObjects\ParticipantRow;
use App\Enums\ImportStatus;
use App\Exceptions\CannotCancelImportBatchException;
use App\Exceptions\ImportLimitExceededException;
use App\Exceptions\MissingImportColumnsException;
use App\Exports\ImportErrorReportExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreImportRequest;
use App\Http\Requests\UpdateImportRowRequest;
use App\Jobs\CommitImportBatch as CommitImportBatchJob;
use App\Models\Competition;
use App\Models\ImportBatch;
use App\Services\Import\ImportUploadService;
use App\Services\Import\RowValidator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ImportController extends Controller
{
    public function index(Competition $competition): View
    {
        $this->authorize('viewAny', ImportBatch::class);

        $batches = ImportBatch::query()
            ->where('competition_id', $competition->id)
            ->latest()
            ->get();

        return view('admin.imports.index', compact('competition', 'batches'));
    }

    public function store(StoreImportRequest $request, Competition $competition, ImportUploadService $uploads): RedirectResponse
    {
        abort_unless($competition->isOpenForRegistration(), 403, 'Pendaftaran sudah ditutup');

        try {
            $batch = $uploads->store($competition, $request->user(), $request->file('file'));
        } catch (MissingImportColumnsException $exception) {
            return back()->withErrors(['file' => $exception->getMessage()]);
        } catch (ImportLimitExceededException $exception) {
            return back()->withErrors(['file' => $exception->getMessage()]);
        }

        return redirect()
            ->route('admin.imports.show', $batch)
            ->with('status', $batch->status === ImportStatus::Validating
                ? 'Berkas besar sedang divalidasi di latar belakang.'
                : 'Berkas selesai divalidasi.');
    }

    public function show(ImportBatch $importBatch): View
    {
        $this->authorize('view', $importBatch);

        $result = $importBatch->result();
        $progress = (int) Cache::get('import-progress.'.$importBatch->id, $importBatch->status === ImportStatus::Validating ? 10 : 100);

        return view('admin.imports.show', [
            'batch' => $importBatch,
            'competition' => $importBatch->competition,
            'result' => $result,
            'progress' => $progress,
        ]);
    }

    public function updateRow(
        UpdateImportRowRequest $request,
        ImportBatch $importBatch,
        int $excelRow,
        RowValidator $validator,
    ): RedirectResponse {
        abort_unless($importBatch->status === ImportStatus::Validated, 403);

        $result = $importBatch->result();
        $current = $result->rowByExcelNumber($excelRow);

        abort_unless($current !== null, 404);

        $updated = ParticipantRow::fromFields([
            ...$current->row->toArray(),
            ...$request->validated(),
        ], $excelRow);

        $rows = [];
        foreach ($result->participantRows() as $row) {
            $rows[] = $row->excelRow === $excelRow ? $updated : $row;
        }

        $importBatch->storeResult($validator->validateMany($importBatch->competition, $rows, $request->user()));

        return back()->with('status', 'Baris '.$excelRow.' divalidasi ulang.');
    }

    public function commit(Request $request, ImportBatch $importBatch, CommitImportBatch $action): RedirectResponse
    {
        $this->authorize('update', $importBatch);
        abort_unless($importBatch->status === ImportStatus::Validated, 403);
        abort_unless($importBatch->competition->isOpenForRegistration(), 403, 'Pendaftaran sudah ditutup');

        $valid = $importBatch->valid_rows;
        $queueAfter = (int) config('searia.import.queue_after_rows', 200);

        if ($valid > $queueAfter) {
            CommitImportBatchJob::dispatch($importBatch->id);

            return back()->with('status', 'Penyimpanan '.$valid.' baris dijalankan di latar belakang.');
        }

        $action->handle($importBatch);

        return redirect()
            ->route('admin.imports.show', $importBatch)
            ->with('status', $valid.' baris valid berhasil diimpor.');
    }

    public function destroy(ImportBatch $importBatch): RedirectResponse
    {
        $this->authorize('delete', $importBatch);

        try {
            $importBatch->cancel();
        } catch (CannotCancelImportBatchException $exception) {
            return back()->withErrors(['delete' => $exception->getMessage()]);
        }

        return redirect()
            ->route('admin.imports.index', $importBatch->competition)
            ->with('status', 'Batch import dibatalkan.');
    }

    public function errors(ImportBatch $importBatch): BinaryFileResponse
    {
        $this->authorize('view', $importBatch);

        $filename = 'kesalahan-'.$importBatch->id.'.xlsx';

        return Excel::download(
            new ImportErrorReportExport($importBatch->result()->invalidRows()),
            $filename,
        );
    }

    public function progress(ImportBatch $importBatch): \Illuminate\Http\JsonResponse
    {
        $this->authorize('view', $importBatch);

        return response()->json([
            'status' => $importBatch->status->value,
            'progress' => (int) Cache::get('import-progress.'.$importBatch->id, 0),
            'valid' => $importBatch->valid_rows,
            'invalid' => $importBatch->invalid_rows,
        ]);
    }
}
