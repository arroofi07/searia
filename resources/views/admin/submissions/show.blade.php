@php
    use App\Support\SwimTime;
@endphp

@extends('layouts.app')

@section('title', 'Pendaftaran '.$submission->code)

@section('content')
    <h1 class="text-2xl font-semibold">Pendaftaran {{ $submission->code }}</h1>
    <p class="mt-1 text-sm text-slate-500">{{ $competition->name }}</p>

    @include('admin.registrations._tabs', ['competition' => $competition, 'current' => 'submissions'])

    @error('age_group_id')
        <div class="mt-4 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ $message }}</div>
    @enderror
    @error('reason')
        <div class="mt-4 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ $message }}</div>
    @enderror

    <div class="mt-6 grid gap-4 lg:grid-cols-2">
        <div class="rounded-lg border border-slate-200 bg-white p-5 text-sm">
            <h2 class="font-semibold">Kontak pendaftar</h2>
            <dl class="mt-3 space-y-1">
                <div class="flex gap-2"><dt class="w-32 text-slate-500">Nama</dt><dd>{{ $submission->registrant_name }}</dd></div>
                <div class="flex gap-2"><dt class="w-32 text-slate-500">WhatsApp</dt><dd>{{ $submission->registrant_phone }}</dd></div>
                <div class="flex gap-2"><dt class="w-32 text-slate-500">Email</dt><dd>{{ $submission->registrant_email ?: '—' }}</dd></div>
                <div class="flex gap-2"><dt class="w-32 text-slate-500">Dikirim</dt><dd>{{ $submission->created_at->translatedFormat('d M Y H:i') }}</dd></div>
            </dl>
        </div>

        <div class="rounded-lg border border-slate-200 bg-white p-5 text-sm">
            <h2 class="font-semibold">Atlet</h2>
            <dl class="mt-3 space-y-1">
                <div class="flex gap-2"><dt class="w-32 text-slate-500">Nama</dt><dd>{{ $submission->athlete?->full_name }}</dd></div>
                <div class="flex gap-2"><dt class="w-32 text-slate-500">Tahun lahir</dt><dd>{{ $submission->athlete?->birth_year }}</dd></div>
                <div class="flex gap-2"><dt class="w-32 text-slate-500">Klub</dt><dd>{{ $submission->athlete?->club?->name }} ({{ $submission->athlete?->club?->status->label() }})</dd></div>
            </dl>
        </div>
    </div>

    <div class="mt-6 overflow-x-auto rounded-lg border border-slate-200 bg-white">
        <table class="min-w-full text-left text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="px-4 py-3 font-medium">Nomor lomba</th>
                    <th class="px-4 py-3 font-medium">Kelompok umur</th>
                    <th class="px-4 py-3 font-medium">Catatan waktu</th>
                    <th class="px-4 py-3 font-medium">Status</th>
                    <th class="px-4 py-3 font-medium"></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($registrations as $registration)
                    <tr class="border-t border-slate-100">
                        <td class="px-4 py-3">Acara {{ $registration->event?->event_number }} {{ $registration->event?->formattedName() }}</td>
                        <td class="px-4 py-3">
                            <div>{{ $registration->ageGroup?->name }}</div>
                            @if ($registration->isAgeGroupOverride())
                                <span class="mt-0.5 inline-block rounded bg-amber-100 px-1.5 py-0.5 text-xs font-medium text-amber-900">Naik kelas · lahir {{ $registration->athlete?->birth_year }}</span>
                            @endif
                            @include('admin.registrations._override-form', ['registration' => $registration, 'ageGroups' => $ageGroups])
                        </td>
                        <td class="px-4 py-3">
                            <form method="POST" action="{{ route('admin.registrations.update', $registration) }}" class="flex gap-2">
                                @csrf
                                @method('PUT')
                                <input name="seed_time" value="{{ $registration->seed_time_ms ? SwimTime::formatMilliseconds($registration->seed_time_ms) : '' }}"
                                    placeholder="NT" class="w-28 rounded-md border border-slate-300 px-2 py-1">
                                <button class="text-teal-800 hover:underline">Simpan</button>
                            </form>
                        </td>
                        <td class="px-4 py-3">
                            {{ $registration->status->label() }}
                            @if ($registration->rejection_reason)
                                <span class="block text-xs text-red-600">{{ $registration->rejection_reason }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right">
                            <form method="POST" action="{{ route('admin.registrations.destroy', $registration) }}"
                                onsubmit="return confirm('Batalkan entri ini?')">
                                @csrf
                                @method('DELETE')
                                <button class="text-red-700 hover:underline">Batalkan</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @include('partials.pagination', ['paginator' => $registrations])

    <a href="{{ route('admin.submissions.index', $competition) }}" class="mt-6 inline-block text-sm text-teal-800 hover:underline">Kembali ke daftar</a>
@endsection
