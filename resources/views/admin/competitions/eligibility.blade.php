@extends('layouts.app')

@section('title', 'Matriks kelayakan')

@section('content')
    <h1 class="text-2xl font-semibold">{{ $competition->name }}</h1>
    @include('admin.competitions._nav', ['competition' => $competition, 'current' => 'eligibility'])

    <section class="mt-6 max-w-3xl rounded-xl border border-teal-200 bg-teal-50/70 p-5 text-sm leading-6 text-teal-950">
        <h2 class="text-base font-semibold">Apa fungsi matriks ini?</h2>
        <p class="mt-1">
            Menentukan <strong>kelompok umur mana yang boleh ikut nomor lomba mana</strong>.
            Contoh: Group 1 hanya 25 m, Group 4 boleh 50 m gaya dada.
        </p>
        <p class="mt-2">
            Form daftar, import Excel, dan input manual <strong>hanya menampilkan kombinasi yang dicentang</strong>.
            Peserta tidak bisa memilih nomor yang tidak boleh diikuti grupnya.
        </p>
    </section>

    <p id="eligibility-status" class="mt-3 text-sm text-slate-600" aria-live="polite"></p>

    @if ($competition->ageGroups->isEmpty() || $competition->events->isEmpty())
        <p class="mt-6 rounded-md border border-amber-200 bg-amber-50 px-4 py-3 text-sm">Isi kelompok umur dan nomor lomba terlebih dahulu.</p>
    @else
        <div class="mt-4 rounded-xl border border-slate-200 bg-white p-4 text-sm leading-6 text-slate-700">
            <p class="font-medium text-slate-900">Cara mencentang</p>
            <ul class="mt-2 list-disc space-y-1 pl-5">
                <li>Kotak di tengah tabel: grup itu <strong>boleh ikut</strong> nomor itu.</li>
                <li>Kotak di samping nama grup: izinkan grup itu di <strong>semua nomor</strong>.</li>
                <li>Kotak di bawah nomor acara: izinkan <strong>semua grup</strong> ikut nomor itu.</li>
            </ul>
            <p class="mt-2 text-xs text-slate-500">Perubahan tersimpan otomatis. Jika cabut centang yang sudah dipakai pendaftaran, sistem akan minta konfirmasi.</p>
        </div>

        <div class="mt-4 overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">
            <table class="min-w-max border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-50">
                        <th class="sticky left-0 z-20 min-w-[11rem] bg-slate-50 px-3 py-3 text-left font-medium text-slate-600">
                            <span class="block text-[11px] font-semibold uppercase tracking-wide">Kelompok umur</span>
                            <span class="mt-1 block font-normal text-slate-400">↓ grup · nomor →</span>
                        </th>
                        @foreach ($competition->events as $event)
                            <th class="min-w-[5.5rem] px-2 py-3 text-center align-bottom font-medium">
                                <div class="font-mono text-sm font-semibold text-slate-900">{{ $event->paddedEventNumber() }}</div>
                                <div class="mt-0.5 text-[10px] font-semibold uppercase tracking-wide text-teal-800">{{ $event->gender->value }}</div>
                                <div class="mx-auto mt-1 max-w-[5.25rem] leading-4 text-slate-500" title="{{ $event->formattedName() }}">{{ $event->shortName() }}</div>
                                <label class="mt-2 inline-flex cursor-pointer items-center justify-center gap-1 text-[10px] font-normal text-slate-500">
                                    <input type="checkbox"
                                        class="col-toggle h-4 w-4 rounded border-slate-300 text-teal-700"
                                        data-event="{{ $event->id }}"
                                        aria-label="Izinkan semua grup ikut nomor {{ $event->event_number }} {{ $event->formattedName() }}"
                                        title="Izinkan semua grup ikut nomor ini">
                                </label>
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach ($competition->ageGroups as $group)
                        <tr class="border-t border-slate-100 hover:bg-slate-50/80">
                            <th class="sticky left-0 z-10 bg-white px-3 py-2.5 text-left font-medium hover:bg-slate-50/80">
                                <div class="flex items-center gap-2">
                                    <input type="checkbox"
                                        class="row-toggle h-4 w-4 shrink-0 rounded border-slate-300 text-teal-700"
                                        data-group="{{ $group->id }}"
                                        aria-label="Izinkan {{ $group->name }} di semua nomor"
                                        title="Izinkan grup ini di semua nomor">
                                    <span>
                                        <span class="block text-sm text-slate-900">{{ $group->name }}</span>
                                        <span class="block text-[11px] font-normal text-slate-400">{{ $group->birth_year_start }}–{{ $group->birth_year_end }}</span>
                                    </span>
                                </div>
                            </th>
                            @foreach ($competition->events as $event)
                                @php $key = $event->id.':'.$group->id; @endphp
                                <td class="px-2 py-2.5 text-center">
                                    <input type="checkbox"
                                        class="cell-box h-4 w-4 rounded border-slate-300 text-teal-700"
                                        data-event="{{ $event->id }}"
                                        data-group="{{ $group->id }}"
                                        data-registrations="{{ $registrationCounts[$key] ?? 0 }}"
                                        aria-label="{{ $group->name }} boleh ikut nomor {{ $event->event_number }} {{ $event->formattedName() }}"
                                        title="{{ $group->name }} × {{ $event->paddedEventNumber() }} {{ $event->formattedName() }}"
                                        @checked(in_array($key, $eligible, true))>
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <script>
        (() => {
            const status = document.getElementById('eligibility-status');
            const boxes = [...document.querySelectorAll('.cell-box')];
            if (boxes.length === 0) return;

            const colMasters = [...document.querySelectorAll('.col-toggle')];
            const rowMasters = [...document.querySelectorAll('.row-toggle')];
            const url = @json(route('admin.competitions.eligibility.update', $competition));
            const csrf = document.querySelector('meta[name="csrf-token"]').content;
            let timer = null;

            function pairs() {
                return boxes.filter((box) => box.checked).map((box) => ({
                    event_id: Number(box.dataset.event),
                    age_group_id: Number(box.dataset.group),
                }));
            }

            function syncMaster(master, related) {
                const checked = related.filter((box) => box.checked).length;
                master.checked = related.length > 0 && checked === related.length;
                master.indeterminate = checked > 0 && checked < related.length;
            }

            function syncMasters() {
                colMasters.forEach((master) => {
                    syncMaster(master, boxes.filter((box) => box.dataset.event === master.dataset.event));
                });
                rowMasters.forEach((master) => {
                    syncMaster(master, boxes.filter((box) => box.dataset.group === master.dataset.group));
                });
            }

            async function save(confirmAffected = false) {
                status.textContent = 'Menyimpan…';
                const response = await fetch(url, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ pairs: pairs(), confirm_affected: confirmAffected }),
                });

                const payload = await response.json();

                if (response.status === 409 && payload.requires_confirmation) {
                    if (confirm(payload.message)) {
                        return save(true);
                    }
                    status.textContent = 'Perubahan dibatalkan.';
                    window.location.reload();
                    return;
                }

                if (!response.ok) {
                    status.textContent = 'Gagal menyimpan. Coba lagi.';
                    return;
                }

                status.textContent = 'Tersimpan. ' + payload.count + ' kombinasi grup × nomor diizinkan.';
            }

            function scheduleSave() {
                syncMasters();
                clearTimeout(timer);
                timer = setTimeout(() => save(false), 300);
            }

            boxes.forEach((box) => {
                box.addEventListener('change', () => {
                    const count = Number(box.dataset.registrations || 0);
                    if (!box.checked && count > 0) {
                        if (!confirm('Kombinasi ini sudah dipakai ' + count + ' pendaftaran. Cabut izin?')) {
                            box.checked = true;
                            return;
                        }
                    }
                    scheduleSave();
                });
            });

            rowMasters.forEach((master) => {
                master.addEventListener('change', () => {
                    const related = boxes.filter((box) => box.dataset.group === master.dataset.group);
                    related.forEach((box) => { box.checked = master.checked; });
                    master.indeterminate = false;
                    scheduleSave();
                });
            });

            colMasters.forEach((master) => {
                master.addEventListener('change', () => {
                    const related = boxes.filter((box) => box.dataset.event === master.dataset.event);
                    related.forEach((box) => { box.checked = master.checked; });
                    master.indeterminate = false;
                    scheduleSave();
                });
            });

            syncMasters();
        })();
    </script>
@endsection
