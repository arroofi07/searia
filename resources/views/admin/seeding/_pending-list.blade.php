@php
    /** @var list<array{event_id: int, age_group_id: int, event_number: string, event_name: string, age_group_name: string|null, label: string, missing_count?: int, locked?: bool}> $items */
    $items = $items ?? [];
    $title = $title ?? 'Masih ada nomor yang belum dibagi seri';
    $intro = $intro ?? 'Nomor berikut punya peserta disetujui yang belum masuk lintasan. Bisa nomor yang belum pernah dibagi, atau peserta baru setelah seri lama dibuat.';
    $showSeedForm = $showSeedForm ?? true;
    $showBulk = $showBulk ?? true;
@endphp

@if ($items !== [])
    <div class="mt-4 overflow-hidden rounded-2xl border border-amber-200 bg-amber-50">
        <div class="flex flex-col gap-3 px-5 py-4 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <p class="font-semibold text-amber-950">{{ $title }}</p>
                <p class="mt-1 text-sm leading-6 text-amber-900">{{ $intro }}</p>
            </div>
            <span class="inline-flex h-8 shrink-0 items-center rounded-full bg-white px-3 text-sm font-semibold text-amber-950 ring-1 ring-amber-200">
                {{ count($items) }} kombinasi
            </span>
        </div>

        <ul class="max-h-80 divide-y divide-slate-100 overflow-y-auto border-t border-amber-200 bg-white">
            @foreach ($items as $item)
                <li class="flex flex-col gap-2 px-5 py-3 sm:flex-row sm:items-center sm:justify-between">
                    <div class="min-w-0">
                        <p class="text-sm font-medium text-slate-900">
                            <span class="mr-1.5 font-mono font-semibold text-teal-800">{{ $item['event_number'] }}</span>
                            {{ $item['event_name'] }}
                        </p>
                        @if (! empty($item['age_group_name']))
                            <p class="mt-0.5 text-xs text-slate-500">{{ $item['age_group_name'] }}</p>
                        @endif
                        @if (! empty($item['missing_count']))
                            <p class="mt-0.5 text-xs text-amber-800">{{ $item['missing_count'] }} peserta belum masuk seri</p>
                        @endif
                    </div>
                    @if ($showSeedForm)
                        <form method="POST" action="{{ route('admin.seeding.run', $competition) }}" class="shrink-0"
                            @if (! empty($item['locked'])) onsubmit="return confirm('Nomor ini sudah dikunci. Ulangi pembagian akan mengganti susunan. Lanjutkan?')" @endif>
                            @csrf
                            <input type="hidden" name="event_id" value="{{ $item['event_id'] }}">
                            <input type="hidden" name="age_group_id" value="{{ $item['age_group_id'] }}">
                            @if (! empty($item['locked']))
                                <input type="hidden" name="force" value="1">
                            @endif
                            <button class="inline-flex min-h-9 items-center rounded-md bg-teal-700 px-3 py-1.5 text-xs font-medium text-white hover:bg-teal-800">
                                {{ ! empty($item['locked']) ? 'Ulangi pembagian' : 'Bagi seri ini' }}
                            </button>
                        </form>
                    @endif
                </li>
            @endforeach
        </ul>

        <div class="flex flex-col gap-2 border-t border-amber-200 px-5 py-3 sm:flex-row sm:items-center sm:justify-between">
            <a href="{{ route('admin.seeding.index', [$competition, 'status' => 'unseeded']) }}" class="text-sm font-medium text-teal-800 hover:underline">
                Buka semua yang belum dibagi
            </a>
            @if ($showBulk)
                <form method="POST" action="{{ route('admin.seeding.run', $competition) }}">
                    @csrf
                    <button class="inline-flex min-h-10 items-center justify-center rounded-md border border-teal-700 px-4 py-2 text-sm font-medium text-teal-800 hover:bg-teal-50">
                        Bagi seri seluruh kejuaraan
                    </button>
                </form>
            @endif
        </div>
    </div>
@endif
