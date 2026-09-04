<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('competition_id')->constrained()->cascadeOnDelete();
            $table->integer('event_number');
            $table->string('gender', 2);
            $table->smallInteger('distance');
            $table->string('stroke', 20);
            $table->string('equipment', 20)->default('none');
            $table->smallInteger('session')->default(1);
            $table->smallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['competition_id', 'event_number']);
            $table->index(['competition_id', 'session', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
