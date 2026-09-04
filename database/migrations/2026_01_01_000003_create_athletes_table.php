<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('athletes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('club_id')->constrained()->restrictOnDelete();
            $table->string('full_name', 100);
            $table->string('gender', 1)->index();
            $table->smallInteger('birth_year')->index();
            $table->date('birth_date')->nullable();
            $table->string('identity_number', 30)->nullable();
            $table->string('photo_path')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['club_id', 'full_name', 'birth_year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('athletes');
    }
};
