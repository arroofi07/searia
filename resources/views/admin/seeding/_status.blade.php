@if (! $seeded)
    <span class="inline-flex items-center rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-semibold text-amber-950">Belum dibagi</span>
@elseif ($locked)
    <span class="inline-flex items-center rounded-full bg-teal-100 px-2.5 py-0.5 text-xs font-semibold text-teal-900">Terkunci</span>
@else
    <span class="inline-flex items-center rounded-full bg-sky-100 px-2.5 py-0.5 text-xs font-semibold text-sky-950">Pratinjau — belum dikunci</span>
@endif
