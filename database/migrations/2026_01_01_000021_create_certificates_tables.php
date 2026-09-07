<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('competitions', function (Blueprint $table) {
            $table->string('certificate_background_path')->nullable()->after('banner_path');
            $table->string('certificate_signer_name')->nullable()->after('certificate_background_path');
            $table->string('certificate_signer_title')->nullable()->after('certificate_signer_name');
        });

        Schema::create('certificates', function (Blueprint $table) {
            $table->id();
            $table->string('code', 32)->unique();
            $table->foreignId('competition_id')->constrained()->cascadeOnDelete();
            $table->foreignId('athlete_id')->constrained()->cascadeOnDelete();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('age_group_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('result_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 20);
            $table->unsignedSmallInteger('rank')->nullable();
            $table->integer('time_ms')->nullable();
            $table->string('status', 20)->nullable();
            $table->timestamps();

            $table->unique(['competition_id', 'athlete_id', 'event_id', 'type']);
        });

        Schema::create('certificate_archives', function (Blueprint $table) {
            $table->id();
            $table->foreignId('competition_id')->constrained()->cascadeOnDelete();
            $table->foreignId('requested_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('club_id')->nullable()->constrained()->nullOnDelete();
            $table->string('token', 64)->unique();
            $table->string('disk_path')->nullable();
            $table->string('status', 20)->default('pending');
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('ready_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('certificate_archives');
        Schema::dropIfExists('certificates');

        Schema::table('competitions', function (Blueprint $table) {
            $table->dropColumn([
                'certificate_background_path',
                'certificate_signer_name',
                'certificate_signer_title',
            ]);
        });
    }
};
