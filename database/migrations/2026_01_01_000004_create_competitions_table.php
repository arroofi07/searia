<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('competitions', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 120)->unique();
            $table->string('name', 150);
            $table->string('venue', 200);
            $table->string('city', 100);
            $table->date('start_date')->index();
            $table->date('end_date');
            $table->dateTime('registration_opens_at');
            $table->dateTime('registration_closes_at');
            $table->dateTime('technical_meeting_at')->nullable();
            $table->string('type', 20);
            $table->smallInteger('pool_lanes')->default(8);
            $table->smallInteger('pool_length')->default(25);
            $table->smallInteger('max_events_per_athlete')->default(3);
            $table->string('seeding_mode', 20)->default('balanced');
            $table->integer('fee_per_event')->default(0);
            $table->integer('late_fee_per_event')->default(0);
            $table->string('status', 20)->default('draft')->index();
            $table->string('banner_path')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('competitions');
    }
};
