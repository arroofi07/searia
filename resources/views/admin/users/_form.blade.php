@php
    $selectedRole = old('role', $user?->role?->value ?? $defaultRole);
@endphp

<div>
    <label for="name" class="block text-sm font-medium text-slate-700">Nama</label>
    <input id="name" name="name" value="{{ old('name', $user?->name) }}" required maxlength="100"
        class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
    @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
</div>

<div>
    <label for="email" class="block text-sm font-medium text-slate-700">Email</label>
    <input id="email" type="email" name="email" value="{{ old('email', $user?->email) }}" required maxlength="150"
        class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
    @error('email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
</div>

<div>
    <label for="phone" class="block text-sm font-medium text-slate-700">Telepon</label>
    <input id="phone" name="phone" value="{{ old('phone', $user?->phone) }}" maxlength="20"
        class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
    @error('phone') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
</div>

<div>
    <label for="role" class="block text-sm font-medium text-slate-700">Peran</label>
    <select id="role" name="role" required class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
        @foreach ($roles as $role)
            <option value="{{ $role->value }}" @selected($selectedRole === $role->value)>{{ $role->label() }}</option>
        @endforeach
    </select>
    @error('role') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
</div>

<div class="grid gap-4 sm:grid-cols-2">
    <div>
        <label for="password" class="block text-sm font-medium text-slate-700">
            Kata sandi
            @if ($user)
                <span class="font-normal text-slate-500">(opsional)</span>
            @endif
        </label>
        <input id="password" type="password" name="password" @if (! $user) required @endif minlength="8"
            class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm"
            autocomplete="new-password">
        @error('password') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
    <div>
        <label for="password_confirmation" class="block text-sm font-medium text-slate-700">Konfirmasi kata sandi</label>
        <input id="password_confirmation" type="password" name="password_confirmation" @if (! $user) required @endif minlength="8"
            class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm"
            autocomplete="new-password">
    </div>
</div>

<input type="hidden" name="is_active" value="0">
<label class="flex items-center gap-2 text-sm text-slate-700">
    <input type="checkbox" name="is_active" value="1" class="rounded border-slate-300 text-teal-700"
        @checked(old('is_active', $user?->is_active ?? true))>
    Akun aktif
</label>
@error('is_active') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
