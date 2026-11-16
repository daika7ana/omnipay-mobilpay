# Omnipay: MobilPay

MobilPay (Netopia) driver for [Omnipay](https://github.com/thephpleague/omnipay): it encrypts a card
payment order, hands you the POST data for the provider's hosted payment page, and decrypts the IPN
notification the provider posts back to your server.

[![CI](https://github.com/daika7ana/omnipay-mobilpay/actions/workflows/ci.yml/badge.svg)](https://github.com/daika7ana/omnipay-mobilpay/actions/workflows/ci.yml)
[![Latest version](https://img.shields.io/packagist/v/daika7ana/omnipay-mobilpay.svg)](https://packagist.org/packages/daika7ana/omnipay-mobilpay)
[![Downloads](https://img.shields.io/packagist/dt/daika7ana/omnipay-mobilpay.svg)](https://packagist.org/packages/daika7ana/omnipay-mobilpay)
[![PHP](https://img.shields.io/packagist/php-v/daika7ana/omnipay-mobilpay.svg)](https://packagist.org/packages/daika7ana/omnipay-mobilpay)
[![License](https://img.shields.io/packagist/l/daika7ana/omnipay-mobilpay.svg)](LICENSE)

## Install

```bash
composer require daika7ana/omnipay-mobilpay:^1.6
```

## Requirements

PHP 8.2 or newer, `omnipay/common` ^3.5, and the OpenSSL extension with the `aes-256-cbc` cipher:
orders are sealed with it, and a missing algorithm fails the request instead of falling back.

## Usage

### Start a payment

```php
use Omnipay\Omnipay;

$gateway = Omnipay::create('MobilPay');
$gateway->setMerchantId('1234-5678-9012-3456-7890'); // merchant signature from Netopia
$gateway->setPublicKey('/path/to/public.cer');      // Netopia X.509 certificate
$gateway->setTestMode(true);                        // sandbox endpoint

/** @var \Omnipay\MobilPay\Message\PurchaseResponse $response */
$response = $gateway->purchase([
    'amount'     => '10.00',
    'currency'   => 'RON',
    'orderId'    => 'ORDER-1',
    'details'    => 'Test payment',
    'confirmUrl' => 'https://example.com/mobilpay/ipn',
    'returnUrl'  => 'https://example.com/mobilpay/return',
])->send();

$response->getRedirectUrl();    // https://sandboxsecure.mobilpay.ro, or https://secure.mobilpay.ro when live
$response->getRedirectData();   // ['env_key' => ..., 'data' => ..., 'cipher' => 'aes-256-cbc', 'iv' => ...]
$response->getRedirectMethod(); // 'POST', always
```

`amount`, `currency`, `orderId`, `details`, `confirmUrl` and `returnUrl` are all required; a missing
one throws an `InvalidRequestException` naming the parameter. `isSuccessful()` is `false` on this
response and `isRedirect()` is `true`, because the payment has not happened yet. Optional keys:

- `params` — `array` of extra name/value pairs; sent to the provider and echoed back in the IPN.
- `billingAddress` — `array`, described below.
- `testMode` — `bool`.
- `recurrence` — `bool`; when truthy, `paymentNo` and `intervalDay` are required and a `<recurrence>`
  element is added to the order.

The gateway also exposes `setCurrency()`, `setConfirmUrl()`, `setReturnUrl()`, `setBillingAddress()`,
`setRecurrence()`, `setPaymentNo()` and `setIntervalDay()` for values reused across payments. Gateway
parameters are merged into every request, and request parameters win.

### Render the redirect form yourself

Recommended for Inertia, SPA and API-driven apps: post the fields from your own markup. Hand
`getRedirectUrl()` and `getRedirectData()` to the view as `$url` and `$fields`:

```blade
<form action="{{ $url }}" method="post">
    @foreach ($fields as $name => $value)
        <input type="hidden" name="{{ $name }}" value="{{ $value }}">
    @endforeach
    <button type="submit">Continue to payment</button>
</form>
```

For classic request/response handlers there is a shortcut: `getRedirectResponse()` returns the Symfony
`Response` (the auto-submitting form document), and `redirect()` sends it immediately with `header()`
and `echo`. Neither calls `exit`, so the PHP request finishes normally.

### Billing address

`billingAddress` becomes the `<contact_info><billing>` element. `type` must be `person` or `company`;
any other value makes the driver drop the address without an error. The mapper reads every key
(`type`, `firstName`, `lastName`, `fiscalNumber`, `identityNumber`, `country`, `county`, `city`,
`zipCode`, `address`, `email`, `mobilePhone`, `bank`, `iban`), so pass the whole shape with empty
strings for unused fields. There is no shipping address parameter: the driver maps `billingAddress`
only, so a `shippingAddress` key is ignored.

### Complete a payment (IPN)

The provider POSTs `env_key`, `data`, `cipher` and `iv` to your `confirmUrl`. Pass those raw fields
plus the private key that belongs to the certificate:

```php
$gateway = Omnipay::create('MobilPay');  // setPrivateKey() on the gateway works too

$response = $gateway->completePurchase([
    'env_key'    => $request->input('env_key'),
    'data'       => $request->input('data'),
    'cipher'     => $request->input('cipher'),
    'iv'         => $request->input('iv'),
    'privateKey' => '/path/to/private.key',
])->send();

$response->getData();      // decrypted payload; objPmNotify holds the notification
$response->getMessage();   // action, e.g. 'confirmed'
$response->isSuccessful(); // true only for 'confirmed'
$response->isPending();    // true for 'confirmed_pending', 'paid_pending', 'paid'
```

`cipher` and `iv` are honoured for the AES payloads; a missing `cipher` falls back to `rc4` for legacy
notifications. `privateKey` is mandatory; without it the request throws `MissingKeyException`.
Undecryptable payloads and actions outside `confirmed_pending`, `paid_pending`, `paid`, `confirmed`,
`canceled` and `credit` do not throw: the response reports them through the acknowledgement XML.

Acknowledge the notification with the XML the provider expects:

```php
return response($response->getCrcResponse(), 200, ['Content-Type' => 'application/xml']);
```

`getCrcResponse()` returns `<crc>...</crc>`, or `<crc error_type="..." error_code="...">` when the
payload was rejected. `sendResponse()` still exists for older integrations but is deprecated: it calls
`header()` and `echo` directly, bypassing the framework response lifecycle.

## Development

```bash
composer test
```

PHPUnit 11 only. Each test generates a throwaway key and certificate in the system temp directory, so
the suite needs no fixtures, credentials or network access.
