@php
    use App\Enums\CompetitionType;
    use App\Enums\SeedingMode;

    $lanesLocked = isset($competition) && ! $competition->isDraft();
@endphp

<div>
    <label for="name" class="block text-sm font-medium text-slate-700">Nama kejuaraan</label>
    <input id="name" name="name" value="{{ old('name', $competition?->name) }}" required maxlength="150"
        class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
    @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
</div>

<div class="grid gap-4 sm:grid-cols-2">
    <div>
        <label for="venue" class="block text-sm font-medium text-slate-700">Tempat</label>
        <input id="venue" name="venue" value="{{ old('venue', $competition?->venue) }}" required maxlength="200"
            class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
        @error('venue') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
    <div>
        <label for="city" class="block text-sm font-medium text-slate-700">Kota</label>
        <input id="city" name="city" value="{{ old('city', $competition?->city) }}" required maxlength="100"
            class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
        @error('city') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
</div>

<div class="grid gap-4 sm:grid-cols-2">
    <div>
        <label for="start_date" class="block text-sm font-medium text-slate-700">Tanggal mulai</label>
        <input id="start_date" name="start_date" type="date" required
            value="{{ old('start_date', $competition?->start_date?->toDateString()) }}"
            class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
        @error('start_date') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
    <div>
        <label for="end_date" class="block text-sm font-medium text-slate-700">Tanggal selesai</label>
        <input id="end_date" name="end_date" type="date" required
            value="{{ old('end_date', $competition?->end_date?->toDateString()) }}"
            class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
        @error('end_date') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
</div>

<div class="grid gap-4 sm:grid-cols-2">
    <div>
        <label for="registration_opens_at" class="block text-sm font-medium text-slate-700">Pembukaan pendaftaran</label>
        <input id="registration_opens_at" name="registration_opens_at" type="datetime-local" required
            value="{{ old('registration_opens_at', $competition?->registration_opens_at?->format('Y-m-d\TH:i')) }}"
            class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
        @error('registration_opens_at') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
    <div>
        <label for="registration_closes_at" class="block text-sm font-medium text-slate-700">Penutupan pendaftaran</label>
        <input id="registration_closes_at" name="registration_closes_at" type="datetime-local" required
            value="{{ old('registration_closes_at', $competition?->registration_closes_at?->format('Y-m-d\TH:i')) }}"
            class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
        @error('registration_closes_at') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
</div>

<div>
    <label for="technical_meeting_at" class="block text-sm font-medium text-slate-700">Technical meeting (opsional)</label>
    <input id="technical_meeting_at" name="technical_meeting_at" type="datetime-local"
        value="{{ old('technical_meeting_at', $competition?->technical_meeting_at?->format('Y-m-d\TH:i')) }}"
        class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
</div>

<div class="grid gap-4 sm:grid-cols-2">
    <div>
        <label for="type" class="block text-sm font-medium text-slate-700">Jenis</label>
        <select id="type" name="type" required class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
            @foreach (CompetitionType::cases() as $type)
                <option value="{{ $type->value }}" @selected(old('type', $competition?->type?->value) === $type->value)>{{ $type->label() }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label for="seeding_mode" class="block text-sm font-medium text-slate-700">Mode seeding</label>
        <select id="seeding_mode" name="seeding_mode" required class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
            @foreach (SeedingMode::cases() as $mode)
                <option value="{{ $mode->value }}" @selected(old('seeding_mode', $competition?->seeding_mode?->value) === $mode->value)>{{ $mode->label() }}</option>
            @endforeach
        </select>
    </div>
</div>

<div class="grid gap-4 sm:grid-cols-3">
    <div>
        <label for="pool_lanes" class="block text-sm font-medium text-slate-700">Jumlah lintasan</label>
        <input id="pool_lanes" name="pool_lanes" type="number" min="4" max="10" required
            value="{{ old('pool_lanes', $competition?->pool_lanes ?? 8) }}"
            @if ($lanesLocked) aria-describedby="pool-lanes-lock" @endif
            class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
        @if ($lanesLocked)
            <p id="pool-lanes-lock" class="mt-1 text-xs text-amber-700">Tidak dapat diubah setelah keluar dari draf.</p>
        @endif
        @error('pool_lanes') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
    <div>
        <label for="pool_length" class="block text-sm font-medium text-slate-700">Panjang kolam (m)</label>
        <select id="pool_length" name="pool_length" required class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
            <option value="25" @selected((string) old('pool_length', $competition?->pool_length ?? 25) === '25')>25</option>
            <option value="50" @selected((string) old('pool_length', $competition?->pool_length) === '50')>50</option>
        </select>
    </div>
    <div>
        <label for="max_events_per_athlete" class="block text-sm font-medium text-slate-700">Batas nomor per atlet</label>
        <input id="max_events_per_athlete" name="max_events_per_athlete" type="number" min="1" max="20" required
            value="{{ old('max_events_per_athlete', $competition?->max_events_per_athlete ?? 3) }}"
            class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
    </div>
</div>

<div class="grid gap-4 sm:grid-cols-2">
    <div>
        <label for="fee_per_event" class="block text-sm font-medium text-slate-700">Biaya per nomor (rupiah)</label>
        <input id="fee_per_event" name="fee_per_event" type="number" min="0" required
            value="{{ old('fee_per_event', $competition?->fee_per_event ?? 0) }}"
            class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
        @error('fee_per_event') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
    <div>
        <label for="late_fee_per_event" class="block text-sm font-medium text-slate-700">Denda keterlambatan</label>
        <input id="late_fee_per_event" name="late_fee_per_event" type="number" min="0" required
            value="{{ old('late_fee_per_event', $competition?->late_fee_per_event ?? 0) }}"
            class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
    </div>
</div>

<div>
    <label for="description" class="block text-sm font-medium text-slate-700">Deskripsi</label>
    <textarea id="description" name="description" rows="3" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">{{ old('description', $competition?->description) }}</textarea>
</div>
