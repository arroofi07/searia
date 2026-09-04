<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 30)->default('peserta')->index()->after('password');
            $table->foreignId('club_id')->nullable()->after('role')->constrained()->nullOnDelete();
            $table->string('phone', 20)->nullable()->after('club_id');
            $table->boolean('is_active')->default(true)->after('phone');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('club_id');
            $table->dropColumn(['role', 'phone', 'is_active']);
        });
    }
};
