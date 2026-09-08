<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Satu baris mewakili satu kali pengiriman form pendaftaran publik: satu atlet,
     * satu atau beberapa nomor lomba, dan satu kontak pendaftar yang bisa dihubungi
     * panitia. Inilah pengganti akun pelatih sebagai pemilik sebuah pendaftaran.
     */
    public function up(): void
    {
        Schema::create('registration_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('competition_id')->constrained()->restrictOnDelete();
            $table->foreignId('athlete_id')->constrained()->restrictOnDelete();
            $table->string('code', 20)->unique();
            $table->string('registrant_name', 100);
            $table->string('registrant_phone', 20);
            $table->string('registrant_email', 120)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->index(['competition_id', 'created_at']);
        });

        Schema::table('registrations', function (Blueprint $table) {
            $table->foreignId('submission_id')
                ->nullable()
                ->after('athlete_id')
                ->constrained('registration_submissions')
                ->nullOnDelete();
        });

        // Entri publik tidak punya pengguna yang bertanggung jawab.
        Schema::table('registrations', function (Blueprint $table) {
            $table->dropForeign(['registered_by']);
        });

        Schema::table('registrations', function (Blueprint $table) {
            $table->unsignedBigInteger('registered_by')->nullable()->change();
        });

        Schema::table('registrations', function (Blueprint $table) {
            $table->foreign('registered_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('registrations', function (Blueprint $table) {
            $table->dropForeign(['submission_id']);
            $table->dropColumn('submission_id');
        });

        Schema::dropIfExists('registration_submissions');
    }
};
