<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('registrations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('competition_id')->index();
            $table->unsignedBigInteger('event_id');
            $table->foreignId('athlete_id')->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('age_group_id');
            $table->integer('seed_time_ms')->nullable();
            $table->string('status', 20)->default('draft');
            $table->string('rejection_reason')->nullable();
            $table->foreignId('registered_by')->constrained('users');
            $table->unsignedBigInteger('import_batch_id')->nullable();
            $table->unsignedBigInteger('invoice_id')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users');
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();

            $table->unique(['event_id', 'athlete_id']);
            $table->index(['competition_id', 'status']);
            $table->index(['event_id', 'age_group_id', 'seed_time_ms']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('registrations');
    }
};
