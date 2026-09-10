@extends('layouts.public')

@section('title', $page->title.' · SeaRIA')
@section('meta_description', \Illuminate\Support\Str::limit(strip_tags($page->body), 150))

@section('content')
    <article class="mx-auto max-w-3xl">
        <h1 class="text-2xl font-semibold">{{ $page->title }}</h1>
        <div class="mt-6 whitespace-pre-wrap text-sm leading-7 text-slate-700">{{ $page->body }}</div>
    </article>
@endsection
