@extends('errors.layout')

@section('title', 'Silakan masuk')
@section('code', '401')
@section('heading', 'Sesi belum masuk')
@section('message', 'Halaman ini membutuhkan akun panitia atau juri. Peserta tidak perlu masuk — daftar lomba dan cek hasil lewat beranda.')

@section('extra-actions')
    <a class="btn-ghost" href="{{ url('/login') }}">Masuk panitia</a>
@endsection
