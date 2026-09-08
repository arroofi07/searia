<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tagihan kini punya dua pemilik yang saling eksklusif: `submission_id` untuk
     * pendaftaran mandiri lewat form publik, dan `club_id` untuk entri yang masuk
     * lewat import Excel panitia. Aturan "satu klub satu tagihan" karenanya tidak
     * lagi bisa ditegakkan basis data dan pindah ke IssueInvoice.
     */
    public function up(): void
    {
        // MySQL memakai unique komposit ini sebagai index penopang foreign key
        // `competition_id`, sehingga kedua foreign key harus dilepas lebih dulu.
        // Saat dipasang kembali, MySQL membuat index penopangnya sendiri.
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropForeign(['competition_id']);
            $table->dropForeign(['club_id']);
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropUnique(['competition_id', 'club_id']);
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->unsignedBigInteger('club_id')->nullable()->change();
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->foreign('competition_id')->references('id')->on('competitions')->restrictOnDelete();
            $table->foreign('club_id')->references('id')->on('clubs')->restrictOnDelete();
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->foreignId('submission_id')
                ->nullable()
                ->after('club_id')
                ->unique()
                ->constrained('registration_submissions')
                ->cascadeOnDelete();
        });

        // Bukti transfer tidak lagi diunggah pendaftar, jadi tidak ada lagi antrean
        // "menunggu verifikasi": panitia langsung menandai lunas.
        DB::table('invoices')
            ->whereIn('status', ['waiting_verification', 'rejected'])
            ->update(['status' => 'unpaid']);

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn('proof_path');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->string('proof_path')->nullable()->after('line_items');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropForeign(['submission_id']);
            $table->dropColumn('submission_id');
        });

        // Tagihan tanpa klub tidak punya tempat pada aturan lama, jadi dihapus
        // sebelum kunci unik dan kolom wajib dipasang kembali.
        DB::table('invoices')->whereNull('club_id')->delete();

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropForeign(['competition_id']);
            $table->dropForeign(['club_id']);
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->unsignedBigInteger('club_id')->nullable(false)->change();
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->unique(['competition_id', 'club_id']);
            $table->foreign('competition_id')->references('id')->on('competitions')->restrictOnDelete();
            $table->foreign('club_id')->references('id')->on('clubs')->restrictOnDelete();
        });
    }
};
