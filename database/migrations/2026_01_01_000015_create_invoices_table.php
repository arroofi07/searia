<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('competition_id')->constrained()->restrictOnDelete();
            $table->foreignId('club_id')->constrained()->restrictOnDelete();
            $table->string('invoice_number', 30)->unique();
            $table->integer('item_count')->default(0);
            $table->integer('amount')->default(0);
            $table->string('proof_path')->nullable();
            $table->string('status', 30)->default('unpaid');
            $table->foreignId('verified_by')->nullable()->constrained('users');
            $table->timestamp('verified_at')->nullable();
            $table->dateTime('due_at')->nullable();
            $table->timestamps();

            $table->unique(['competition_id', 'club_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
