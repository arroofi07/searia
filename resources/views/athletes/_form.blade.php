@php
    use App\Enums\Gender;
@endphp

<div>
    <label class="block text-sm font-medium text-slate-700">Klub</label>
    @if ($lockedClub)
        <input type="text" value="{{ $lockedClub->name }}" disabled class="mt-1 w-full rounded-md border border-slate-200 bg-slate-100 px-3 py-2 text-sm text-slate-600">
        <input type="hidden" name="club_id" value="{{ $lockedClub->id }}">
    @else
        <select name="club_id" required class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
            <option value="">Pilih klub</option>
            @foreach ($clubs as $club)
                <option value="{{ $club->id }}" @selected((string) old('club_id', $athlete?->club_id) === (string) $club->id)>{{ $club->name }}</option>
            @endforeach
        </select>
    @endif
    @error('club_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
</div>

<div>
    <label for="full_name" class="block text-sm font-medium text-slate-700">Nama lengkap</label>
    <input id="full_name" name="full_name" value="{{ old('full_name', $athlete?->full_name) }}" required maxlength="100"
        class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
    @error('full_name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
</div>

<div class="grid gap-4 sm:grid-cols-2">
    <div>
        <label for="gender" class="block text-sm font-medium text-slate-700">Jenis kelamin</label>
        <select id="gender" name="gender" required class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
            @foreach (Gender::cases() as $gender)
                <option value="{{ $gender->value }}" @selected(old('gender', $athlete?->gender?->value) === $gender->value)>{{ $gender->label() }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label for="birth_year" class="block text-sm font-medium text-slate-700">Tahun lahir</label>
        <input id="birth_year" name="birth_year" type="number" min="1950" max="{{ now()->year }}" required
            value="{{ old('birth_year', $athlete?->birth_year) }}"
            class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
        @error('birth_year') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
</div>

<div>
    <label for="birth_date" class="block text-sm font-medium text-slate-700">Tanggal lahir (opsional)</label>
    <input id="birth_date" name="birth_date" type="date" value="{{ old('birth_date', $athlete?->birth_date?->toDateString()) }}"
        class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
</div>

<div>
    <label for="identity_number" class="block text-sm font-medium text-slate-700">NIK / nomor akta (opsional)</label>
    <input id="identity_number" name="identity_number" value="{{ old('identity_number', $athlete?->identity_number) }}" maxlength="30"
        class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
</div>

<div>
    <label for="photo" class="block text-sm font-medium text-slate-700">Foto (opsional, JPG atau PNG)</label>
    <input id="photo" name="photo" type="file" accept="image/jpeg,image/png" class="mt-1 block w-full text-sm">
    @error('photo') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
</div>
