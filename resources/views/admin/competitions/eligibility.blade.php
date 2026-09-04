@extends('layouts.app')

@section('title', 'Matriks kelayakan')

@section('content')
    <h1 class="text-2xl font-semibold">{{ $competition->name }}</h1>
    @include('admin.competitions._nav', ['competition' => $competition, 'current' => 'eligibility'])

    <p class="mt-4 text-sm text-slate-500">Centang sel, seluruh baris, atau seluruh kolom. Perubahan disimpan tanpa memuat ulang halaman. Kolom pertama membeku saat digulir.</p>
    <p id="eligibility-status" class="mt-2 text-sm text-slate-600"></p>

    @if ($competition->ageGroups->isEmpty() || $competition->events->isEmpty())
        <p class="mt-6 rounded-md border border-amber-200 bg-amber-50 px-4 py-3 text-sm">Isi kelompok umur dan nomor lomba terlebih dahulu.</p>
    @else
        <div class="mt-4 overflow-x-auto rounded-lg border border-slate-200 bg-white">
            <table class="min-w-max border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-50">
                        <th class="sticky left-0 z-20 bg-slate-50 px-3 py-2 text-left font-medium">Grup</th>
                        @foreach ($competition->events as $event)
                            <th class="min-w-[4.5rem] px-2 py-2 font-medium">
                                <div>{{ $event->event_number }}</div>
                                <button type="button" class="col-toggle font-normal text-teal-700 underline" data-event="{{ $event->id }}">kolom</button>
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach ($competition->ageGroups as $group)
                        <tr class="border-t border-slate-100">
                            <th class="sticky left-0 z-10 bg-white px-3 py-2 text-left font-medium">
                                {{ $group->name }}
                                <button type="button" class="row-toggle ml-1 font-normal text-teal-700 underline" data-group="{{ $group->id }}">baris</button>
                            </th>
                            @foreach ($competition->events as $event)
                                @php $key = $event->id.':'.$group->id; @endphp
                                <td class="px-2 py-2 text-center">
                                    <input type="checkbox"
                                        class="cell-box h-4 w-4"
                                        data-event="{{ $event->id }}"
                                        data-group="{{ $group->id }}"
                                        data-registrations="{{ $registrationCounts[$key] ?? 0 }}"
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

            const url = @json(route('admin.competitions.eligibility.update', $competition));
            const csrf = document.querySelector('meta[name="csrf-token"]').content;
            let timer = null;

            function pairs() {
                return boxes.filter((box) => box.checked).map((box) => ({
                    event_id: Number(box.dataset.event),
                    age_group_id: Number(box.dataset.group),
                }));
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
                    status.textContent = 'Gagal menyimpan matriks.';
                    return;
                }

                status.textContent = 'Matriks tersimpan (' + payload.count + ' sel).';
            }

            function scheduleSave() {
                clearTimeout(timer);
                timer = setTimeout(() => save(false), 300);
            }

            boxes.forEach((box) => {
                box.addEventListener('change', () => {
                    const count = Number(box.dataset.registrations || 0);
                    if (!box.checked && count > 0) {
                        if (!confirm('Sel ini dipakai ' + count + ' pendaftaran. Cabut kelayakan?')) {
                            box.checked = true;
                            return;
                        }
                    }
                    scheduleSave();
                });
            });

            document.querySelectorAll('.row-toggle').forEach((button) => {
                button.addEventListener('click', () => {
                    const group = button.dataset.group;
                    const rowBoxes = boxes.filter((box) => box.dataset.group === group);
                    const allOn = rowBoxes.every((box) => box.checked);
                    rowBoxes.forEach((box) => { box.checked = !allOn; });
                    scheduleSave();
                });
            });

            document.querySelectorAll('.col-toggle').forEach((button) => {
                button.addEventListener('click', () => {
                    const eventId = button.dataset.event;
                    const colBoxes = boxes.filter((box) => box.dataset.event === eventId);
                    const allOn = colBoxes.every((box) => box.checked);
                    colBoxes.forEach((box) => { box.checked = !allOn; });
                    scheduleSave();
                });
            });
        })();
    </script>
@endsection
