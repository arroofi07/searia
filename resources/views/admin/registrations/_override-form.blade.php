@php
    $athleteYear = $registration->athlete?->birth_year;
    $overrideGroups = $ageGroups->filter(
        fn ($group) => $athleteYear === null || $group->birth_year_start <= $athleteYear
    );
@endphp
@if ($overrideGroups->count() > 1)
    <form method="POST" action="{{ route('admin.registrations.override-age-group', $registration) }}" class="mt-2 space-y-1">
        @csrf
        @method('PATCH')
        <label class="sr-only" for="age-group-{{ $registration->id }}">Kelompok umur</label>
        <select id="age-group-{{ $registration->id }}" name="age_group_id" class="w-full rounded-md border border-slate-300 px-2 py-1 text-xs">
            @foreach ($overrideGroups as $group)
                <option value="{{ $group->id }}" @selected($group->id === $registration->age_group_id)>
                    {{ $group->name }} · {{ $group->birth_year_start }}–{{ $group->birth_year_end }}
                </option>
            @endforeach
        </select>
        <label class="sr-only" for="reason-{{ $registration->id }}">Alasan naik kelas</label>
        <input id="reason-{{ $registration->id }}" name="reason" value="{{ old('reason') }}" required maxlength="500"
            placeholder="Alasan naik kelas" class="w-full rounded-md border border-slate-300 px-2 py-1 text-xs">
        <button class="text-xs font-medium text-teal-800 hover:underline">Naik kelas</button>
    </form>
@endif
