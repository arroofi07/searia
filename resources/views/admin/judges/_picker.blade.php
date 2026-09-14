@php
    use App\Enums\UserRole;

    /** @var \Illuminate\Support\Collection<int, \App\Models\User> $selected */
    $selected = collect($selected ?? []);
    $showCounts = $showCounts ?? false;
    $hasOfficials = $judges->contains(fn ($judge): bool => $judge->role !== UserRole::Juri);
@endphp

<div data-judge-picker-root>
    @if ($hasOfficials)
        <label class="mb-3 flex items-center gap-2 text-sm text-slate-600">
            <input type="checkbox" class="rounded border-slate-300" data-show-officials>
            Sertakan panitia & Super Admin
        </label>
    @endif

    <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3" data-judge-picker>
        @forelse ($judges as $judge)
            @php
                $checked = $selected->contains($judge->id);
                $isOfficial = $judge->role !== UserRole::Juri;
                $hide = $isOfficial && ! $checked;
            @endphp
            <label
                class="flex min-h-11 cursor-pointer items-center gap-2 rounded-lg border px-3 py-2 text-sm {{ $checked ? 'border-teal-300 bg-teal-50' : 'border-slate-200 hover:bg-slate-50' }} {{ $hide ? 'hidden' : '' }}"
                @if ($isOfficial) data-official="1" @endif
            >
                <input
                    type="checkbox"
                    name="judge_ids[]"
                    value="{{ $judge->id }}"
                    class="rounded border-slate-300"
                    @checked($checked)
                >
                <span class="min-w-0">
                    <span class="font-medium text-slate-900">{{ $judge->name }}</span>
                    <span class="block text-xs text-slate-500">
                        {{ $judge->role->label() }}
                        @if ($showCounts)
                            · {{ $judge->assigned_events_count }} nomor
                        @endif
                    </span>
                </span>
            </label>
        @empty
            <p class="text-sm text-slate-500 sm:col-span-2 lg:col-span-3">Belum ada akun juri atau panitia yang aktif.</p>
        @endforelse
    </div>
</div>
