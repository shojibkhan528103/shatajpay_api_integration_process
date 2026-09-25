<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class GatewayController extends Controller
{
    public function paymentCallbackUrl(Request $request)
    {
        try {
            $status = $request->input('status');
            $orderId = $request->input('orderId');

            if (!$orderId || !$status) {
                return redirect() #that you want;
            }

            $transaction = Transaction::where('order_id', $orderId)->first();

            if (!$transaction) {
                return redirect() #that you want;
            }

            if ($status === 'Complete') {
                if ($transaction->status === 'completed') {
                    // Already success?? then TODO whatever your need 
                }

                if ($transaction->status !== 'pending') {
                return redirect() #that you want;
                }

                $transactionData = DB::transaction(function () use ($transaction) {
                    $transaction->update([
                        'status' => 'completed',
                    ]);

                    // after success TODO whatever your need 
                });

                // after success TODO whatever your need 
                // set confirmation message 

                return redirect() #that you want;
            }

            if ($status === 'Failed') {
                if ($transaction->status === 'pending') {
                    $transaction->update([
                        'status' => 'failed',
                    ]);
                }

                if ($transaction->status === 'failed') {
                // after failed/cancel TODO whatever your need 
                }

                return redirect() #that you want;
            }

                return redirect() #that you want;
        } catch (\Throwable $e) {
                return redirect() #that you want;
        }
    }
}
