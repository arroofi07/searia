<?php

namespace App\Http\Controllers\Admin;

use App\Actions\IssueInvoice;
use App\Enums\RegistrationStatus;
use App\Exceptions\CannotReissuePaidInvoiceException;
use App\Exceptions\EmptyInvoiceException;
use App\Http\Controllers\Controller;
use App\Http\Requests\IssueAllInvoicesRequest;
use App\Http\Requests\IssueInvoiceRequest;
use App\Models\Club;
use App\Models\Competition;
use App\Models\Invoice;
use App\Models\Registration;
use App\Services\Invoice\InvoicePdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\View\View;

class InvoiceController extends Controller
{
    public function index(Competition $competition): View
    {
        $this->authorize('viewAny', Invoice::class);

        $verifiedByClub = Registration::query()
            ->selectRaw('athletes.club_id as club_id, count(*) as verified_count')
            ->join('athletes', 'athletes.id', '=', 'registrations.athlete_id')
            ->where('registrations.competition_id', $competition->id)
            ->where('registrations.status', RegistrationStatus::Verified)
            ->groupBy('athletes.club_id')
            ->pluck('verified_count', 'club_id');

        $invoices = $competition->invoices()->with('club')->get()->keyBy('club_id');
        $clubIds = $verifiedByClub->keys()->merge($invoices->keys())->unique()->filter();
        $clubs = Club::query()->whereIn('id', $clubIds)->orderBy('name')->get();

        return view('admin.invoices.index', [
            'competition' => $competition,
            'clubs' => $clubs,
            'invoices' => $invoices,
            'verifiedByClub' => $verifiedByClub,
        ]);
    }

    public function store(IssueInvoiceRequest $request, Competition $competition, IssueInvoice $issue): RedirectResponse
    {
        $club = Club::query()->findOrFail($request->integer('club_id'));

        try {
            $invoice = $issue->handle($competition, $club, $request->date('due_at'));
        } catch (CannotReissuePaidInvoiceException $exception) {
            abort(Response::HTTP_UNPROCESSABLE_ENTITY, $exception->getMessage());
        } catch (EmptyInvoiceException $exception) {
            return back()->withErrors(['club_id' => $exception->getMessage()]);
        }

        return redirect()
            ->route('admin.invoices.show', $invoice)
            ->with('status', 'Tagihan '.$invoice->invoice_number.' diterbitkan.');
    }

    public function storeAll(IssueAllInvoicesRequest $request, Competition $competition, IssueInvoice $issue): RedirectResponse
    {
        $invoices = $issue->handleAll($competition, $request->date('due_at'));

        return redirect()
            ->route('admin.invoices.index', $competition)
            ->with('status', $invoices->count().' tagihan diterbitkan.');
    }

    public function show(Invoice $invoice): View
    {
        $this->authorize('view', $invoice);

        $invoice->load(['club', 'competition', 'verifier', 'registrations']);

        return view('admin.invoices.show', [
            'invoice' => $invoice,
            'competition' => $invoice->competition,
            'hasWithdrawnEntries' => $invoice->registrations
                ->contains(fn (Registration $registration): bool => $registration->status === RegistrationStatus::Withdrawn),
        ]);
    }

    public function pdf(Invoice $invoice, InvoicePdf $pdf): Response
    {
        $this->authorize('view', $invoice);

        $invoice->load(['club', 'competition']);

        return $pdf->download($invoice);
    }
}
