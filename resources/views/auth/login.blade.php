@extends('layouts.guest')

@section('title', 'Masuk')

@section('content')
    <div class="mast-id">
        @include('layouts.partials.brand-mark', ['class' => 'meet-logo', 'alt' => 'Aquatic SeaRIA'])
        <div>
            <p class="kicker">Panitia &amp; juri</p>
            <h1>Masuk</h1>
        </div>
    </div>
    <p class="lead">Peserta tidak memakai akun. Pendaftaran lomba ada di halaman publik.</p>

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <div class="gap-top">
            <label for="email">Email</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="username" class="public-input">
            @error('email')
                <p class="note">{{ $message }}</p>
            @enderror
        </div>

        <div class="gap-top">
            <label for="password">Kata sandi</label>
            <input id="password" name="password" type="password" required autocomplete="current-password" class="public-input">
        </div>

        <label class="pick gap-top">
            <input type="checkbox" name="remember" class="event-box">
            Ingat saya
        </label>

        <div class="gap-top">
            <button type="submit" class="public-btn">Masuk</button>
        </div>
    </form>
@endsection
