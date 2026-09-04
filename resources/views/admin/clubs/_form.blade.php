@php
    use App\Enums\ClubType;
@endphp

<div>
    <label for="name" class="block text-sm font-medium text-slate-700">Nama klub</label>
    <input id="name" name="name" value="{{ old('name', $club?->name) }}" required maxlength="150"
        class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
    @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
</div>

<div>
    <label for="short_name" class="block text-sm font-medium text-slate-700">Singkatan</label>
    <input id="short_name" name="short_name" value="{{ old('short_name', $club?->short_name) }}" maxlength="30"
        class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
</div>

<div>
    <label for="type" class="block text-sm font-medium text-slate-700">Jenis</label>
    <select id="type" name="type" required class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
        @foreach (ClubType::cases() as $type)
            <option value="{{ $type->value }}" @selected(old('type', $club?->type?->value) === $type->value)>{{ $type->label() }}</option>
        @endforeach
    </select>
</div>

<div class="grid gap-4 sm:grid-cols-2">
    <div>
        <label for="city" class="block text-sm font-medium text-slate-700">Kota / kabupaten</label>
        <input id="city" name="city" value="{{ old('city', $club?->city) }}" required maxlength="100"
            class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
        @error('city') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
    <div>
        <label for="province" class="block text-sm font-medium text-slate-700">Provinsi</label>
        <input id="province" name="province" value="{{ old('province', $club?->province) }}" maxlength="100"
            class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
    </div>
</div>

<div class="grid gap-4 sm:grid-cols-2">
    <div>
        <label for="contact_name" class="block text-sm font-medium text-slate-700">Nama kontak</label>
        <input id="contact_name" name="contact_name" value="{{ old('contact_name', $club?->contact_name) }}" maxlength="100"
            class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
    </div>
    <div>
        <label for="contact_phone" class="block text-sm font-medium text-slate-700">Telepon kontak</label>
        <input id="contact_phone" name="contact_phone" value="{{ old('contact_phone', $club?->contact_phone) }}" maxlength="20"
            class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
    </div>
</div>
