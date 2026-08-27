<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->foreignId('courier_id')->constrained('users')->cascadeOnDelete();
            $table->string('code_hash');
            $table->string('status')->default('pending');
            $table->unsignedTinyInteger('failed_attempts')->default(0);
            $table->text('reject_reason')->nullable();
            $table->timestamp('sms_sent_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamps();

            $table->index(['courier_id', 'status']);
            $table->index(['invoice_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('deliveries');
    }
};
