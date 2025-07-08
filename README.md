# Payum Payplace Gateway

Ein Payum-Gateway für die Integration des Payplace-Zahlungsanbieters mit Unterstützung für Kreditkarten- und SEPA-Lastschriftzahlungen über sichere iframe-Integration.

## Features

- ✅ **Kreditkartenzahlungen** mit 3D-Secure 2.0 Unterstützung
- ✅ **SEPA-Lastschriftzahlungen** (ELV)
- ✅ **Sichere iframe-Integration** für PCI-Compliance
- ✅ **Zwei-Phasen-Zahlungen**: Autorisierung + Buchung
- ✅ **Stornierungen** und **Erstattungen**
- ✅ **Sandbox- und Produktivmodus**
- ✅ **Vollständige Payum-Integration**
- ✅ **Event-basierte Benachrichtigungen**
- ✅ **Separate Templates** für Kreditkarte und Lastschrift

## Template-Implementierung

Das Gateway verwendet **separate Templates** für Kreditkarten- und Lastschriftzahlungen:

### Vorteile der separaten Templates:
- **Dedicated Benutzererfahrung** für jede Zahlungsart
- **Optimierte UI/UX** speziell für den jeweiligen Zahlungsfluss
- **Verbesserte Sicherheit** mit methodenspezifischer Validierung
- **Bessere Wartbarkeit** und Anpassungsmöglichkeiten

### Template-Struktur:

#### Kreditkarten-Template
**Datei**: `Resources/views/Action/obtain_token_credit_card.html.twig`
- Kreditkartenspezifische Formularfelder (Karteninhaber, Nummer, Ablauf, CVV)
- Kartenerkennung und Icon-Anzeige
- 3D-Secure 2.0 Integration
- Echtzeit-Validierungsfeedback
- Blaues Farbschema (verbunden mit Vertrauen und Sicherheit)

#### Lastschrift-Template
**Datei**: `Resources/views/Action/obtain_token_direct_debit.html.twig`
- SEPA-spezifische Formularfelder (Kontoinhaber, IBAN, BIC)
- Dynamische BIC-Feld-Sichtbarkeit basierend auf IBAN
- SEPA-Mandatsinformationen
- PPAN (pseudonymisierte PAN) Unterstützung
- Grünes Farbschema (verbunden mit Banking und Geld)



### PHP-Implementierung:

#### Neue Request-Klassen:
```php
// Für Kreditkartenzahlungen
Orcaya\Payum\Payplace\Request\ObtainCreditCardToken

// Für Lastschriftzahlungen
Orcaya\Payum\Payplace\Request\ObtainDirectDebitToken
```

#### Neue Action-Klassen:
```php
// Verarbeitet Kreditkarten-Token-Anfragen
Orcaya\Payum\Payplace\Action\ObtainCreditCardTokenAction

// Verarbeitet Lastschrift-Token-Anfragen
Orcaya\Payum\Payplace\Action\ObtainDirectDebitTokenAction
```

#### Automatische Template-Auswahl:
Die `AuthorizeAction` und `CaptureAction` erkennen automatisch die Zahlungsart:

```php
// Automatische Erkennung basierend auf payment_method
$paymentMethod = $model['payment_method'] ?? 'creditcard';

if ($paymentMethod === 'elv') {
    $this->gateway->execute(new ObtainDirectDebitToken($model));
} else {
    $this->gateway->execute(new ObtainCreditCardToken($model));
}
```

### Migrationsleitfaden

#### Neue Template-Implementierung

Die Gateway verwendet jetzt ausschließlich separate Templates für optimale Benutzererfahrung:

**Controller-Updates erforderlich:**
```php
// Korrekte Implementierung - spezifizieren Sie die Zahlungsart
if ($paymentMethod === 'elv') {
    $this->gateway->execute(new ObtainDirectDebitToken($model));
} else {
    $this->gateway->execute(new ObtainCreditCardToken($model));
}
```

#### Template-Anpassung
```twig
{# templates/bundles/PayumPayplace/Action/obtain_token_credit_card.html.twig #}
{% extends '@PayumPayplace/Action/obtain_token_credit_card.html.twig' %}

{% block header %}
    <h2>🏪 Ihr Shop - Kreditkartenzahlung</h2>
    <p>Sichere Zahlung powered by Payplace</p>
{% endblock %}
```

## Installation

### 1. Paket installieren

```bash
composer require orcaya/payum-payplace
```

### 2. Gateway registrieren

Registrieren Sie das Gateway in Ihrer Payum-Konfiguration:

```yaml
# config/packages/payum.yaml
payum:
    gateways:
        payplace:
            factory: payplace
            merchant_id: "%env(PAYPLACE_MERCHANT_ID)%"
            password: "%env(PAYPLACE_PASSWORD)%"
            sandbox: "%env(bool:PAYPLACE_SANDBOX)%"
            use_3dsecure: true
            iframe_width: "100%"
            iframe_height: "500px"
```

### 3. Umgebungsvariablen konfigurieren

```bash
# .env
PAYPLACE_MERCHANT_ID=your_merchant_id
PAYPLACE_PASSWORD=your_password
PAYPLACE_SANDBOX=true
```

## Konfiguration

### Gateway-Optionen

| Option | Typ | Standard | Beschreibung |
|--------|-----|----------|--------------|
| `merchant_id` | string | *erforderlich* | Ihre Payplace Merchant ID |
| `password` | string | *erforderlich* | Ihr Payplace API-Passwort |
| `sandbox` | boolean | `true` | Testsystem verwenden |
| `use_3dsecure` | boolean | `true` | 3D-Secure für Kreditkarten aktivieren |
| `iframe_width` | string | `"100%"` | Breite des Zahlungsformulars |
| `iframe_height` | string | `"500px"` | Höhe des Zahlungsformulars |

### URLs konfigurieren

Für die iframe-Integration müssen Sie folgende URLs in Ihrem System definieren:

```php
// Beispiel-Controller
$paymentDetails = [
    'orderid' => $order->getId(),
    'amount' => $order->getTotal(),
    'currency' => 'EUR',
    'customer_email' => $customer->getEmail(),
    'payment_method' => 'creditcard', // oder 'elv'
    'successurl' => $this->generateUrl('payment_success', [], UrlGeneratorInterface::ABSOLUTE_URL),
    'errorurl' => $this->generateUrl('payment_error', [], UrlGeneratorInterface::ABSOLUTE_URL),
    'backurl' => $this->generateUrl('payment_cancel', [], UrlGeneratorInterface::ABSOLUTE_URL),
    'notificationurl' => $this->generateUrl('payment_notify', [], UrlGeneratorInterface::ABSOLUTE_URL),
];
```

## Verwendung

### Basis-Zahlungsworkflow

```php
<?php

use Payum\Core\Payum;
use Payum\Core\Request\Capture;

// Payum-Service injizieren
/** @var Payum $payum */
$payum = $this->get('payum');

// Gateway abrufen
$gateway = $payum->getGateway('payplace');

// Zahlungsdetails definieren
$paymentDetails = [
    'orderid' => 'ORDER_123',
    'amount' => 2500, // 25,00 EUR in Cent
    'currency' => 'EUR',
    'customer_email' => 'customer@example.com',
    'payment_method' => 'creditcard', // oder 'elv' für SEPA
    'description' => 'Bestellung #123',
    
    // URLs für iframe-Integration
    'successurl' => 'https://example.com/payment/success',
    'errorurl' => 'https://example.com/payment/error', 
    'backurl' => 'https://example.com/payment/cancel',
    'notificationurl' => 'https://example.com/payment/notify',
];

// Zahlung durchführen (Autorisierung + Buchung)
$gateway->execute(new Capture($paymentDetails));
```

### Nur Autorisierung (Zwei-Phasen-Zahlung)

```php
use Payum\Core\Request\Authorize;

// Zahlung nur autorisieren
$gateway->execute(new Authorize($paymentDetails));

// Status prüfen
$gateway->execute($status = new GetStatus($paymentDetails));

if ($status->isAuthorized()) {
    // Später buchen
    $gateway->execute(new Capture($paymentDetails));
}
```

### Stornierung

```php
use Payum\Core\Request\Cancel;

// Autorisierte Zahlung stornieren
$gateway->execute(new Cancel($paymentDetails));
```

### Erstattung

```php
use Payum\Core\Request\Refund;

// Vollständige Erstattung
$paymentDetails['refund_amount'] = $paymentDetails['amount'];
$gateway->execute(new Refund($paymentDetails));

// Teilerstattung
$paymentDetails['refund_amount'] = 1000; // 10,00 EUR
$gateway->execute(new Refund($paymentDetails));
```

## Zahlungsmethoden

### Kreditkarte

```php
$paymentDetails = [
    'payment_method' => 'creditcard',
    // ... weitere Details
];
```

**Unterstützte Karten:**
- Visa
- Mastercard
- American Express
- 3D-Secure 2.0

### SEPA-Lastschrift (ELV)

```php
$paymentDetails = [
    'payment_method' => 'elv',
    // ... weitere Details
];
```

**Features:**
- IBAN-Validierung
- Mandatsreferenz-Generierung
- SEPA-konforme Abwicklung

## iframe-Integration

Das Gateway verwendet die sichere iframe-Technologie von Payplace:

### Workflow

1. **Initialisierung**: Gateway erstellt Payplace-Session
2. **iframe-Anzeige**: Zahlungsformular wird in iframe geladen
3. **Dateneingabe**: Kunde gibt Zahlungsdaten sicher ein
4. **Token-Generierung**: Payplace erstellt sicheren Token
5. **Autorisierung**: Zahlung wird mit Token autorisiert
6. **Buchung**: Bei Capture wird Zahlung eingezogen

### Sicherheit

- **PCI-Compliance**: Zahlungsdaten verlassen nie Ihr System
- **SSL/TLS**: Alle Übertragungen verschlüsselt
- **Token-basiert**: Keine Speicherung sensibler Daten
- **Domain-Validierung**: iframe nur für registrierte Domains

## Status-Management

Das Gateway unterstützt alle Payum-Status:

```php
use Payum\Core\Request\GetStatus;

$gateway->execute($status = new GetStatus($paymentDetails));

// Status prüfen
if ($status->isCaptured()) {
    // Zahlung erfolgreich eingezogen
} elseif ($status->isAuthorized()) {
    // Zahlung autorisiert, noch nicht eingezogen
} elseif ($status->isCanceled()) {
    // Zahlung storniert
} elseif ($status->isRefunded()) {
    // Zahlung erstattet
} elseif ($status->isFailed()) {
    // Zahlung fehlgeschlagen
    $errorMessage = $paymentDetails['rmsg'] ?? 'Unbekannter Fehler';
}
```

## Benachrichtigungen (Webhooks)

Payplace sendet Benachrichtigungen an Ihre `notificationurl`:

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

## Fehlerbehandlung

### API-Fehler

```php
use Orcaya\Payum\Payplace\Api;

$gateway->execute($status = new GetStatus($paymentDetails));

if ($status->isFailed()) {
    $errorCode = $paymentDetails[Api::FIELD_POSHERR] ?? 'unknown';
    $errorMessage = $paymentDetails[Api::FIELD_RMSG] ?? 'Unbekannter Fehler';
    
    // Logging
    $this->logger->error('Payplace payment failed', [
        'order_id' => $paymentDetails['orderid'],
        'error_code' => $errorCode,
        'error_message' => $errorMessage,
    ]);
}
```

### Häufige Fehlercodes

| Code | Beschreibung | Lösung |
|------|--------------|--------|
| `001` | Ungültige Merchant ID | Konfiguration prüfen |
| `002` | Ungültiges Passwort | Zugangsdaten prüfen |
| `101` | Ungültiger Betrag | Betrag muss > 0 sein |
| `201` | Karte abgelehnt | Kunde informieren |
| `301` | 3D-Secure fehlgeschlagen | Erneut versuchen |

## Logging

Das Gateway unterstützt PSR-3 Logging:

```yaml
# config/packages/monolog.yaml
monolog:
    channels: ['payplace']
    handlers:
        payplace:
            type: rotating_file
            path: '%kernel.logs_dir%/payplace.log'
            level: info
            channels: ['payplace']
```

```php
// Service-Konfiguration
$api = new \Orcaya\Payum\Payplace\Api($options);
$api->setLogger($this->get('monolog.logger.payplace'));
```

## Testing

### Sandbox-Modus

```yaml
# config/packages/test/payum.yaml
payum:
    gateways:
        payplace:
            sandbox: true
            merchant_id: "test_merchant"
            password: "test_password"
```

### Test-Kreditkarten

**Visa:**
- Nummer: `4111111111111111`
- CVV: `123`
- Gültig bis: `12/25`

**Mastercard:**
- Nummer: `5555555555554444`
- CVV: `123`
- Gültig bis: `12/25`

### Test-IBAN (SEPA)

- **Deutschland**: `DE89370400440532013000`
- **Österreich**: `AT611904300234573201`

## Produktivbetrieb

### Checkliste

- [ ] **SSL-Zertifikat** installiert und konfiguriert
- [ ] **Webhook-URLs** mit HTTPS erreichbar
- [ ] **Produktive Zugangsdaten** von Payplace erhalten
- [ ] **Domain** bei Payplace registriert
- [ ] **Sandbox-Modus** deaktiviert (`sandbox: false`)
- [ ] **Logging** konfiguriert
- [ ] **Monitoring** eingerichtet
- [ ] **Backup-Strategie** für Zahlungsdaten

### Performance-Optimierung

```yaml
# Cache für Gateway-Factory
framework:
    cache:
        pools:
            payplace.cache:
                adapter: cache.adapter.redis
                default_lifetime: 3600
```

## Support

### Probleme melden

Bei Problemen mit dem Gateway:

1. **Logs prüfen**: `var/log/payplace.log`
2. **Debug-Modus**: `sandbox: true` aktivieren
3. **Issue erstellen**: [GitHub Issues](https://github.com/orcaya/payum-payplace/issues)

### Payplace-Support

- **Dokumentation**: [Payplace Entwickler-Portal](https://developer.payplace.de)
- **Support**: support@payplace.de
- **Hotline**: +49 (0) 30 123456789

## Lizenz

MIT License - siehe [LICENSE.md](LICENSE.md)

## Mitwirken

Beiträge sind willkommen! Siehe [CONTRIBUTING.md](CONTRIBUTING.md) für Details.

---

**© 2024 Orcaya GmbH** - Entwickelt für die sichere Integration von Payplace-Zahlungen. 