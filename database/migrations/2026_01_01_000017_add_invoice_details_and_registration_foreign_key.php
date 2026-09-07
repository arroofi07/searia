<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->json('line_items')->nullable()->after('amount');
            $table->text('rejection_reason')->nullable()->after('status');
            $table->timestamp('reminder_sent_at')->nullable()->after('due_at');
        });

        Schema::table('registrations', function (Blueprint $table) {
            $table->foreign('invoice_id')->references('id')->on('invoices')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('registrations', function (Blueprint $table) {
            $table->dropForeign(['invoice_id']);
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['line_items', 'rejection_reason', 'reminder_sent_at']);
        });
    }
};
