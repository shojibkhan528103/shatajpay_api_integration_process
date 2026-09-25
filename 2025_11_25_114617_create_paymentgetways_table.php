<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * this is for dynamic crediential store and use for shataj payment gateway.you can also keep all crediential on env and config , then you can use also directly without dynamic storing.
     */
    public function up(): void
    {
        Schema::create('paymentgetways', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('getwayname')->nullable();
            $table->string('displayname')->nullable();
            $table->string('logo')->nullable();
            $table->string('getway_id')->nullable();
            $table->string('getway_storeid')->nullable()->comment('app_key');
            $table->string('getway_appsecret')->nullable()->comment('app_secret');
            $table->string('getway_storepassword')->nullable()->comment('app_password');
            $table->string('getway_username')->nullable()->comment('app_username');
            $table->string('getway_mode')->nullable()->comment('sandbox,production');
            $table->string('getway_status')->default(0)->comment('1=active,0=inactive');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('paymentgetways');
    }
};
