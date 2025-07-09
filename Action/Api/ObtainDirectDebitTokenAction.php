<?php

declare(strict_types=1);

namespace Orcaya\Payum\Payplace\Action\Api;

use Orcaya\Payum\Payplace\Request\Api\ObtainDirectDebitToken;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\Reply\HttpResponse;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class ObtainDirectDebitTokenAction extends BaseApiAwareAction implements LoggerAwareInterface
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    public function __construct()
    {
        parent::__construct();
        $this->logger = new NullLogger();
    }

    /**
     * @param ObtainDirectDebitToken $request
     */
    public function execute($request): void
    {
        RequestNotSupportedException::assertSupports($this, $request);

        $model = ArrayObject::ensureArrayObject($request->getModel());
        $model['status'] = 'pending';
        $model['posherr'] = '0';

        $formServiceUrl = $this->getFormServiceUrlWithParameters($model);        

        throw new HttpResponse($formServiceUrl);
    }

    public function supports($request): bool
    {
        return $request instanceof ObtainDirectDebitToken && $request->getModel() instanceof \ArrayAccess;
    }

    protected function getFormServiceUrl(): string
    {
        return $this->getApi()->getOption('sandbox') 
            ? 'https://testsystem.payplace.de/web-api/SSLPayment.po'
            : 'https://system.payplace.de/web-api/SSLPayment.po';
    }

    protected function getFormServiceUrlWithParameters($model): string
    {
        $formServiceParameters = [
            'amount' => number_format($model['amount'] / 100, 2, ',', ''),
            'basketid' => $model['number'],
            'command' => 'sslform',
            'currency' => $model['currency'],
            'date' => date('Ymd_H:i:s'),
            'orderid' => $model['orderid'],
            'paymentmethod' => 'directdebit',
            'sessionid' => $model['clientSession'],
            'sslmerchant' => $this->getApi()->getOption('ssl_merchant_id'),
            'transactiontype' => 'preauthorization',
            'payment_options' => '3dsecure20',
            'version' => '2.0',
            'tdsCustomerEmail' => $model['customer_email'],
            'tdsCustomerBillingAddress.city' => $model['city'],
            'tdsCustomerBillingAddress.country' => $model['country'],
            'tdsCustomerBillingAddress.line1' => $model['street'],
            'tdsCustomerBillingAddress.postCode' => $model['zip'],
            'tdsCustomerBillingAddress.state' => $model['state'],
            'tdsCustomerShippingAddress.city' => $model['city'],
            'tdsCustomerShippingAddress.country' => $model['country'],
            'tdsCustomerShippingAddress.line1' => $model['street'],
            'tdsCustomerShippingAddress.postCode' => $model['zip'],
            'tdsCustomerShippingAddress.state' => $model['state'],
            'notifyurl' => $this->getApi()->getOption('notify_url'),
        ];

        $hmac = $this->getHmac($this->getApi()->getOption('ssl_password'), $formServiceParameters);
        $formServiceParameters['hmac1'] = $hmac;

        if ($this->getApi()->getOption('sandbox') == false) {
            $formServiceParameters['notifyurl'] = $this->getApi()->getOption('notify_url');
        }
        
        // Build URL with proper encoding
        $queryPairs = [];
        foreach ($formServiceParameters as $parameter => $parameterValue) {
            if ($parameter === 'hmac1') {
                // HMAC is already hex-encoded, don't encode again
                $queryPairs[] = $parameter . '=' . $parameterValue;
            } else {
                // Use standard urlencode for final URL building
                $queryPairs[] = $parameter . '=' . urlencode($parameterValue);
            }
        }

        $formServiceUrl = $this->getFormServiceUrl() . '?' . implode('&', $queryPairs);

        return $formServiceUrl;
    }

    protected function getHmac($key, $parameters): string
    {
        // Step 1: Pad key to 64 bytes with null bytes (0x00)
        $paddedKey = $this->padKeyTo64Bytes($key);
        
        // Step 2: XOR padded key with 64 bytes of 0x36
        $ipadKey = $this->xorKeyWithPattern($paddedKey, 0x36);
        
        // Step 3: Sort parameters alphabetically by their names
        ksort($parameters);
        
        // Steps 4-7: Create query string with special URL encoding
        $queryString = $this->createPayplaceQueryString($parameters);
        
        // Step 7: Append query string to ipad key (no separator)
        $innerMessage = $ipadKey . $queryString;
        
        // Step 8: Apply SHA-256 hash function
        $innerHash = hash('sha256', $innerMessage, true);
        
        // Step 9: XOR padded key with 64 bytes of 0x5c
        $opadKey = $this->xorKeyWithPattern($paddedKey, 0x5c);
        
        // Step 10: Append inner hash to opad key (no separator)
        $outerMessage = $opadKey . $innerHash;
        
        // Step 11: Apply SHA-256 hash function and output as hexadecimal
        return hash('sha256', $outerMessage, false);
    }

    /**
     * Pads the key to exactly 64 bytes with null bytes (0x00)
     */
    protected function padKeyTo64Bytes(string $key): string
    {
        $keyLength = strlen($key);
        
        if ($keyLength > 64) {
            // If key is longer than 64 bytes, hash it first
            $key = hash('sha256', $key, true);
            $keyLength = strlen($key);
        }
        
        // Pad with null bytes to reach exactly 64 bytes
        return $key . str_repeat("\x00", 64 - $keyLength);
    }

    /**
     * XOR the 64-byte key with a pattern (0x36 or 0x5c)
     */
    protected function xorKeyWithPattern(string $paddedKey, int $pattern): string
    {
        $result = '';
        for ($i = 0; $i < 64; $i++) {
            $result .= chr(ord($paddedKey[$i]) ^ $pattern);
        }
        return $result;
    }

    /**
     * Creates a query string according to Payplace Express specification
     */
    protected function createPayplaceQueryString(array $parameters): string
    {
        $pairs = [];
        
        foreach ($parameters as $name => $value) {
            // Step 5: URL encode the parameter values with specific encoding
            $encodedValue = $this->payplaceUrlEncode((string)$value);
            
            // Step 6: Combine name and URL-encoded value with "=" separator
            $pairs[] = $name . '=' . $encodedValue;
        }
        
        // Step 7: Join name-value pairs with "&" separator
        return implode('&', $pairs);
    }

    /**
     * URL encodes according to Payplace Express specification:
     * - Letters A-Z and a-z remain unchanged
     * - Digits 0-9 remain unchanged  
     * - Special characters minus "-", underscore "_", dot ".", and tilde "~" remain unchanged
     * - All other characters are encoded as "%" followed by their hexadecimal UTF-8 value
     * - Spaces are encoded as "%20"
     * - Multi-byte UTF-8 characters get "%" before each byte
     */
    protected function payplaceUrlEncode(string $value): string
    {
        $encoded = '';
        $length = strlen($value);
        
        for ($i = 0; $i < $length; $i++) {
            $char = $value[$i];
            $ord = ord($char);
            
            // Check if character should remain unchanged
            if (
                ($ord >= 65 && $ord <= 90) ||   // A-Z
                ($ord >= 97 && $ord <= 122) ||  // a-z
                ($ord >= 48 && $ord <= 57) ||   // 0-9
                $char === '-' ||                // minus
                $char === '_' ||                // underscore
                $char === '.' ||                // dot
                $char === '~'                   // tilde
            ) {
                $encoded .= $char;
            } else {
                // All other characters are percent-encoded
                $encoded .= sprintf('%%%02X', $ord);
            }
        }
        
        return $encoded;
    } 
}