@if ($paginator->total() > 0)
    <div class="mt-4 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
        <p class="text-xs text-slate-500">
            Menampilkan {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} dari {{ number_format($paginator->total(), 0, ',', '.') }}
        </p>
        @if ($paginator->hasPages())
            <div>{{ $paginator->links() }}</div>
        @endif
    </div>
@endif
