<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('heat_lane_id')->unique()->constrained()->restrictOnDelete();
            $table->integer('time_ms')->nullable();
            $table->string('status', 20)->default('ok');
            $table->string('dsq_code', 5)->nullable();
            $table->string('dsq_reason')->nullable();
            $table->foreignId('recorded_by')->constrained('users');
            $table->timestamp('recorded_at');
            $table->foreignId('verified_by')->nullable()->constrained('users');
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('results');
    }
};
