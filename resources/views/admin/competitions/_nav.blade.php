@php
    $current = $current ?? '';
@endphp

<nav class="mt-4 flex flex-wrap gap-2 text-sm">
    <a href="{{ route('admin.competitions.show', $competition) }}"
        class="rounded-md px-3 py-1.5 {{ $current === 'show' ? 'bg-teal-700 text-white' : 'bg-white text-slate-600 ring-1 ring-slate-200 hover:text-teal-800' }}">Ringkasan</a>
    <a href="{{ route('admin.competitions.edit', $competition) }}"
        class="rounded-md px-3 py-1.5 {{ $current === 'edit' ? 'bg-teal-700 text-white' : 'bg-white text-slate-600 ring-1 ring-slate-200 hover:text-teal-800' }}">Data</a>
    <a href="{{ route('admin.competitions.age-groups.index', $competition) }}"
        class="rounded-md px-3 py-1.5 {{ $current === 'age-groups' ? 'bg-teal-700 text-white' : 'bg-white text-slate-600 ring-1 ring-slate-200 hover:text-teal-800' }}">Kelompok umur</a>
    <a href="{{ route('admin.competitions.events.index', $competition) }}"
        class="rounded-md px-3 py-1.5 {{ $current === 'events' ? 'bg-teal-700 text-white' : 'bg-white text-slate-600 ring-1 ring-slate-200 hover:text-teal-800' }}">Nomor lomba</a>
    <a href="{{ route('admin.competitions.eligibility', $competition) }}"
        class="rounded-md px-3 py-1.5 {{ $current === 'eligibility' ? 'bg-teal-700 text-white' : 'bg-white text-slate-600 ring-1 ring-slate-200 hover:text-teal-800' }}">Matriks kelayakan</a>
    <a href="{{ route('admin.competitions.readiness', $competition) }}"
        class="rounded-md px-3 py-1.5 {{ $current === 'readiness' ? 'bg-teal-700 text-white' : 'bg-white text-slate-600 ring-1 ring-slate-200 hover:text-teal-800' }}">Kesiapan</a>
    <a href="{{ route('admin.imports.index', $competition) }}"
        class="rounded-md px-3 py-1.5 {{ $current === 'imports' ? 'bg-teal-700 text-white' : 'bg-white text-slate-600 ring-1 ring-slate-200 hover:text-teal-800' }}">Import</a>
</nav>
