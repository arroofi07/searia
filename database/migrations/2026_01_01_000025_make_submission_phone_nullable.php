<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Form publik tidak lagi meminta nomor WhatsApp. Kolom tetap ada untuk
     * pengiriman lama, tetapi boleh kosong.
     */
    public function up(): void
    {
        Schema::table('registration_submissions', function (Blueprint $table) {
            $table->string('registrant_phone', 20)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('registration_submissions', function (Blueprint $table) {
            $table->string('registrant_phone', 20)->nullable(false)->change();
        });
    }
};
