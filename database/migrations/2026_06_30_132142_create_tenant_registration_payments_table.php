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
        Schema::create('tenant_registration_payments', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_name');
            $table->string('email');
            $table->string('country');
            $table->string('kyc_status')->default('pending');
            $table->bigInteger('amount');
            $table->string('payment_proof')->nullable();
            $table->string('status')->default('pending');
            $table->unsignedBigInteger('approved_by_admin_id')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenant_registration_payments');
    }
};
