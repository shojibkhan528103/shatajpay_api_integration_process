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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone');
            $table->string('method');
            $table->decimal('amount', 10, 2)->default(1000);
            $table->string('currency', 10)->default('BDT');
            $table->string('status')->default('initialize');
            $table->string('transaction_id')->unique();
            $table->string('order_id')->nullable()->index();
            $table->string('event_name')->nullable();
            $table->json('event_data')->nullable();
            $table->text('address')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
