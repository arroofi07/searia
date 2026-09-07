<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_judge', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['event_id', 'user_id']);
            $table->index('user_id');
        });

        Schema::table('heats', function (Blueprint $table) {
            $table->timestamp('results_locked_at')->nullable()->after('locked_at');
        });

        Schema::table('competitions', function (Blueprint $table) {
            $table->boolean('fast_time_input')->default(true)->after('late_fee_per_event');
        });
    }

    public function down(): void
    {
        Schema::table('competitions', function (Blueprint $table) {
            $table->dropColumn('fast_time_input');
        });

        Schema::table('heats', function (Blueprint $table) {
            $table->dropColumn('results_locked_at');
        });

        Schema::dropIfExists('event_judge');
    }
};
