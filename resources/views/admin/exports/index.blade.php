@extends('layouts.app')

@section('title', 'Export · '.$competition->name)

@section('content')
    <h1 class="text-2xl font-semibold">Export &amp; sertifikat</h1>
    @include('admin.competitions._nav', ['competition' => $competition, 'current' => 'exports'])

    @if (session('status'))
        <p class="mt-4 rounded-md border border-teal-200 bg-teal-50 px-4 py-3 text-sm text-teal-900">{{ session('status') }}</p>
    @endif

    <section class="mt-6 rounded-lg border border-slate-200 bg-white p-5">
        <h2 class="font-medium">Export Excel</h2>
        <form method="GET" action="{{ route('admin.exports.participants', $competition) }}" class="mt-4 grid gap-3 sm:grid-cols-4">
            <div>
                <label class="block text-xs font-medium text-slate-600">Klub</label>
                <select name="club_id" class="mt-1 w-full rounded-md border border-slate-300 px-2 py-1.5 text-sm">
                    <option value="">Semua</option>
                    @foreach ($clubs as $club)
                        <option value="{{ $club->id }}">{{ $club->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-600">Nomor lomba</label>
                <select name="event_id" class="mt-1 w-full rounded-md border border-slate-300 px-2 py-1.5 text-sm">
                    <option value="">Semua</option>
                    @foreach ($events as $event)
                        <option value="{{ $event->id }}">{{ $event->event_number }} · {{ $event->formattedName() }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-600">Status</label>
                <select name="status" class="mt-1 w-full rounded-md border border-slate-300 px-2 py-1.5 text-sm">
                    <option value="">Semua</option>
                    @foreach ($statuses as $status)
                        <option value="{{ $status->value }}">{{ $status->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-end">
                <button class="rounded-md bg-teal-700 px-3 py-2 text-sm font-medium text-white hover:bg-teal-800">Unduh peserta</button>
            </div>
        </form>

        <div class="mt-5 flex flex-wrap gap-2 text-sm">
            <a href="{{ route('admin.exports.start-list', $competition) }}" class="rounded-md border border-slate-300 px-3 py-1.5 hover:bg-slate-50">Start list</a>
            <a href="{{ route('admin.exports.start-list', [$competition, 'per_event' => 1]) }}" class="rounded-md border border-slate-300 px-3 py-1.5 hover:bg-slate-50">Start list per acara</a>
            <a href="{{ route('admin.exports.results', $competition) }}" class="rounded-md border border-slate-300 px-3 py-1.5 hover:bg-slate-50">Hasil</a>
            <a href="{{ route('admin.exports.results', [$competition, 'per_event' => 1]) }}" class="rounded-md border border-slate-300 px-3 py-1.5 hover:bg-slate-50">Hasil per acara</a>
            <a href="{{ route('admin.exports.medals', $competition) }}" class="rounded-md border border-slate-300 px-3 py-1.5 hover:bg-slate-50">Rekap medali</a>
            <a href="{{ route('admin.exports.blank-results', $competition) }}" class="rounded-md border border-slate-300 px-3 py-1.5 hover:bg-slate-50">Lembar hasil kosong</a>
        </div>
    </section>

    <section class="mt-6 rounded-lg border border-slate-200 bg-white p-5">
        <h2 class="font-medium">Impor lembar hasil Excel</h2>
        <p class="mt-1 text-sm text-slate-500">Cadangan bila input juri tidak tersedia. Kolom STATUS: OK, DNS, DNF, DSQ.</p>
        <form method="POST" action="{{ route('admin.exports.blank-results.import', $competition) }}" enctype="multipart/form-data" class="mt-4 flex flex-wrap items-end gap-3">
            @csrf
            <div>
                <label class="block text-xs font-medium text-slate-600">Berkas</label>
                <input type="file" name="file" accept=".xlsx,.xls,.csv" required class="mt-1 text-sm">
            </div>
            <button class="rounded-md bg-slate-800 px-3 py-2 text-sm font-medium text-white hover:bg-slate-900">Unggah hasil</button>
        </form>
    </section>

    <section class="mt-6 rounded-lg border border-slate-200 bg-white p-5">
        <h2 class="font-medium">Pengaturan sertifikat</h2>
        <form method="POST" action="{{ route('admin.certificates.settings', $competition) }}" enctype="multipart/form-data" class="mt-4 grid max-w-xl gap-3">
            @csrf
            <div>
                <label class="block text-xs font-medium text-slate-600">Nama penandatangan</label>
                <input name="certificate_signer_name" value="{{ old('certificate_signer_name', $competition->certificate_signer_name) }}" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-600">Jabatan penandatangan</label>
                <input name="certificate_signer_title" value="{{ old('certificate_signer_title', $competition->certificate_signer_title) }}" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-600">Gambar latar (opsional)</label>
                <input type="file" name="certificate_background" accept="image/*" class="mt-1 text-sm">
            </div>
            <button class="w-fit rounded-md bg-teal-700 px-3 py-2 text-sm font-medium text-white hover:bg-teal-800">Simpan</button>
        </form>

        @if ($competition->status === \App\Enums\CompetitionStatus::Published)
            <div class="mt-5 flex flex-wrap gap-2">
                <a href="{{ route('certificates.index', $competition) }}" class="rounded-md border border-slate-300 px-3 py-1.5 text-sm hover:bg-slate-50">Daftar sertifikat</a>
                <form method="POST" action="{{ route('certificates.archive', $competition) }}">
                    @csrf
                    <button class="rounded-md bg-amber-700 px-3 py-1.5 text-sm font-medium text-white hover:bg-amber-800">Siapkan arsip ZIP seluruh kejuaraan</button>
                </form>
            </div>
        @else
            <p class="mt-4 text-sm text-slate-500">Sertifikat tersedia setelah kejuaraan berstatus dipublikasikan.</p>
        @endif
    </section>
@endsection
