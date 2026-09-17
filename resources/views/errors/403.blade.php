@extends('errors.layout')

@section('title', 'Tidak diizinkan')
@section('code', '403')
@section('heading', 'Anda tidak punya akses')
@section('message', 'Halaman ini khusus panitia atau juri, atau data ini tidak boleh dilihat dari akun Anda. Masuk dengan akun yang sesuai, atau kembali ke halaman peserta.')

@section('extra-actions')
    <a class="btn-ghost" href="{{ url('/login') }}">Masuk panitia</a>
@endsection
