<section class="rounded-2xl border border-teal-200 bg-gradient-to-br from-teal-50 to-white p-4 sm:p-5">
    <h2 class="text-base font-semibold text-teal-950">Apa itu catatan waktu?</h2>
    <p class="mt-1.5 text-sm leading-6 text-slate-700">
        Catatan waktu (sering disebut <strong>seed</strong>) adalah <strong>waktu terbaik atlet</strong> di nomor yang sama.
        Bukan hasil lomba nanti. Panitia memakainya untuk <strong>menyusun seri dan lintasan</strong>:
        yang lebih cepat biasanya di lintasan tengah, yang belum punya waktu (NT) di seri belakang.
    </p>

    <h3 class="mt-4 text-sm font-semibold text-teal-950">Cara mengisi: ketik 6 angka</h3>
    <p class="mt-1 text-sm leading-6 text-slate-700">
        Urutan dari <strong>kiri ke kanan</strong>: 2 angka menit, 2 angka detik, 2 angka seperseratus detik.
        Tidak perlu titik atau titik dua — sistem yang merapikan.
    </p>

    <div class="mt-4 overflow-x-auto">
        <div class="min-w-[18rem]">
            <div class="flex items-end justify-center gap-1 sm:gap-1.5">
                <div class="text-center">
                    <div class="flex gap-1">
                        <span class="seed-digit filled">0</span>
                        <span class="seed-digit filled">1</span>
                    </div>
                    <p class="mt-1.5 text-[11px] font-semibold uppercase tracking-wide text-teal-800">Menit</p>
                    <p class="text-[11px] text-slate-500">paling kiri</p>
                </div>
                <span class="mb-7 px-0.5 font-mono text-lg font-bold text-slate-400">:</span>
                <div class="text-center">
                    <div class="flex gap-1">
                        <span class="seed-digit filled">3</span>
                        <span class="seed-digit filled">4</span>
                    </div>
                    <p class="mt-1.5 text-[11px] font-semibold uppercase tracking-wide text-teal-800">Detik</p>
                    <p class="text-[11px] text-slate-500">tengah</p>
                </div>
                <span class="mb-7 px-0.5 font-mono text-lg font-bold text-slate-400">.</span>
                <div class="text-center">
                    <div class="flex gap-1">
                        <span class="seed-digit filled">7</span>
                        <span class="seed-digit filled">0</span>
                    </div>
                    <p class="mt-1.5 text-[11px] font-semibold uppercase tracking-wide text-teal-800">1/100 dtk</p>
                    <p class="text-[11px] text-slate-500">paling kanan</p>
                </div>
            </div>
            <p class="mt-3 text-center font-mono text-sm font-semibold text-teal-900">013470 → 01:34.70</p>
        </div>
    </div>

    <dl class="mt-4 space-y-2 text-sm text-slate-700">
        <div class="rounded-xl bg-white/80 px-3 py-2">
            <dt class="font-medium text-slate-900">Contoh 50 meter, 52,20 detik</dt>
            <dd class="mt-0.5 font-mono text-teal-800">005220 atau 5220 → 00:52.20</dd>
        </div>
        <div class="rounded-xl bg-white/80 px-3 py-2">
            <dt class="font-medium text-slate-900">Contoh 1 menit 34,70 detik</dt>
            <dd class="mt-0.5 font-mono text-teal-800">013470 → 01:34.70</dd>
        </div>
        <div class="rounded-xl bg-white/80 px-3 py-2">
            <dt class="font-medium text-slate-900">Belum punya catatan waktu</dt>
            <dd class="mt-0.5">Kosongkan, atau ketik <span class="font-mono font-semibold">NT</span>. Atlet tetap bisa daftar.</dd>
        </div>
    </dl>

    <div data-seed-field class="mt-4 rounded-xl border border-teal-100 bg-white p-3">
        <label for="seed-demo" class="block text-sm font-medium text-slate-800">Coba ketik di sini dulu</label>
        <input id="seed-demo" type="text" inputmode="numeric" autocomplete="off" maxlength="20" data-seed-input
            data-empty-preview="Ketik 013470 atau 5220, lalu lihat kotak di atas berubah."
            class="public-input font-mono tracking-wide" placeholder="013470" value="">
        <div data-seed-boxes class="mt-3 flex items-center gap-1" aria-hidden="true">
            <span data-seed-digit class="seed-digit">·</span>
            <span data-seed-digit class="seed-digit">·</span>
            <span class="px-0.5 font-mono text-slate-400">:</span>
            <span data-seed-digit class="seed-digit">·</span>
            <span data-seed-digit class="seed-digit">·</span>
            <span class="px-0.5 font-mono text-slate-400">.</span>
            <span data-seed-digit class="seed-digit">·</span>
            <span data-seed-digit class="seed-digit">·</span>
        </div>
        <p data-seed-preview class="mt-2 text-sm text-slate-600">Ketik 013470 atau 5220, lalu lihat hasilnya.</p>
    </div>

    <details class="mt-3 text-sm text-slate-600">
        <summary class="cursor-pointer font-medium text-teal-900">Cara lain yang juga diterima</summary>
        <p class="mt-2 leading-6">Boleh juga menulis <span class="font-mono">52.20</span>, <span class="font-mono">00:52.20</span>, atau <span class="font-mono">1:34.70</span>. Setelah diketik, cek tulisan “Terbaca …” di bawah kotak.</p>
    </details>
</section>
