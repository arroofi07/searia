<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('heat_lanes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('heat_id')->constrained()->cascadeOnDelete();
            $table->smallInteger('lane_number');
            $table->foreignId('registration_id')->constrained()->restrictOnDelete();
            $table->timestamps();

            $table->unique(['heat_id', 'lane_number']);
            $table->unique('registration_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('heat_lanes');
    }
};
