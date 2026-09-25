<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * here transaction is optional but must store the transaction and order id for next callback url and after payment success or fail or cancel update transaction or your expected table data that need
     */
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone');
            $table->string('address')->nullable();
            $table->string('method');
            $table->decimal('amount', 10, 2)->default(1000);
            $table->string('currency', 10)->default('BDT');
            $table->string('status')->default('initialize');
            $table->string('transaction_id')->unique();
            $table->string('order_id')->nullable()->index();
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
