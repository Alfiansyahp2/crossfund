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
        Schema::create('wallet_topups', function (Blueprint $table) {
            $table->id();
            $table->string('reference_number')->unique();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->bigInteger('amount');
            $table->string('status')->default('pending'); // pending, approved, rejected
            $table->string('payment_proof')->nullable();
            $table->unsignedBigInteger('approved_by_admin_id')->nullable(); // no strict FK constraint here since admins are in tenant DB, but if it was central admin it could be. Since there are central admins? Wait, we said 'Admins' are in tenant DB. But who approves topups? Central Super Admin! Wait, we don't have a central admin table. Users table can have 'super_admin' role, or 'admin' role. Let's just leave it as integer.
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wallet_topups');
    }
};
