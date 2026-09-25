<?php

namespace App\Livewire\Frontend;

use App\Models\SeminarParticipant;
use App\Models\Transaction;
use App\Models\paymentgetway;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use phpDocumentor\Reflection\Types\This;

#[Layout('layouts.app')]
#[Title('Shataj Seminar Registration')]
class Index extends Component
{
    public $name = '';
    public $mobile = '';
    public $email = '';
    public $paymentMethod = 'shatajPay';
    public $totalAmount = 10;

    public array $seminar = [
        'title' => 'সেমিনার রেজিস্ট্রেশন',
        'name' => 'Shataj Seminar',
        'fee' => 1000,
        'currency' => 'BDT',
        'location' => 'Dhaka, Bangladesh',
        'date' => '',
        'time' => '',
        'description' => 'সেমিনারে অংশগ্রহণের জন্য নিচের তথ্য পূরণ করে রেজিস্ট্রেশন সম্পন্ন করুন।',
    ];

    protected function rules()
    {
        return [
            'name' => ['required', 'string', 'min:3', 'max:100'],
            'mobile' => ['required', 'regex:/^01[3-9]\d{8}$/'],
            'email' => ['nullable', 'email', 'max:150'],
            'paymentMethod' => ['required', 'in:shatajPay,bKash'],
        ];
    }

    protected function messages()
    {
        return [
            'name.required' => 'আপনার নাম দিন।',
            'name.min' => 'নাম কমপক্ষে ৩ অক্ষরের হতে হবে।',
            'mobile.required' => 'মোবাইল নম্বর দিন।',
            'mobile.regex' => 'সঠিক ১১ সংখ্যার বাংলাদেশি মোবাইল নম্বর দিন।',
            'email.email' => 'সঠিক ই-মেইল ঠিকানা দিন।',
            'paymentMethod.in' => 'সঠিক পেমেন্ট পদ্ধতি নির্বাচন করুন।',
        ];
    }

    public function updated($property)
    {
        $this->validateOnly($property);
    }

    public function selectPayment($method)
    {
        $this->paymentMethod = $method;
        $this->resetValidation('paymentMethod');
    }

    public function submit()
    {
        $this->validate();

        if ($this->participantExists()) {
            $this->addError(
                'mobile',
                'এই মোবাইল নম্বর দিয়ে ইতোমধ্যে সেমিনারে রেজিস্ট্রেশন সম্পন্ন হয়েছে।'
            );

            return;
        }

        $this->totalAmount = 10;

        $user = (object) [
            'name' => $this->name,
            'email' => $this->email,
            'mobileNumber' => $this->mobile,
        ];

        if ($this->paymentMethod === 'shatajPay') {
            $this->paymentGatewaySubmit($user, $this->seminar);
            return;
        }

        if ($this->paymentMethod === 'bKash') {
            $transaction = $this->createTransaction($user);

            if ($transaction) {
                $this->bkashPayment($transaction->id);
            }
        }
    }

    private function participantExists()
    {
        return SeminarParticipant::where('mobile', $this->mobile)->exists();
    }

    private function createTransaction($user)
    {
        try {
            return Transaction::create([
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->mobileNumber,
                'method' => $this->paymentMethod,
                'amount' => 10,
                'status' => 'pending',
                'transaction_id' => (string) Str::uuid(),
                'order_id' => (string) Str::uuid(),
                'currency' => 'BDT',
                'event_name' => $this->seminar['name'],
                'event_data' => $this->seminar,
            ]);
        } catch (\Throwable $e) {
            session()->flash('error', 'Payment creation failed.');
            return null;
        }
    }

    private function paymentGatewaySubmit($user, $event)
    {
        $gateway_crediential = $this->getOption('shatajPay');

        if (!$gateway_crediential) {
            session()->flash('error', 'Oops! Payment gateway credential not found.');
            return;
        }

        try {
            $transactionId = (string) Str::uuid();
            $orderId = (string) Str::uuid();

            $orderPayment = new Transaction();

            $orderPayment->name = $user->name;
            $orderPayment->email = $user->email;
            $orderPayment->method = $this->paymentMethod;
            $orderPayment->phone = $user->mobileNumber;
            $orderPayment->amount = $this->totalAmount;
            $orderPayment->status = 'pending';
            $orderPayment->address = '';
            $orderPayment->transaction_id = $transactionId;
            $orderPayment->order_id = $orderId;
            $orderPayment->currency = 'BDT';
            $orderPayment->event_name = $event['name'];
            $orderPayment->event_data = $event;

            if (!$orderPayment->save()) {
                session()->flash('error', 'Payment creation failed! Record was not saved.');
                return;
            }

            $this->createPayment($orderPayment, $user);
            $this->clearField();
        } catch (\Throwable $th) {
            report($th);
            session()->flash('error', 'Payment creation failed.');
        }
    }

    private function getOption($optionName)
    {
        return paymentgetway::where('getwayname', $optionName)->first();
    }

    private function createPayment($orderPayment, $user)
    {
        if (!$user || !$orderPayment) {
            return;
        }

        $crediential = $this->getOption('shatajPay');

        if (!$crediential) {
            session()->flash('error', 'Payment gateway credential not found.');
            return;
        }

        if ($crediential->getway_mode == 'production') {
            $apiUrl = 'http://pay.shataj.com/api/v1/user/payment-system';
        } else {
            return back()->with('status', 'Your Api Must be Live');
        }

        $headers = [
            'password' => $crediential->getway_storepassword,
            'username' => $crediential->getway_username,
            'appkey' => $crediential->getway_storeid,
            'appsecret' => $crediential->getway_appsecret,
            'sandbox' => $crediential->getway_mode == 'sandbox' ? 0 : 1,
            'gatewayid' => $crediential->getway_id,
        ];

        $body = [
            'amount' => $orderPayment->amount,
            'currency' => 'BDT',
            'name' => $user->name,
            'email' => $user->email,
            'mobile' => $user->mobileNumber,
            'Address' => 'Dhaka Mirpur',
            'opt_value_a' => '',
            'opt_value_b' => '',
            'transactionId' => $orderPayment->transaction_id,
            'orderId' => $orderPayment->order_id,
        ];

        try {
            $response = Http::timeout(10)
                ->withHeaders($headers)
                ->post($apiUrl, $body);

            if (!$response->successful()) {
                session()->flash('error', 'Payment failed: ' . $response->body());
                return;
            }

            if (
                $response->json('status') === true &&
                $response->json('status_code') === 200
            ) {
                $redirectUrl = $response->json('redirect_url');

                if ($redirectUrl) {
                    return redirect()->to($redirectUrl);
                }

                session()->flash('error', 'No redirection URL provided by the API.');
                return;
            }

            session()->flash(
                'error',
                $response->json('message') ?? 'Payment gateway rejected the request.'
            );
        } catch (\Throwable $e) {
            report($e);
            session()->flash('error', 'An error occurred while processing payment.');
        }
    }

    private function bkashPayment($orderId)
    {
        try {
            $this->redirect(route('payment.process', [
                'method' => 'Bkash',
                'payment_id' => $orderId,
            ]));
        } catch (\Throwable $th) {
            session()->flash('error', 'Unable to start bKash payment.');
        }
    }

    private function clearField()
    {
        $this->reset([
            'name',
            'mobile',
            'email',
        ]);

        $this->paymentMethod = 'shatajPay';
    }

    public function get_demo(){
        $this->redirectRoute('get.demo');
    }
    public function render()
    {
        return view('livewire.frontend.index');
    }
}