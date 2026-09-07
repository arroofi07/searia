@extends('layouts.public')

@section('title', 'Rekap medali · '.$competition->name)
@section('meta_description', 'Rekap medali '.$competition->name)

@section('content')
    <p class="text-sm"><a href="{{ route('results.index', $competition) }}" class="text-teal-800 hover:underline">← Hasil</a></p>
    <h1 class="mt-2 text-2xl font-semibold">Rekap medali</h1>
    <p class="text-sm text-slate-500">{{ $competition->name }}</p>
    @if ($preview)
        <div class="mt-4 rounded-md border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-950">Pratinjau panitia</div>
    @endif

    <div class="mt-6 space-y-4">
        @foreach ($blocks as $block)
            <section class="rounded-lg border border-slate-200 bg-white p-4">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <h2 class="font-medium">Acara {{ $block['event']->event_number }} · {{ $block['age_group']->name }}</h2>
                    <div class="text-sm text-slate-600">
                        Emas {{ $block['gold'] }} · Perak {{ $block['silver'] }} · Perunggu {{ $block['bronze'] }}
                        @if ($block['small_field'])
                            <span class="ml-2 rounded bg-amber-100 px-2 py-0.5 text-xs text-amber-900">Kurang dari 3 peserta</span>
                        @endif
                    </div>
                </div>
                @if ($block['medals'] !== [])
                    <ul class="mt-3 space-y-1 text-sm">
                        @foreach ($block['medals'] as $medal)
                            <li>
                                <span class="inline-block w-16 uppercase text-xs text-slate-500">{{ $medal['metal'] }}</span>
                                {{ $medal['entry']->athleteName }} · {{ $medal['entry']->clubName }}
                                · {{ ($formatTime)($medal['entry']->timeMs) }}
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>
        @endforeach
    </div>
@endsection
