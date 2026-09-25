<?php

namespace App\Http\Controllers;

use App\Models\SeminarParticipant;
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
                return redirect()->route('payment.failed.blade');
            }

            $transaction = Transaction::where('order_id', $orderId)->first();

            if (!$transaction) {
                return redirect()->route('payment.failed.blade');
            }

            if ($status === 'Complete') {
                if ($transaction->status === 'completed') {
                    $participant = SeminarParticipant::where(
                        'transaction_id',
                        $transaction->id
                    )->first();

                    if (!$participant) {
                        return redirect()->route('payment.failed.blade');
                    }

                    $this->setSuccessSession($participant->id);

                    return redirect()->route('payment.successful.blade');
                }

                if ($transaction->status !== 'pending') {
                    return redirect()->route('payment.failed.blade');
                }

                $participant = DB::transaction(function () use ($transaction) {
                    $transaction->update([
                        'status' => 'completed',
                    ]);

                    return SeminarParticipant::firstOrCreate(
                        [
                            'transaction_id' => $transaction->id,
                        ],
                        [
                            'name' => $transaction->name,
                            'mobile' => $transaction->phone,
                            'email' => $transaction->email,
                            'seminar_name' => $transaction->event_name,
                            'seminar_data' => $transaction->event_data,
                        ]
                    );
                });

                $this->sendConfirmMessage($transaction->phone, $transaction->amount);
                $this->setSuccessSession($participant->id);

                return redirect()->route('payment.successful.blade');
            }

            if ($status === 'Failed') {
                if ($transaction->status === 'pending') {
                    $transaction->update([
                        'status' => 'failed',
                    ]);
                }

                if ($transaction->status === 'failed') {
                    $this->setFailedSession($transaction->id);

                    return redirect()->route('payment.failed.blade');
                }

                return redirect()->route('payment.failed.blade');
            }

            return redirect()->route('payment.failed.blade');
        } catch (\Throwable $e) {
            report($e);

            return redirect()->route('payment.failed.blade');
        }
    }

    private function setSuccessSession($participantId)
    {
        session()->put([
            'seminar_participant_id' => $participantId,
            'seminar_success_expires_at' => now()->addMinute()->timestamp,
        ]);

        session()->forget([
            'seminar_failed_transaction_id',
            'seminar_failed_expires_at',
        ]);
    }

    private function setFailedSession($transactionId)
    {
        session()->put([
            'seminar_failed_transaction_id' => $transactionId,
            'seminar_failed_expires_at' => now()->addMinute()->timestamp,
        ]);

        session()->forget([
            'seminar_participant_id',
            'seminar_success_expires_at',
        ]);
    }

    private function sendConfirmMessage($UserPhoneNumber, $totalAmount)
    {
        $message = "Registration successful! Your payment of {$totalAmount}/- TK has been received.";
        $phoneNumber = "+88" . $UserPhoneNumber;

        $response = Http::get('https://sms.shataj.com/services/send.php', [
            'key' => '03e568ed96136a3c7e5d93f9e5aa9ca93f579400',
            'number' => $phoneNumber,
            'message' => $message,
            'type' => 'sms',
            'prioritize' => 0,
        ]);
    }
}
