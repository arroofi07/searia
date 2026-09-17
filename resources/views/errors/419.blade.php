@extends('errors.layout')

@section('title', 'Sesi kedaluwarsa')
@section('code', '419')
@section('heading', 'Sesi formulir sudah habis')
@section('message', 'Halaman ini terlalu lama terbuka, jadi pengiriman dihentikan demi keamanan. Muat ulang, lalu isi dan kirim formulir sekali lagi.')

@section('extra-actions')
    <a class="btn-ghost" href="{{ url()->current() }}">Muat ulang halaman</a>
@endsection
