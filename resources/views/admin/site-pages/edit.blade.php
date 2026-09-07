@extends('layouts.app')

@section('title', 'Sunting '.$page->slug)

@section('content')
    <h1 class="text-2xl font-semibold">Sunting: {{ $page->slug }}</h1>
    <form method="POST" action="{{ route('admin.site-pages.update', $page) }}" class="mt-6 max-w-3xl space-y-4">
        @csrf
        @method('PUT')
        <div>
            <label class="block text-sm text-slate-600">Judul</label>
            <input type="text" name="title" value="{{ old('title', $page->title) }}" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm" required>
            @error('title')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="block text-sm text-slate-600">Isi</label>
            <textarea name="body" rows="18" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 font-mono text-sm" required>{{ old('body', $page->body) }}</textarea>
            @error('body')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        <button class="rounded-md bg-teal-700 px-4 py-2 text-sm font-medium text-white hover:bg-teal-800">Simpan</button>
    </form>
@endsection
