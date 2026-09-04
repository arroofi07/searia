<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('age_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('competition_id')->constrained()->cascadeOnDelete();
            $table->string('code', 10);
            $table->string('name', 50);
            $table->string('display_code', 10)->nullable();
            $table->smallInteger('birth_year_start');
            $table->smallInteger('birth_year_end');
            $table->smallInteger('sort_order');
            $table->timestamps();

            $table->unique(['competition_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('age_groups');
    }
};
