<?php

use App\Http\Controllers\GatewayController;
Route::get('/gateway/payment/callback', [GatewayController::class, 'paymentCallbackUrl'])->name('payment.callback.management');