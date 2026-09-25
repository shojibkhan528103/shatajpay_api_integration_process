# ShatajPay Payment Gateway API Documentation

## Payment Initialization API

### Api Base URL

```text
$apiUrl = 'http://pay.shataj.com';
```
### Endpoint

```text
POST /api/v1/user/payment-system
```

### Headers

| Parameter   | Type    | Required | Description              |
| ----------- | ------- | -------: | ------------------------ |
| `password`  | string  |      Yes | ShatajPay app password   |
| `username`  | string  |      Yes | ShatajPay app username   |
| `appkey`    | string  |      Yes | ShatajPay app key        |
| `appsecret` | string  |      Yes | ShatajPay app secret     |
| `sandbox`   | integer |      Yes | Payment environment mode |
| `gatewayid` | string  |      Yes | ShatajPay gateway ID     |

### Request Parameters

| Parameter       | Type   | Required | Description               |
| --------------- | ------ | -------: | ------------------------- |
| `amount`        | number |      Yes | Payment amount            |
| `currency`      | string |      Yes | Currency code, e.g. `BDT` |
| `name`          | string |      Yes | Customer name             |
| `email`         | string |      Yes | Customer email            |
| `mobile`        | string |      Yes | Customer mobile number    |
| `Address`       | string |       No | Customer address          |
| `opt_value_a`   | string |       No | Optional value            |
| `opt_value_b`   | string |       No | Optional value            |
| `transactionId` | string |      Yes | Unique transaction ID     |
| `orderId`       | string |      Yes | Unique order ID           |

### Request Example

```json
{
    "amount": 1000,
    "currency": "BDT",
    "name": "Customer Name",
    "email": "customer@example.com",
    "mobile": "01700000000",
    "Address": "Dhaka, Bangladesh",
    "opt_value_a": "",
    "opt_value_b": "",
    "transactionId": "550e8400-e29b-41d4-a716-446655440000",
    "orderId": "7b9e3d9a-3f2a-4f4d-9f3b-123456789abc"
}
```

## Success Response

When the payment request is successfully initialized, the API returns a payment redirect URL.

```json
{
    "status": true,
    "status_code": 200,
    "message": "Payment initialized successfully",
    "redirect_url": "..."
}
```

The customer should be redirected to the returned `redirect_url` to continue the payment.

## Error Response

Example:

```json
{
    "status": false,
    "status_code": 400,
    "message": "Payment request failed"
}
```

## Laravel Integration

### Send Payment Request

```php
$response = Http::timeout(10)
    ->withHeaders([
        'password' => $gateway->getway_storepassword,
        'username' => $gateway->getway_username,
        'appkey' => $gateway->getway_storeid,
        'appsecret' => $gateway->getway_appsecret,
        'sandbox' => $gateway->getway_mode === 'sandbox' ? 0 : 1,
        'gatewayid' => $gateway->getway_id,
    ])
    ->post($apiUrl, [
        'amount' => $transaction->amount,
        'currency' => 'BDT',
        'name' => $user->name,
        'email' => $user->email,
        'mobile' => $user->mobileNumber,
        'Address' => $user->address ?? '',
        'opt_value_a' => '',
        'opt_value_b' => '',
        'transactionId' => $transaction->transaction_id,
        'orderId' => $transaction->order_id,
    ]);
```

### Check Response

```php
if (
    $response->successful() &&
    $response->json('status') === true &&
    $response->json('status_code') === 200
) {
    $redirectUrl = $response->json('redirect_url');

    return redirect()->to($redirectUrl);
}
```

## Payment Callback API

After the customer completes the payment, ShatajPay redirects the customer to the configured callback URL.

### Route

```php
Route::get(
    '/gateway/payment/callback',
    [GatewayController::class, 'paymentCallbackUrl']
)->name('payment.callback.management');
```

### Callback Parameters

| Parameter | Type   | Required | Description                          |
| --------- | ------ | -------: | ------------------------------------ |
| `status`  | string |      Yes | Payment status returned by ShatajPay |
| `orderId` | string |      Yes | Original order ID                    |

### Successful Payment

```text
status=Complete
```

When the callback status is `Complete`, the corresponding local transaction should be updated to:

```text
completed
```

### Failed Payment

```text
status=Failed
```

When the callback status is `Failed`, the corresponding local transaction should be updated to:

```text
failed
```

## Transaction Status

| Status       | Description                                |
| ------------ | ------------------------------------------ |
| `initialize` | Transaction has been created               |
| `pending`    | Payment request has been sent to ShatajPay |
| `completed`  | Payment has been successfully completed    |
| `failed`     | Payment has failed                         |

## Transaction Data

The local transaction record should contain the following information:

| Field            | Description                   |
| ---------------- | ----------------------------- |
| `name`           | Customer name                 |
| `email`          | Customer email                |
| `phone`          | Customer phone number         |
| `address`        | Customer address              |
| `method`         | Payment method                |
| `amount`         | Transaction amount            |
| `currency`       | Transaction currency          |
| `status`         | Current transaction status    |
| `transaction_id` | Unique payment transaction ID |
| `order_id`       | Unique order ID               |
| `created_at`     | Transaction creation time     |
| `updated_at`     | Last update time              |

### Transaction Example

```json
{
    "name": "Customer Name",
    "email": "customer@example.com",
    "phone": "01700000000",
    "method": "shatajPay",
    "amount": 1000,
    "currency": "BDT",
    "status": "pending",
    "transaction_id": "550e8400-e29b-41d4-a716-446655440000",
    "order_id": "7b9e3d9a-3f2a-4f4d-9f3b-123456789abc"
}
```

## Transaction ID and Order ID

Both identifiers must be unique for every payment request.

```text
transactionId → Payment transaction identifier
orderId        → Merchant order identifier
```

Example:

```text
transactionId: 550e8400-e29b-41d4-a716-446655440000
orderId:       7b9e3d9a-3f2a-4f4d-9f3b-123456789abc
```

The `orderId` is used to identify the local transaction when processing the payment callback.

## Security

* ShatajPay credentials must remain on the backend.
* Do not expose `appsecret`, password, or other gateway credentials to the frontend.
* Use unique `transactionId` and `orderId` values for every payment.
* Do not mark a transaction as `completed` when only the payment initialization request is successful.
* Update the final transaction status based on the payment callback.