@php
    use App\Enums\ImportStatus;
@endphp

@extends('layouts.app')

@section('title', 'Pratinjau import')

@section('content')
    <h1 class="text-2xl font-semibold">Pratinjau import</h1>
    <p class="mt-1 text-sm text-slate-500">{{ $competition->name }} · {{ $batch->original_filename }}</p>

    @include('admin.registrations._tabs', ['competition' => $competition, 'current' => 'imports'])

    @if ($batch->status === ImportStatus::Validating)
        <meta http-equiv="refresh" content="2">
        <div class="mt-6 rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
            Sedang divalidasi di latar belakang ({{ $progress }}%). Halaman ini menyegarkan diri otomatis.
        </div>
    @endif

    @if ($result->fileError)
        <div class="mt-6 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ $result->fileError }}</div>
    @endif

    @if ($result->missingColumns)
        <div class="mt-6 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
            Kolom wajib tidak ditemukan: {{ implode(', ', $result->missingColumns) }}
        </div>
    @endif

    <dl class="mt-6 grid gap-3 rounded-lg border border-slate-200 bg-white p-5 sm:grid-cols-4 text-sm">
        <div>
            <dt class="text-xs uppercase tracking-wide text-slate-500">Dibaca</dt>
            <dd class="mt-1 text-lg font-semibold">{{ $batch->total_rows }} baris</dd>
        </div>
        <div>
            <dt class="text-xs uppercase tracking-wide text-slate-500">Valid</dt>
            <dd class="mt-1 text-lg font-semibold text-teal-800">{{ $batch->valid_rows }} baris</dd>
        </div>
        <div>
            <dt class="text-xs uppercase tracking-wide text-slate-500">Bermasalah</dt>
            <dd class="mt-1 text-lg font-semibold text-red-700">{{ $batch->invalid_rows }} baris</dd>
        </div>
        <div>
            <dt class="text-xs uppercase tracking-wide text-slate-500">Peringatan</dt>
            <dd class="mt-1 text-lg font-semibold">{{ $result->warningRows }} baris</dd>
        </div>
    </dl>

    @if ($batch->status === ImportStatus::Validated)
        <div class="mt-4 flex flex-wrap gap-3">
            @if ($batch->invalid_rows > 0)
                <a href="{{ route('admin.imports.errors', $batch) }}" class="rounded-md border border-slate-300 bg-white px-4 py-2 text-sm hover:bg-slate-50">Unduh laporan kesalahan</a>
            @endif
            @if ($batch->valid_rows > 0)
                <form method="POST" action="{{ route('admin.imports.commit', $batch) }}">
                    @csrf
                    <button class="rounded-md bg-teal-700 px-4 py-2 text-sm font-medium text-white hover:bg-teal-800">Import {{ $batch->valid_rows }} baris valid</button>
                </form>
            @endif
            <form method="POST" action="{{ route('admin.imports.destroy', $batch) }}" onsubmit="return confirm('Batalkan batch ini? Pendaftaran dari batch akan dihapus.')">
                @csrf
                @method('DELETE')
                <button class="rounded-md border border-red-300 px-4 py-2 text-sm text-red-700 hover:bg-red-50">Batalkan batch</button>
            </form>
        </div>
    @endif

    @if ($batch->status === ImportStatus::Committed)
        <form method="POST" action="{{ route('admin.imports.destroy', $batch) }}" class="mt-4" onsubmit="return confirm('Batalkan seluruh pendaftaran dari batch ini?')">
            @csrf
            @method('DELETE')
            <button class="rounded-md border border-red-300 px-4 py-2 text-sm text-red-700 hover:bg-red-50">Batalkan batch</button>
        </form>
    @endif

    @if ($result->invalidRows())
        <h2 class="mt-8 text-lg font-medium">Baris bermasalah</h2>
        <div class="mt-3 space-y-4">
            @foreach ($result->invalidRows() as $row)
                <form method="POST" action="{{ route('admin.imports.rows.update', [$batch, $row->row->excelRow]) }}" class="rounded-lg border border-red-200 bg-white p-4 text-sm">
                    @csrf
                    @method('PATCH')
                    <p class="font-medium text-red-800">Baris {{ $row->row->excelRow }}</p>
                    <p class="mt-1 text-red-700">
                        @foreach ($row->errors as $error)
                            <span class="mr-2">{{ $error['code'] }} {{ $error['message'] }}</span>
                        @endforeach
                    </p>
                    <div class="mt-3 grid gap-2 sm:grid-cols-2">
                        <input name="full_name" value="{{ $row->row->fullName }}" class="rounded-md border border-slate-300 px-3 py-2" placeholder="Nama lengkap">
                        <input name="gender" value="{{ $row->row->gender }}" class="rounded-md border border-slate-300 px-3 py-2" placeholder="L/P">
                        <input name="birth_year" value="{{ $row->row->birthYear }}" class="rounded-md border border-slate-300 px-3 py-2" placeholder="Tahun lahir">
                        <input name="club_name" value="{{ $row->row->clubName }}" class="rounded-md border border-slate-300 px-3 py-2" placeholder="Klub">
                        <input name="city" value="{{ $row->row->city }}" class="rounded-md border border-slate-300 px-3 py-2" placeholder="Kabupaten/kota">
                        <input name="event_code" value="{{ $row->row->eventCode }}" class="rounded-md border border-slate-300 px-3 py-2" placeholder="Kode acara">
                        <input name="seed_time" value="{{ $row->row->seedTime }}" class="rounded-md border border-slate-300 px-3 py-2" placeholder="Catatan waktu">
                    </div>
                    @if ($row->clubSuggestions)
                        <label class="mt-3 block text-xs font-medium text-slate-600">Petakan ke klub yang sudah ada</label>
                        <select name="mapped_club_id" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2">
                            <option value="">Buat klub baru</option>
                            @foreach ($row->clubSuggestions as $suggestion)
                                <option value="{{ $suggestion['id'] }}" @selected((int) $row->row->mappedClubId === (int) $suggestion['id'])>{{ $suggestion['name'] }}</option>
                            @endforeach
                        </select>
                    @endif
                    @if ($row->athleteSuggestions)
                        <label class="mt-3 block text-xs font-medium text-slate-600">Gunakan atlet yang sudah ada</label>
                        <select name="mapped_athlete_id" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2">
                            <option value="">Buat atlet baru</option>
                            @foreach ($row->athleteSuggestions as $suggestion)
                                <option value="{{ $suggestion['id'] }}" @selected((int) $row->row->mappedAthleteId === (int) $suggestion['id'])>{{ $suggestion['full_name'] }}</option>
                            @endforeach
                        </select>
                    @endif
                    <button class="mt-3 rounded-md bg-slate-800 px-3 py-1.5 text-white">Validasi ulang</button>
                </form>
            @endforeach
        </div>
    @endif

    @if ($result->validRows())
        <h2 class="mt-8 text-lg font-medium">Baris valid</h2>
        <div class="mt-3 overflow-x-auto rounded-lg border border-slate-200 bg-white">
            <table class="min-w-full text-left text-sm">
                <thead class="bg-slate-50 text-slate-600">
                    <tr>
                        <th class="px-3 py-2 font-medium">Baris</th>
                        <th class="px-3 py-2 font-medium">Nama</th>
                        <th class="px-3 py-2 font-medium">Thn</th>
                        <th class="px-3 py-2 font-medium">Acara</th>
                        <th class="px-3 py-2 font-medium">Waktu</th>
                        <th class="px-3 py-2 font-medium">Peringatan</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($result->validRows() as $row)
                        <tr class="border-t border-slate-100">
                            <td class="px-3 py-2">{{ $row->row->excelRow }}</td>
                            <td class="px-3 py-2">{{ $row->row->fullName }}</td>
                            <td class="px-3 py-2">{{ $row->row->birthYear }}</td>
                            <td class="px-3 py-2">{{ $row->row->eventCode }}</td>
                            <td class="px-3 py-2">{{ $row->row->seedTime !== '' ? $row->row->seedTime : 'NT' }}</td>
                            <td class="px-3 py-2 text-amber-700">
                                @foreach ($row->warnings as $warning)
                                    {{ $warning['code'] }}{{ $loop->last ? '' : ', ' }}
                                @endforeach
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
@endsection
