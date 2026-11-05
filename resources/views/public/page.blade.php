@extends('layouts.public')

@section('title', $page->title.' · SeaRIA')
@section('meta_description', \Illuminate\Support\Str::limit(strip_tags($page->body), 150))

@section('content')
    <article class="sheet">
        @include('public._mast', [
            'kicker' => 'Catatan',
            'title' => $page->title,
        ])
        <div class="lead pre">{{ $page->body }}</div>
    </article>
@endsection
