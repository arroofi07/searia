@extends('layouts.app')

@section('title', 'Sertifikat · '.$competition->name)

@section('content')
    <h1 class="text-2xl font-semibold">Sertifikat</h1>
    @include('admin.competitions._nav', ['competition' => $competition, 'current' => 'exports'])

    <table class="mt-6 min-w-full text-left text-sm">
        <thead class="text-slate-500">
            <tr>
                <th class="py-2 pr-3">Kode</th>
                <th class="py-2 pr-3">Jenis</th>
                <th class="py-2 pr-3">Atlet</th>
                <th class="py-2 pr-3">Acara</th>
                <th class="py-2">Unduh</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($certificates as $certificate)
                <tr class="border-t border-slate-100">
                    <td class="py-2 pr-3 font-mono text-xs">{{ $certificate->code }}</td>
                    <td class="py-2 pr-3">{{ $certificate->type === 'winner' ? 'Juara #'.$certificate->rank : 'Peserta' }}</td>
                    <td class="py-2 pr-3">{{ $certificate->athlete?->full_name }}</td>
                    <td class="py-2 pr-3">{{ $certificate->event?->event_number }}</td>
                    <td class="py-2">
                        <a href="{{ route('certificates.download', $certificate) }}" class="text-teal-800 hover:underline">PDF</a>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="mt-4">{{ $certificates->links() }}</div>
@endsection
