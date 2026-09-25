
    public function submit()
    {

        # necessery validation here then

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
    }

    // here transaction is optional but must store the transaction and order id for next callback url and after payment success or fail or cancel update transaction or necessery data that customer need
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
            $this->clearField(); # whatever user want with his form
        } catch (\Throwable $th) {
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

            session()->flash('error', $response->json('message') ?? 'Payment gateway rejected the request.');
        } catch (\Throwable $e) {
            session()->flash('error', 'An error occurred while processing payment.');
        }
    }
