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
        Schema::create('commissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignId('investment_id')->constrained('investments')->cascadeOnDelete();
            $table->unsignedBigInteger('recipient_user_id'); // FK to Central DB users table
            $table->string('commission_type'); // referral, direct_agent, override_agent
            $table->decimal('commission_rate_used', 5, 2);
            $table->decimal('exchange_rate_used', 16, 8);
            $table->bigInteger('amount'); // in home currency minor units
            $table->string('status')->default('pending'); // pending, paid
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('commissions');
    }
};
