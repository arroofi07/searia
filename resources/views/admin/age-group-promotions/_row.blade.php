@php
    use App\Support\SwimTime;

    $athlete = $registration->athlete;
    $event = $registration->event;
    $suggested = ($athlete !== null && $event !== null)
        ? $overrides->nearestOlderEligible($competition, $athlete, $event)
        : null;
    $athleteYear = $athlete?->birth_year;
    $overrideGroups = $ageGroups->filter(
        fn ($group) => $athleteYear === null || $group->birth_year_start <= $athleteYear
    );
    $selectedId = $registration->isAgeGroupOverride()
        ? $registration->age_group_id
        : ($suggested?->id ?? $registration->age_group_id);
@endphp
<tr class="border-t border-slate-100 align-top">
    <td class="px-3 py-3">
        <div>{{ $athlete?->full_name }}</div>
        <div class="text-xs text-slate-500">{{ $athlete?->club?->name }} · lahir {{ $athleteYear }}</div>
    </td>
    <td class="px-3 py-3">{{ $event?->event_number }} {{ $event?->shortName() }}</td>
    <td class="px-3 py-3">
        <div>{{ $registration->ageGroup?->name }}</div>
        @if ($registration->isAgeGroupOverride())
            <span class="mt-0.5 inline-block rounded bg-amber-100 px-1.5 py-0.5 text-xs font-medium text-amber-900">Naik kelas</span>
        @elseif ($suggested)
            <span class="mt-0.5 inline-block text-xs text-slate-500">Saran: {{ $suggested->name }}</span>
        @endif
    </td>
    <td class="px-3 py-3">{{ SwimTime::formatMilliseconds($registration->seed_time_ms) }}</td>
    <td class="px-3 py-3">
        @if ($overrideGroups->count() > 1)
            <form method="POST" action="{{ route('admin.registrations.override-age-group', $registration) }}" class="space-y-1">
                @csrf
                @method('PATCH')
                <label class="sr-only" for="age-group-{{ $registration->id }}">Kelompok umur</label>
                <select id="age-group-{{ $registration->id }}" name="age_group_id" class="w-full rounded-md border border-slate-300 px-2 py-1 text-xs">
                    @foreach ($overrideGroups as $group)
                        <option value="{{ $group->id }}" @selected($group->id === $selectedId)>
                            {{ $group->name }} · {{ $group->birth_year_start }}–{{ $group->birth_year_end }}
                        </option>
                    @endforeach
                </select>
                <label class="sr-only" for="reason-{{ $registration->id }}">Alasan naik kelas</label>
                <input id="reason-{{ $registration->id }}" name="reason" value="{{ old('reason') }}" required maxlength="500"
                    placeholder="Alasan naik kelas" class="w-full rounded-md border border-slate-300 px-2 py-1 text-xs">
                <button class="text-xs font-medium text-teal-800 hover:underline">Simpan</button>
            </form>
        @else
            <span class="text-xs text-slate-400">Tidak ada grup lebih tua</span>
        @endif
    </td>
</tr>
