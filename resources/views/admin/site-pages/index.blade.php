@extends('layouts.app')

@section('title', 'Halaman publik')

@section('content')
    <h1 class="text-2xl font-semibold">Halaman publik</h1>
    <p class="mt-1 text-sm text-slate-500">Sunting naskah pengenalan dan syarat tanpa mengubah kode.</p>

    <ul class="mt-6 space-y-3">
        @foreach ($pages as $page)
            <li class="flex items-center justify-between rounded-lg border border-slate-200 bg-white px-4 py-3">
                <div>
                    <p class="font-medium">{{ $page->title }}</p>
                    <p class="text-xs text-slate-500">/{{ $page->slug }}</p>
                </div>
                <a href="{{ route('admin.site-pages.edit', $page) }}" class="text-sm text-teal-800 hover:underline">Sunting</a>
            </li>
        @endforeach
    </ul>
@endsection
