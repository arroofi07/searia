@php
    use App\Support\SwimTime;
@endphp

@extends('layouts.app')

@section('title', $athlete->full_name)

@section('content')
    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <h1 class="text-2xl font-semibold">{{ $athlete->full_name }}</h1>
            <p class="text-sm text-slate-500">{{ $athlete->club->name }} · {{ $athlete->gender->label() }} · {{ $athlete->birth_year }}</p>
        </div>
        <div class="flex flex-wrap gap-2">
            @can('update', $athlete)
                <a href="{{ route('athletes.edit', $athlete) }}" class="rounded-md border border-slate-300 px-4 py-2 text-sm hover:bg-slate-50">Ubah</a>
            @endcan
            @can('merge', $athlete)
                <a href="{{ route('admin.athletes.merge.create', $athlete) }}" class="rounded-md border border-slate-300 px-4 py-2 text-sm hover:bg-slate-50">Gabungkan</a>
            @endcan
        </div>
    </div>

    @if ($similarAthletes->isNotEmpty())
        <div class="mt-4 rounded-md border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-950">
            <p class="font-medium">Ditemukan atlet mirip di klub yang sama dengan tahun lahir yang sama.</p>
            <ul class="mt-2 list-disc pl-5">
                @foreach ($similarAthletes as $similar)
                    <li>
                        <a href="{{ route('athletes.show', $similar) }}" class="underline">{{ $similar->full_name }}</a>
                        ({{ $similar->birth_year }})
                    </li>
                @endforeach
            </ul>
            @can('merge', $athlete)
                <a href="{{ route('admin.athletes.merge.create', $athlete) }}" class="mt-2 inline-block font-medium underline">Tinjau penggabungan</a>
            @endcan
        </div>
    @endif

    <dl class="mt-6 grid gap-4 rounded-lg border border-slate-200 bg-white p-5 sm:grid-cols-2">
        <div>
            <dt class="text-xs uppercase tracking-wide text-slate-500">Status</dt>
            <dd class="mt-1">{{ $athlete->is_active ? 'Aktif' : 'Nonaktif' }}</dd>
        </div>
        <div>
            <dt class="text-xs uppercase tracking-wide text-slate-500">Identitas</dt>
            <dd class="mt-1">{{ $athlete->identity_number ?: '—' }}</dd>
        </div>
        @if ($athlete->photo_path)
            <div class="sm:col-span-2">
                <dt class="text-xs uppercase tracking-wide text-slate-500">Foto</dt>
                <dd class="mt-2"><img src="{{ route('files.athletes.photo', $athlete) }}" alt="{{ $athlete->full_name }}" class="h-32 rounded object-cover"></dd>
            </div>
        @endif
    </dl>

    <section class="mt-8" id="nomor-lomba">
        <div class="flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h2 class="text-lg font-semibold">Nomor lomba yang diikuti</h2>
                <p class="text-sm text-slate-500">Daftar nomor per kejuaraan. Panitia dapat menambah, mengubah, atau membatalkan entri.</p>
            </div>
        </div>

        @if ($errors->has('registration') || $errors->has('event_ids') || $errors->has('event_id') || $errors->has('seed_time') || $errors->has('competition_id'))
            <div class="mt-4 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                {{ $errors->first('registration') ?: $errors->first('event_ids') ?: $errors->first('event_id') ?: $errors->first('seed_time') ?: $errors->first('competition_id') }}
            </div>
        @endif

        @forelse ($groupedRegistrations as $items)
            @php
                $competition = $items->first()?->competition;
            @endphp
            <div class="mt-4 overflow-x-auto rounded-lg border border-slate-200 bg-white">
                <div class="border-b border-slate-100 px-4 py-3">
                    <h3 class="font-semibold">{{ $competition?->name ?? 'Kejuaraan' }}</h3>
                    @if ($competition)
                        <p class="text-xs text-slate-500">{{ $competition->status->label() }} · maksimal {{ $competition->max_events_per_athlete }} nomor</p>
                    @endif
                </div>
                <table class="min-w-full text-left text-sm">
                    <thead class="bg-slate-50 text-slate-600">
                        <tr>
                            <th class="px-4 py-3 font-medium">No</th>
                            <th class="px-4 py-3 font-medium">Nomor lomba</th>
                            <th class="px-4 py-3 font-medium">Kelompok umur</th>
                            <th class="px-4 py-3 font-medium">Catatan waktu</th>
                            <th class="px-4 py-3 font-medium">Status</th>
                            <th class="px-4 py-3 font-medium"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($items as $registration)
                            @php
                                $eventOptions = $eventOptionsByRegistrationId[$registration->id] ?? collect();
                                $canChangeEvent = $registration->canChangeEvent() && $eventOptions->isNotEmpty();
                                $formId = 'edit-registration-'.$registration->id;
                            @endphp
                            <tr class="border-t border-slate-100 align-top">
                                <td class="px-4 py-3 font-mono font-semibold text-teal-800">{{ $registration->event?->paddedEventNumber() }}</td>
                                <td class="px-4 py-3">
                                    @can('update', $registration)
                                        <form id="{{ $formId }}" method="POST" action="{{ route('athletes.registrations.update', [$athlete, $registration]) }}">
                                            @csrf
                                            @method('PUT')
                                        </form>
                                        @if ($canChangeEvent)
                                            <select form="{{ $formId }}" name="event_id" class="w-full min-w-[16rem] rounded-md border border-slate-300 px-2 py-1.5 text-sm">
                                                @foreach ($eventOptions as $event)
                                                    <option value="{{ $event->id }}" @selected((string) old('event_id', $registration->event_id) === (string) $event->id)>
                                                        {{ $event->paddedEventNumber() }} · {{ $event->formattedName() }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        @else
                                            <input type="hidden" form="{{ $formId }}" name="event_id" value="{{ $registration->event_id }}">
                                            <div>{{ $registration->event?->formattedName() }}</div>
                                            @if ($registration->heatLane)
                                                <p class="mt-1 text-xs text-slate-500">Sudah masuk seri, nomor tidak bisa diganti.</p>
                                            @endif
                                        @endif
                                    @else
                                        <div>{{ $registration->event?->formattedName() }}</div>
                                    @endcan
                                </td>
                                <td class="px-4 py-3">
                                    <div>{{ $registration->ageGroup?->name ?: '—' }}</div>
                                    @if ($registration->ageGroup && ! $registration->ageGroup->containsBirthYear($athlete->birth_year))
                                        <span class="mt-0.5 inline-block rounded bg-amber-100 px-1.5 py-0.5 text-xs font-medium text-amber-900">Naik kelas · lahir {{ $athlete->birth_year }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    @can('update', $registration)
                                        <div class="flex flex-wrap items-center gap-2">
                                            <input form="{{ $formId }}" name="seed_time" value="{{ old('seed_time', $registration->seed_time_ms ? SwimTime::formatMilliseconds($registration->seed_time_ms) : '') }}"
                                                placeholder="NT" class="w-28 rounded-md border border-slate-300 px-2 py-1.5 font-mono text-sm">
                                            <button form="{{ $formId }}" class="text-sm font-medium text-teal-800 hover:underline">Simpan</button>
                                        </div>
                                    @else
                                        <span class="font-mono">{{ $registration->seed_time_ms ? SwimTime::formatMilliseconds($registration->seed_time_ms) : 'NT' }}</span>
                                    @endcan
                                </td>
                                <td class="px-4 py-3">
                                    {{ $registration->status->label() }}
                                    @if ($registration->rejection_reason)
                                        <span class="block text-xs text-red-600">{{ $registration->rejection_reason }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right">
                                    @can('delete', $registration)
                                        <form method="POST" action="{{ route('athletes.registrations.destroy', [$athlete, $registration]) }}"
                                            onsubmit="return confirm('Batalkan nomor ini untuk atlet ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button class="text-red-700 hover:underline">Batalkan</button>
                                        </form>
                                    @endcan
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @empty
            <p class="mt-4 rounded-lg border border-dashed border-slate-300 bg-white px-4 py-8 text-center text-sm text-slate-500">
                Atlet ini belum terdaftar pada nomor lomba.
            </p>
        @endforelse

        @can('update', $athlete)
            <div class="mt-6 rounded-lg border border-slate-200 bg-white p-5">
                <h3 class="font-semibold">Tambah nomor lomba</h3>
                <p class="mt-1 text-sm text-slate-500">Pilih kejuaraan yang masih Pendaftaran terbuka atau Pendaftaran ditutup. Form publik tetap tertutup; panitia boleh menambah atau mengubah nomor sampai sebelum seeding.</p>

                @if ($openCompetitions->isEmpty())
                    <p class="mt-4 text-sm text-slate-500">Tidak ada kejuaraan yang masih bisa dikoreksi panitia (sebelum seeding).</p>
                @else
                    <form method="GET" action="{{ route('athletes.show', $athlete) }}" class="mt-4 max-w-xl">
                        <label for="competition_id" class="block text-sm font-medium text-slate-700">Kejuaraan</label>
                        <div class="mt-1 flex flex-wrap gap-2">
                            <select id="competition_id" name="competition_id" class="min-w-0 flex-1 rounded-md border border-slate-300 px-3 py-2 text-sm" onchange="this.form.submit()">
                                <option value="">Pilih kejuaraan</option>
                                @foreach ($openCompetitions as $competition)
                                    <option value="{{ $competition->id }}" @selected((string) old('competition_id', request('competition_id')) === (string) $competition->id)>
                                        {{ $competition->name }} · {{ $competition->status->label() }}
                                    </option>
                                @endforeach
                            </select>
                            <button class="rounded-md border border-slate-300 px-3 py-2 text-sm hover:bg-slate-50">Tampilkan</button>
                        </div>
                    </form>

                    @if ($selectedCompetition)
                        @if ($selectedAgeGroup === null)
                            <p class="mt-4 text-sm text-amber-800">Tahun lahir atlet di luar rentang kejuaraan ini.</p>
                        @elseif ($availableEvents->isEmpty())
                            <p class="mt-4 text-sm text-slate-500">
                                Tidak ada nomor yang bisa ditambahkan. Nomor mungkin sudah diikuti, tidak sesuai L/P, atau tidak dibuka untuk {{ $selectedAgeGroup->name }}.
                            </p>
                        @else
                            <form method="POST" action="{{ route('athletes.registrations.store', $athlete) }}" class="mt-4 space-y-4">
                                @csrf
                                <input type="hidden" name="competition_id" value="{{ $selectedCompetition->id }}">
                                <p class="text-sm text-slate-600">
                                    {{ $selectedAgeGroup->name }} · maksimal {{ $selectedCompetition->max_events_per_athlete }} nomor per atlet.
                                </p>
                                <ul class="space-y-2">
                                    @foreach ($availableEvents as $event)
                                        @php
                                            $checked = in_array($event->id, old('event_ids', []), false);
                                        @endphp
                                        <li class="rounded-md border border-slate-200 p-3">
                                            <label class="flex cursor-pointer items-start gap-3">
                                                <input type="checkbox" name="event_ids[]" value="{{ $event->id }}"
                                                    class="mt-1 h-4 w-4 rounded border-slate-300 text-teal-700"
                                                    @checked($checked)>
                                                <span>
                                                    <span class="font-mono font-semibold text-teal-800">{{ $event->paddedEventNumber() }}</span>
                                                    <span class="font-medium">{{ $event->formattedName() }}</span>
                                                </span>
                                            </label>
                                            <div class="mt-2 pl-7">
                                                <label class="sr-only" for="seed-{{ $event->id }}">Catatan waktu {{ $event->paddedEventNumber() }}</label>
                                                <input id="seed-{{ $event->id }}" type="text" name="seed_times[{{ $event->id }}]"
                                                    value="{{ old('seed_times.'.$event->id) }}"
                                                    placeholder="Catatan waktu, kosong = NT"
                                                    class="w-full max-w-xs rounded-md border border-slate-300 px-2 py-1.5 text-sm">
                                            </div>
                                        </li>
                                    @endforeach
                                </ul>
                                <label class="flex items-center gap-2 text-sm">
                                    <input type="checkbox" name="verify_now" value="1" @checked(old('verify_now', true)) class="h-4 w-4">
                                    Langsung setujui (ikut pembagian seri)
                                </label>
                                <button class="rounded-md bg-teal-700 px-4 py-2 text-sm font-medium text-white hover:bg-teal-800">Tambah nomor</button>
                            </form>
                        @endif
                    @endif
                @endif
            </div>
        @endcan
    </section>

    @can('delete', $athlete)
        <form method="POST" action="{{ route('athletes.destroy', $athlete) }}" class="mt-6" onsubmit="return confirm('Hapus atau arsipkan atlet ini?')">
            @csrf
            @method('DELETE')
            <button class="text-sm text-red-700 hover:underline">Hapus atau arsipkan</button>
        </form>
    @endcan
@endsection
