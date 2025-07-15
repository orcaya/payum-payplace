# Payum Payplace Gateway

Ein professionelles Payum-Gateway für die Integration des Payplace-Zahlungsanbieters mit umfassender Unterstützung für Kreditkarten- und SEPA-Lastschriftzahlungen über den sicheren Payplace Formularservice.

**Entwickelt von:** [ORCAYA GmbH, Stuttgart](https://www.orcaya.com/)  
**Lizenz:** MIT License  
**Version:** 1.0+

---

## 🚀 Features

- ✅ **Kreditkartenzahlungen** mit 3D-Secure 2.0 Unterstützung
- ✅ **SEPA-Lastschriftzahlungen** mit automatischem Mandatsmanagement
- ✅ **Payplace Formularservice** für maximale PCI-Compliance
- ✅ **Sichere Weiterleitung** zum Payplace-Zahlungsformular
- ✅ **Zwei-Phasen-Zahlungen**: Autorisierung + Capture
- ✅ **Stornierungen** und **Erstattungen**
- ✅ **Sandbox- und Produktivmodus**
- ✅ **Token-basierte Sicherheit** (kein Speichern sensibler Daten)
- ✅ **Event-basierte Webhooks** für Echtzeit-Benachrichtigungen
- ✅ **PSR-3 Logging** für umfassendes Monitoring
- ✅ **Vollständige Payum-Integration** mit allen Standard-Actions
- ✅ **HMAC-Signierung** aller Anfragen für maximale Sicherheit

## 📦 Installation

### 1. Paket installieren

```bash
composer require orcaya/payum-payplace
```

### 2. Gateway registrieren

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

### 3. Umgebungsvariablen

```bash
# .env
PAYPLACE_MERCHANT_ID=your_merchant_id
PAYPLACE_PASSWORD=your_api_password
PAYPLACE_SSL_MERCHANT_ID=your_ssl_merchant_id
PAYPLACE_SSL_PASSWORD=your_ssl_password
PAYPLACE_SANDBOX=true
PAYPLACE_NOTIFY_URL=https://your-domain.com/payment/notify
```

## 🔧 Konfigurationsoptionen

| Option | Typ | Standard | Beschreibung |
|--------|-----|----------|--------------|
| `merchant_id` | string | *erforderlich* | Ihre Payplace Merchant ID |
| `password` | string | *erforderlich* | Ihr Payplace API-Passwort |
| `ssl_merchant_id` | string | *erforderlich* | SSL Merchant ID für Formularservice |
| `ssl_password` | string | *erforderlich* | SSL-Passwort für HMAC-Signierung |
| `notify_url` | string | *erforderlich* | Webhook-URL für Benachrichtigungen |
| `sandbox` | boolean | `true` | Testsystem verwenden |
| `use_3dsecure` | boolean | `true` | 3D-Secure für Kreditkarten aktivieren |

## 💳 Unterstützte Zahlungsmethoden

### Kreditkarten
- **Visa** (3D-Secure 2.0)
- **Mastercard** (3D-Secure 2.0)
- **American Express**
- **Diners Club**
- **JCB**

### SEPA-Lastschrift
- **Einmalzahlung** mit automatischem Mandat
- **IBAN-Validierung**
- **BIC-Erkennung**
- **Mandatsreferenz-Generierung**

## 🚀 Verwendung

### Basis-Zahlungsworkflow

```php
<?php

use Payum\Core\Payum;
use Payum\Core\Request\Capture;

// Payum-Service abrufen
/** @var Payum $payum */
$payum = $this->get('payum');
$gateway = $payum->getGateway('payplace');

// Zahlungsdetails definieren
$paymentDetails = [
    'orderid' => 'ORDER_123',
    'amount' => 2500, // 25,00 EUR in Cent
    'currency' => 'EUR',
    'payment_method' => 'creditcard', // oder 'directdebit'
    'customer_email' => 'customer@example.com',
    'description' => 'Bestellung #123',
    
    // Callback-URLs für Formularservice
    'successurl' => 'https://shop.example.com/payment/success',
    'errorurl' => 'https://shop.example.com/payment/error',
    'backurl' => 'https://shop.example.com/payment/cancel',
    'notificationurl' => 'https://shop.example.com/payment/notify',
];

// Zahlung reservieren (leitet zum Payplace Formularservice weiter)
$gateway->execute(new Authorize($paymentDetails));

// Führt die Buchung nach erfolgreicher Authorisierung durch
$gateway->execute(new Capture($paymentDetails));
```

### Kreditkartenzahlung

```php
$paymentDetails = [
    'payment_method' => 'creditcard',
    'orderid' => 'ORDER_123',
    'amount' => 2500, // 25,00 EUR
    'currency' => 'EUR',
    'customer_email' => 'customer@example.com',
    'city' => 'Stuttgart',
    'country' => 'DE',
    'street' => 'Musterstraße 1',
    'zip' => '70173',
    // URLs...
];

// Zahlung reservieren (leitet zum Payplace Formularservice weiter)
$gateway->execute(new Authorize($paymentDetails));

// Führt die Buchung nach erfolgreicher Authorisierung durch
$gateway->execute(new Capture($paymentDetails));
```

### SEPA-Lastschrift mit automatischem Mandat

```php
$paymentDetails = [
    'payment_method' => 'directdebit',
    'orderid' => 'ORDER_456',
    'amount' => 5000, // 50,00 EUR
    'currency' => 'EUR',
    'customer_email' => 'customer@example.com',
    // URLs...
];

// Erstellt automatisch SEPA-Mandat
$gateway->execute(new Authorize($paymentDetails));

// Führt nach Bestätigung des Mandats die Reservierung durch
$gateway->execute(new Authorize($paymentDetails));

// Führt die Buchung nach erfolgreicher Authorisierung durch
$gateway->execute(new Capture($paymentDetails));
```

### Zwei-Phasen-Zahlung (Autorisierung + Capture)

```php
use Payum\Core\Request\Authorize;
use Payum\Core\Request\Capture;
use Payum\Core\Request\GetStatus;

// 1. Schritt: Nur autorisieren
$gateway->execute(new Authorize($paymentDetails));

// Status prüfen
$gateway->execute($status = new GetStatus($paymentDetails));

if ($status->isAuthorized()) {
    // 2. Schritt: Geld einziehen
    $gateway->execute(new Capture($paymentDetails));
}
```

## 🛡️ Sicherheit & Formularservice

### Workflow

1. **Initialisierung**: Gateway erstellt sichere Payplace-Session
2. **Weiterleitung**: Kunde wird zum Payplace Formularservice weitergeleitet
3. **Dateneingabe**: Kunde gibt Zahlungsdaten auf der sicheren Payplace-Seite ein
4. **Token-Generierung**: Payplace erstellt verschlüsselten Token
5. **Rückleitung**: Kunde wird zurück zu Ihrer Website geleitet
6. **Autorisierung**: Zahlung wird mit Token autorisiert
7. **Capture**: Bei Bedarf wird Zahlung eingezogen

### Formularservice-URLs

Das Gateway leitet automatisch zu den Payplace Formularservice-URLs weiter:

- **Sandbox**: `https://testsystem.payplace.de/web-api/SSLPayment.po`
- **Produktiv**: `https://system.payplace.de/web-api/SSLPayment.po`

### Sicherheitsfeatures

- ✅ **PCI-DSS Level 1**: Zahlungsdaten verlassen nie Ihr System
- ✅ **SSL/TLS**: Alle Übertragungen end-to-end verschlüsselt
- ✅ **Token-basiert**: Keine Speicherung sensibler Kartendaten
- ✅ **3D-Secure 2.0**: Starke Kundenauthentifizierung
- ✅ **HMAC-SHA256**: Alle Anfragen kryptographisch signiert
- ✅ **Domain-Validierung**: Callbacks nur für registrierte Domains

### HMAC-Signierung

Alle Anfragen an den Formularservice werden automatisch mit HMAC-SHA256 signiert:

```php
// Automatische HMAC-Generierung für alle Parameter
$hmac = hash_hmac('sha256', http_build_query($parameters), $ssl_password);
```

## 📊 Status-Management

```php
use Payum\Core\Request\GetStatus;

$gateway->execute($status = new GetStatus($paymentDetails));

if ($status->isCaptured()) {
    echo "Zahlung erfolgreich!";
} elseif ($status->isAuthorized()) {
    echo "Zahlung autorisiert";
} elseif ($status->isCanceled()) {
    echo "Zahlung storniert";
} elseif ($status->isRefunded()) {
    echo "Zahlung erstattet";
} elseif ($status->isFailed()) {
    $errorMessage = $paymentDetails['rmsg'] ?? 'Unbekannter Fehler';
    echo "Fehler: " . $errorMessage;
} elseif ($status->isPending()) {
    echo "Zahlung wird verarbeitet";
}
```

## 🔔 Webhook-Integration

Payplace sendet automatische Benachrichtigungen an Ihre `notify_url`:

```php
// PaymentController.php
use Payum\Core\Request\Notify;

public function notifyAction(Request $request)
{
    $gateway = $this->get('payum')->getGateway('payplace');
    
    // Token aus URL extrahieren
    $token = $this->get('payum.security.http_request_verifier')
        ->verify($request);
    
    // Zahlung laden
    $payment = $this->get('payum')->getStorage(Payment::class)
        ->find($token->getDetails());
    
    // Benachrichtigung verarbeiten
    $gateway->execute(new Notify($payment->getDetails()));
    
    // Status aktualisieren
    $gateway->execute($status = new GetStatus($payment->getDetails()));
    
    if ($status->isCaptured()) {
        // Zahlung bestätigt - Bestellung abschließen
        $this->processSuccessfulPayment($payment);
    }
    
    return new Response('OK', 200);
}
```

## 🔍 Logging & Debugging

Das Gateway unterstützt PSR-3 Logging für umfassendes Monitoring:

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

## ⚠️ Fehlerbehandlung

### Häufige Fehlercodes

| Code | Beschreibung | Lösung |
|------|--------------|--------|
| `001` | Ungültige Merchant ID | Konfiguration prüfen |
| `002` | Ungültiges Passwort | Zugangsdaten überprüfen |
| `101` | Ungültiger Betrag | Betrag muss > 0 sein |
| `201` | Karte abgelehnt | Kunde über Ablehnung informieren |
| `301` | 3D-Secure fehlgeschlagen | Erneuten Versuch anbieten |
| `401` | IBAN ungültig | IBAN-Format prüfen |
| `501` | Mandat abgelehnt | SEPA-Berechtigung prüfen |

### Fehlerbehandlung im Code

```php
use Orcaya\Payum\Payplace\Api;

$gateway->execute($status = new GetStatus($paymentDetails));

if ($status->isFailed()) {
    $errorCode = $paymentDetails[Api::FIELD_POSHERR] ?? 'unknown';
    $errorMessage = $paymentDetails[Api::FIELD_RMSG] ?? 'Unbekannter Fehler';
    
    // Strukturiertes Logging
    $this->logger->error('Payplace payment failed', [
        'order_id' => $paymentDetails['orderid'],
        'error_code' => $errorCode,
        'error_message' => $errorMessage,
        'payment_method' => $paymentDetails['payment_method'],
    ]);
}
```

## 🧪 Testing

### Sandbox-Modus

```yaml
# Für Tests immer Sandbox verwenden
payum:
    gateways:
        payplace:
            sandbox: true
```

### Test-Kreditkarten

| Karte | Nummer | Ergebnis |
|-------|--------|----------|
| Visa | `4111111111111111` | Erfolgreich |
| Mastercard | `5555555555554444` | Erfolgreich |
| Visa | `4000000000000002` | Abgelehnt |

### Test-IBAN

| IBAN | Ergebnis |
|------|----------|
| `DE89370400440532013000` | Erfolgreich |
| `DE12500105170648489890` | Erfolgreich |
| `DE87123456781234567890` | Abgelehnt |

## 🏗️ Architektur

### Action-Klassen

Das Gateway implementiert folgende Payum-Actions:

| Action | Beschreibung |
|--------|--------------|
| `AuthorizeAction` | Autorisiert Zahlungen |
| `CaptureAction` | Zieht autorisierte Beträge ein |
| `CancelAction` | Storniert Autorisierungen |
| `RefundAction` | Erstattung von Zahlungen |
| `StatusAction` | Ermittelt Zahlungsstatus |
| `NotifyAction` | Verarbeitet Webhooks |
| `ObtainCreditCardTokenAction` | Weiterleitung zum Kreditkarten-Formular |
| `ObtainDirectDebitTokenAction` | Weiterleitung zum SEPA-Formular |
| `ConvertPaymentAction` | Payum-Integration |

### Request-Klassen

| Request | Zweck |
|---------|-------|
| `ObtainCreditCardToken` | Kreditkarten-Token-Anfrage |
| `ObtainDirectDebitToken` | SEPA-Token-Anfrage |
| `ObtainDirectDebitMandate` | SEPA-Mandat-Anfrage |

### Formularservice-Parameter

#### Kreditkarten

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
    // weitere Parameter...
];
```

#### SEPA-Lastschrift

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
    // weitere Parameter...
];
```

## 🔗 Weiterführende Links

- **[Payplace Dokumentation](https://docs.payplace.de/)**
- **[Payum Framework](https://github.com/Payum/Payum)**
- **[ORCAYA GmbH](https://www.orcaya.com/)**

## 📞 Support

Bei Fragen oder Problemen:

- **E-Mail:** [infocom](mailto:info@orcaya.com)
- **Website:** [www.orcaya.com](https://www.orcaya.com/)
- **Issues:** [GitHub Issues](https://github.com/orcaya/payum-payplace/issues)

## 📄 Lizenz

Dieses Projekt steht unter der **MIT-Lizenz**.

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

**Entwickelt mit ❤️ von [ORCAYA GmbH](https://www.orcaya.comx/), Stuttgart**
