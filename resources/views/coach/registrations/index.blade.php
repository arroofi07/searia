@php
    use App\Enums\RegistrationStatus;
    use App\Support\SwimTime;
@endphp

@extends('layouts.app')

@section('title', 'Ringkasan pendaftaran klub')

@section('content')
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-semibold">{{ $competition->name }}</h1>
            <p class="text-sm text-slate-500">Entri klub dikelompokkan per atlet.</p>
        </div>
        @if (! $locked)
            <a href="{{ route('registrations.create', $competition) }}" class="rounded-md bg-teal-700 px-4 py-2 text-center text-sm font-medium text-white hover:bg-teal-800">Daftar atlet</a>
        @endif
    </div>

    @if ($locked)
        <p class="mt-4 rounded-md border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
            Pendaftaran sudah ditutup. Entri tidak dapat diubah atau dibatalkan. Koreksi hanya dilakukan panitia.
        </p>
    @endif

    <p class="mt-4 text-sm font-medium">Total biaya berjalan: Rp {{ number_format($runningTotal, 0, ',', '.') }}</p>

    @forelse ($grouped as $athleteId => $entries)
        @php $athlete = $entries->first()->athlete; @endphp
        <section class="mt-6 rounded-lg border border-slate-200 bg-white p-4">
            <h2 class="font-medium">{{ $athlete->full_name }} · {{ $athlete->birth_year }}</h2>
            <table class="mt-3 min-w-full text-left text-sm">
                <thead class="text-slate-500">
                    <tr>
                        <th class="py-1 pr-3 font-medium">Nomor</th>
                        <th class="py-1 pr-3 font-medium">Waktu</th>
                        <th class="py-1 pr-3 font-medium">Status</th>
                        <th class="py-1 font-medium"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($entries as $registration)
                        <tr class="border-t border-slate-100">
                            <td class="py-2 pr-3">{{ $registration->event->event_number }} {{ $registration->event->formattedName() }}</td>
                            <td class="py-2 pr-3">{{ SwimTime::formatMilliseconds($registration->seed_time_ms) }}</td>
                            <td class="py-2 pr-3">
                                {{ $registration->status->label() }}
                                @if ($registration->status === RegistrationStatus::Rejected && $registration->rejection_reason)
                                    <div class="text-xs text-red-700">{{ $registration->rejection_reason }}</div>
                                @endif
                            </td>
                            <td class="py-2">
                                @if (! $locked && $registration->status !== RegistrationStatus::Withdrawn)
                                    <form method="POST" action="{{ route('registrations.update', $registration) }}" class="mb-1 flex gap-2">
                                        @csrf
                                        @method('PUT')
                                        <input type="text" name="seed_time" value="{{ $registration->seed_time_ms ? SwimTime::formatMilliseconds($registration->seed_time_ms) : '' }}" class="w-28 rounded border border-slate-300 px-2 py-1 text-xs">
                                        <button class="text-xs text-teal-800 hover:underline">Ubah</button>
                                    </form>
                                    <form method="POST" action="{{ route('registrations.destroy', $registration) }}" onsubmit="return confirm('Batalkan entri ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="text-xs text-red-700 hover:underline">Batalkan</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </section>
    @empty
        <p class="mt-6 text-sm text-slate-500">Belum ada pendaftaran.</p>
    @endforelse
@endsection
