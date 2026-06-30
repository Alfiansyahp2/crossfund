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
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('issuer_id')->constrained('issuers')->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('status')->default('submitted'); // submitted, published, funded, locked, completed
            $table->bigInteger('funding_target');
            $table->bigInteger('current_funding')->default(0);
            $table->bigInteger('minimum_investment')->default(0);
            $table->integer('lock_period_months');
            $table->decimal('gross_return_rate', 5, 2)->nullable();
            $table->decimal('investor_return_rate', 5, 2)->nullable();
            $table->timestamp('funding_end_date')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
