# Payum Payplace Gateway

A professional Payum gateway for integrating the Payplace payment provider with comprehensive support for credit card and SEPA direct debit payments via the secure Payplace form service.

**Developed by:** [ORCAYA GmbH, Stuttgart](https://www.orcaya.com/)  
**License:** MIT License  
**Version:** 1.0+

---

## �� Features

- ✅ **Credit card payments** with 3D-Secure 2.0 support
- ✅ **SEPA direct debit payments** with automatic mandate management
- ✅ **Payplace form service** for maximum PCI compliance
- ✅ **Secure redirect** to Payplace payment form
- ✅ **Two-phase payments**: Authorization + Capture
- ✅ **Cancellations** and **refunds**
- ✅ **Sandbox and production mode**
- ✅ **Token-based security** (no storing of sensitive data)
- ✅ **Event-based webhooks** for real-time notifications
- ✅ **PSR-3 logging** for comprehensive monitoring
- ✅ **Full Payum integration** with all standard actions
- ✅ **HMAC signing** of all requests for maximum security

## 📦 Installation

### 1. Install package

```bash
composer require orcaya/payum-payplace
```

### 2. Register gateway

```yaml
# config/packages/payum.yaml
payum:
    gateways:
        payplace:
            factory: payplace
            merchant_id: "%env(PAYPLACE_MERCHANT_ID)%"
            password: "%env(PAYPLACE_PASSWORD)%"
            ssl_merchant_id: "%env(PAYPLACE_SSL_MERCHANT_ID)%"
            ssl_password: "%env(PAYPLACE_SSL_PASSWORD)%"
            notify_url: "%env(PAYPLACE_NOTIFY_URL)%"
            sandbox: "%env(bool:PAYPLACE_SANDBOX)%"
            use_3dsecure: true
```

### 3. Environment variables

```bash
# .env
PAYPLACE_MERCHANT_ID=your_merchant_id
PAYPLACE_PASSWORD=your_api_password
PAYPLACE_SSL_MERCHANT_ID=your_ssl_merchant_id
PAYPLACE_SSL_PASSWORD=your_ssl_password
PAYPLACE_SANDBOX=true
PAYPLACE_NOTIFY_URL=https://your-domain.com/payment/notify
```

## 🔧 Configuration Options

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `merchant_id` | string | *required* | Your Payplace Merchant ID |
| `password` | string | *required* | Your Payplace API password |
| `ssl_merchant_id` | string | *required* | SSL Merchant ID for form service |
| `ssl_password` | string | *required* | SSL password for HMAC signing |
| `notify_url` | string | *required* | Webhook URL for notifications |
| `sandbox` | boolean | `true` | Use test system |
| `use_3dsecure` | boolean | `true` | Enable 3D-Secure for credit cards |

## 💳 Supported Payment Methods

### Credit Cards
- **Visa** (3D-Secure 2.0)
- **Mastercard** (3D-Secure 2.0)
- **American Express**
- **Diners Club**
- **JCB**

### SEPA Direct Debit
- **One-time payment** with automatic mandate
- **IBAN validation**
- **BIC detection**
- **Mandate reference generation**

## 🚀 Usage

### Basic Payment Workflow

```php
<?php

use Payum\Core\Payum;
use Payum\Core\Request\Capture;

// Get Payum service
/** @var Payum $payum */
$payum = $this->get('payum');
$gateway = $payum->getGateway('payplace');

// Define payment details
$paymentDetails = [
    'orderid' => 'ORDER_123',
    'amount' => 2500, // 25.00 EUR in cents
    'currency' => 'EUR',
    'payment_method' => 'creditcard', // or 'directdebit'
    'customer_email' => 'customer@example.com',
    'description' => 'Order #123',
    
    // Callback URLs for form service
    'successurl' => 'https://shop.example.com/payment/success',
    'errorurl' => 'https://shop.example.com/payment/error',
    'backurl' => 'https://shop.example.com/payment/cancel',
    'notificationurl' => 'https://shop.example.com/payment/notify',
];

// Reserve payment (redirects to Payplace form service)
$gateway->execute(new Authorize($paymentDetails));

// Execute booking after successful authorization
$gateway->execute(new Capture($paymentDetails));
```

### Credit Card Payment

```php
$paymentDetails = [
    'payment_method' => 'creditcard',
    'orderid' => 'ORDER_123',
    'amount' => 2500, // 25.00 EUR
    'currency' => 'EUR',
    'customer_email' => 'customer@example.com',
    'city' => 'Stuttgart',
    'country' => 'DE',
    'street' => 'Example Street 1',
    'zip' => '70173',
    // URLs...
];

// Reserve payment (redirects to Payplace form service)
$gateway->execute(new Authorize($paymentDetails));

// Execute booking after successful authorization
$gateway->execute(new Capture($paymentDetails));
```

### SEPA Direct Debit with Automatic Mandate

```php
$paymentDetails = [
    'payment_method' => 'directdebit',
    'orderid' => 'ORDER_456',
    'amount' => 5000, // 50.00 EUR
    'currency' => 'EUR',
    'customer_email' => 'customer@example.com',
    // URLs...
];

// Automatically creates SEPA mandate
$gateway->execute(new Authorize($paymentDetails));

// Execute reservation after mandate confirmation
$gateway->execute(new Authorize($paymentDetails));

// Execute booking after successful authorization
$gateway->execute(new Capture($paymentDetails));
```

### Two-Phase Payment (Authorization + Capture)

```php
use Payum\Core\Request\Authorize;
use Payum\Core\Request\Capture;
use Payum\Core\Request\GetStatus;

// Step 1: Authorize only
$gateway->execute(new Authorize($paymentDetails));

// Check status
$gateway->execute($status = new GetStatus($paymentDetails));

if ($status->isAuthorized()) {
    // Step 2: Capture funds
    $gateway->execute(new Capture($paymentDetails));
}
```

## 🛡️ Security & Form Service

### Workflow

1. **Initialization**: Gateway creates secure Payplace session
2. **Redirect**: Customer is redirected to Payplace form service
3. **Data entry**: Customer enters payment data on secure Payplace page
4. **Token generation**: Payplace creates encrypted token
5. **Return**: Customer is redirected back to your website
6. **Authorization**: Payment is authorized with token
7. **Capture**: Payment is captured if needed

### Form Service URLs

The gateway automatically redirects to Payplace form service URLs:

- **Sandbox**: `https://testsystem.payplace.de/web-api/SSLPayment.po`
- **Production**: `https://system.payplace.de/web-api/SSLPayment.po`

### Security Features

- ✅ **PCI-DSS Level 1**: Payment data never leaves your system
- ✅ **SSL/TLS**: All transmissions end-to-end encrypted
- ✅ **Token-based**: No storage of sensitive card data
- ✅ **3D-Secure 2.0**: Strong customer authentication
- ✅ **HMAC-SHA256**: All requests cryptographically signed
- ✅ **Domain validation**: Callbacks only for registered domains

### HMAC Signing

All requests to the form service are automatically signed with HMAC-SHA256:

```php
// Automatic HMAC generation for all parameters
$hmac = hash_hmac('sha256', http_build_query($parameters), $ssl_password);
```

## 📊 Status Management

```php
use Payum\Core\Request\GetStatus;

$gateway->execute($status = new GetStatus($paymentDetails));

if ($status->isCaptured()) {
    echo "Payment successful!";
} elseif ($status->isAuthorized()) {
    echo "Payment authorized";
} elseif ($status->isCanceled()) {
    echo "Payment canceled";
} elseif ($status->isRefunded()) {
    echo "Payment refunded";
} elseif ($status->isFailed()) {
    $errorMessage = $paymentDetails['rmsg'] ?? 'Unknown error';
    echo "Error: " . $errorMessage;
} elseif ($status->isPending()) {
    echo "Payment is being processed";
}
```

## 🔔 Webhook Integration

Payplace sends automatic notifications to your `notify_url`:

```php
// PaymentController.php
use Payum\Core\Request\Notify;

public function notifyAction(Request $request)
{
    $gateway = $this->get('payum')->getGateway('payplace');
    
    // Extract token from URL
    $token = $this->get('payum.security.http_request_verifier')
        ->verify($request);
    
    // Load payment
    $payment = $this->get('payum')->getStorage(Payment::class)
        ->find($token->getDetails());
    
    // Process notification
    $gateway->execute(new Notify($payment->getDetails()));
    
    // Update status
    $gateway->execute($status = new GetStatus($payment->getDetails()));
    
    if ($status->isCaptured()) {
        // Payment confirmed - complete order
        $this->processSuccessfulPayment($payment);
    }
    
    return new Response('OK', 200);
}
```

## 🔍 Logging & Debugging

The gateway supports PSR-3 logging for comprehensive monitoring:

```yaml
# config/packages/monolog.yaml
monolog:
    channels: ['payplace']
    handlers:
        payplace:
            type: stream
            path: '%kernel.logs_dir%/payplace.log'
            level: info
            channels: ['payplace']
```

## ⚠️ Error Handling

### Common Error Codes

| Code | Description | Solution |
|------|-------------|----------|
| `001` | Invalid Merchant ID | Check configuration |
| `002` | Invalid password | Verify credentials |
| `101` | Invalid amount | Amount must be > 0 |
| `201` | Card declined | Inform customer about decline |
| `301` | 3D-Secure failed | Offer retry |
| `401` | Invalid IBAN | Check IBAN format |
| `501` | Mandate declined | Check SEPA authorization |

### Error Handling in Code

```php
use Orcaya\Payum\Payplace\Api;

$gateway->execute($status = new GetStatus($paymentDetails));

if ($status->isFailed()) {
    $errorCode = $paymentDetails[Api::FIELD_POSHERR] ?? 'unknown';
    $errorMessage = $paymentDetails[Api::FIELD_RMSG] ?? 'Unknown error';
    
    // Structured logging
    $this->logger->error('Payplace payment failed', [
        'order_id' => $paymentDetails['orderid'],
        'error_code' => $errorCode,
        'error_message' => $errorMessage,
        'payment_method' => $paymentDetails['payment_method'],
    ]);
}
```

## 🧪 Testing

### Sandbox Mode

```yaml
# Always use sandbox for testing
payum:
    gateways:
        payplace:
            sandbox: true
```

### Test Credit Cards

| Card | Number | Result |
|------|--------|--------|
| Visa | `4111111111111111` | Successful |
| Mastercard | `5555555555554444` | Successful |
| Visa | `4000000000000002` | Declined |

### Test IBAN

| IBAN | Result |
|------|--------|
| `DE89370400440532013000` | Successful |
| `DE12500105170648489890` | Successful |
| `DE87123456781234567890` | Declined |

## 🏗️ Architecture

### Action Classes

The gateway implements the following Payum actions:

| Action | Description |
|--------|-------------|
| `AuthorizeAction` | Authorizes payments |
| `CaptureAction` | Captures authorized amounts |
| `CancelAction` | Cancels authorizations |
| `RefundAction` | Refunds payments |
| `StatusAction` | Determines payment status |
| `NotifyAction` | Processes webhooks |
| `ObtainCreditCardTokenAction` | Redirect to credit card form |
| `ObtainDirectDebitTokenAction` | Redirect to SEPA form |
| `ConvertPaymentAction` | Payum integration |

### Request Classes

| Request | Purpose |
|---------|---------|
| `ObtainCreditCardToken` | Credit card token request |
| `ObtainDirectDebitToken` | SEPA token request |
| `ObtainDirectDebitMandate` | SEPA mandate request |

### Form Service Parameters

#### Credit Cards

```php
$formServiceParameters = [
    'command' => 'sslform',
    'paymentmethod' => 'creditcard',
    'transactiontype' => 'preauthorization',
    'payment_options' => '3dsecure20;mobile;generate_ppan',
    'amount' => '25,00',
    'currency' => 'EUR',
    'orderid' => 'ORDER_123',
    'sslmerchant' => 'your_ssl_merchant_id',
    'version' => '2.0',
    'locale' => 'de',
    'hmac1' => 'generated_hmac_hash',
    // additional parameters...
];
```

#### SEPA Direct Debit

```php
$formServiceParameters = [
    'command' => 'sslform',
    'paymentmethod' => 'directdebit',
    'transactiontype' => 'preauthorization',
    'mandateid' => 'ORDER_456',
    'mandatesigned' => '20241201',
    'amount' => '50,00',
    'currency' => 'EUR',
    'orderid' => 'ORDER_456',
    'sslmerchant' => 'your_ssl_merchant_id',
    'version' => '2.0',
    'locale' => 'de',
    'hmac1' => 'generated_hmac_hash',
    // additional parameters...
];
```

## 🔗 Further Links

- **[Payplace Documentation](https://docs.payplace.de/)**
- **[Payum Framework](https://github.com/Payum/Payum)**
- **[ORCAYA GmbH](https://www.orcaya.com/)**

## 📞 Support

For questions or issues:

- **Email:** [info@orcaya.com](mailto:info@orcaya.com)
- **Website:** [www.orcaya.com](https://www.orcaya.com/)
- **Issues:** [GitHub Issues](https://github.com/orcaya/payum-payplace/issues)

## 📄 License

This project is licensed under the **MIT License**.

```
MIT License

Copyright (c) 2025 ORCAYA GmbH

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
```

---

**Developed with ❤️ by [ORCAYA GmbH](https://www.orcaya.com/), Stuttgart**
