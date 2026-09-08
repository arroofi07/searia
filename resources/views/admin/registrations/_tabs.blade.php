@php
    $tab = $current ?? 'registrations';
@endphp

<nav class="mt-4 flex flex-wrap gap-2 text-sm">
    <a href="{{ route('admin.registrations.index', $competition) }}"
        class="rounded-md px-3 py-1.5 {{ $tab === 'registrations' ? 'bg-teal-700 text-white' : 'bg-white text-slate-600 ring-1 ring-slate-200 hover:text-teal-800' }}">Antrean verifikasi</a>
    @can('viewAny', App\Models\ImportBatch::class)
        <a href="{{ route('admin.imports.index', $competition) }}"
            class="rounded-md px-3 py-1.5 {{ $tab === 'imports' ? 'bg-teal-700 text-white' : 'bg-white text-slate-600 ring-1 ring-slate-200 hover:text-teal-800' }}">Import Excel</a>
    @endcan
    <a href="{{ route('admin.submissions.index', $competition) }}"
        class="rounded-md px-3 py-1.5 {{ $tab === 'submissions' ? 'bg-teal-700 text-white' : 'bg-white text-slate-600 ring-1 ring-slate-200 hover:text-teal-800' }}">Pendaftaran masuk</a>
    <a href="{{ route('admin.registrations.create', $competition) }}"
        class="rounded-md px-3 py-1.5 {{ $tab === 'create' ? 'bg-teal-700 text-white' : 'bg-white text-slate-600 ring-1 ring-slate-200 hover:text-teal-800' }}">Tambah manual</a>
</nav>
