<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('heats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->restrictOnDelete();
            $table->foreignId('age_group_id')->constrained()->restrictOnDelete();
            $table->smallInteger('heat_number');
            $table->string('round', 20)->default('final');
            $table->dateTime('scheduled_at')->nullable();
            $table->string('status', 20)->default('pending');
            $table->timestamp('locked_at')->nullable();
            $table->timestamp('seeded_at')->nullable();
            $table->timestamps();

            $table->unique(['event_id', 'age_group_id', 'round', 'heat_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('heats');
    }
};
