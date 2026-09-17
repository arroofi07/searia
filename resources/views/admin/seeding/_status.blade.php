@if ($empty ?? false)
    <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-semibold text-slate-700">Tidak ada peserta</span>
@elseif (($missingCount ?? 0) > 0)
    <span class="inline-flex items-center rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-semibold text-amber-950">
        {{ ($hasHeats ?? false) ? 'Peserta belum masuk seri' : 'Belum dibagi' }}
    </span>
@elseif (($pendingCount ?? 0) > 0)
    <span class="inline-flex items-center rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-semibold text-amber-950">Ada pendaftar baru</span>
@elseif (! $seeded)
    <span class="inline-flex items-center rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-semibold text-amber-950">Belum dibagi</span>
@elseif ($locked)
    <span class="inline-flex items-center rounded-full bg-teal-100 px-2.5 py-0.5 text-xs font-semibold text-teal-900">Terkunci</span>
@else
    <span class="inline-flex items-center rounded-full bg-sky-100 px-2.5 py-0.5 text-xs font-semibold text-sky-950">Pratinjau — belum dikunci</span>
@endif
