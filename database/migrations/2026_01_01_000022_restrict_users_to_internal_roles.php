<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pendaftaran peserta kini berjalan tanpa akun, sehingga hanya peran internal
     * (super admin, panitia, juri) yang tersisa. Akun pelatih dan peserta lama
     * dinonaktifkan alih-alih dihapus agar kolom audit `registered_by` tetap utuh.
     */
    public function up(): void
    {
        DB::table('users')
            ->whereIn('role', ['pelatih', 'peserta'])
            ->update([
                'role' => 'panitia',
                'is_active' => false,
            ]);

        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['club_id']);
            $table->dropColumn('club_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('club_id')->nullable()->after('role')->constrained()->nullOnDelete();
        });
    }
};
