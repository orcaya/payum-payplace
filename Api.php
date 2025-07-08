<?php

declare(strict_types=1);

namespace Orcaya\Payum\Payplace;

use GuzzleHttp\Psr7\Request;
use Payum\Core\Bridge\Guzzle\HttpClientFactory;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\Http\HttpException;
use Payum\Core\Exception\InvalidArgumentException;
use Payum\Core\HttpClientInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

class Api implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    // Payplace API Commands
    public const COMMAND_OPEN = 'open';
    public const COMMAND_PREAUTHORIZATION = 'preauthorization';
    public const COMMAND_AUTHORIZATION = 'authorization';
    public const COMMAND_CAPTURE = 'capture';
    public const COMMAND_REVERSAL = 'reversal';
    public const COMMAND_REFUND = 'refund';
    public const COMMAND_CREDIT = 'credit';

    // Payment Options
    public const PAYMENT_OPTIONS_INIT_IFRAME = 'init_iframe';
    public const PAYMENT_OPTIONS_CREDITCARD = 'creditcard';
    public const PAYMENT_OPTIONS_ELV = 'elv';
    public const PAYMENT_OPTIONS_GENERATE_PPAN = 'generate_ppan';
    public const PAYMENT_OPTIONS_3DSECURE20 = '3dsecure20';

    // Status Constants
    public const STATUS_SUCCESS = '000';
    public const POSHERR_SUCCESS = '0';

    // Field Constants
    public const FIELD_POSHERR = 'posherr';
    public const FIELD_RC = 'rc';
    public const FIELD_RMSG = 'rmsg';
    public const FIELD_TREFNUM = 'trefnum';
    public const FIELD_RETREFNR = 'retrefnr';
    public const FIELD_CLIENT_SESSION = 'clientSession';
    public const FIELD_CLIENT_CONFIGURATION = 'clientConfiguration';
    public const FIELD_TOKEN = 'token';
    public const FIELD_PPAN = 'ppan';
    public const FIELD_STATUS = 'status';

    /**
     * @var array
     */
    protected $options = [];

    /**
     * @var HttpClientInterface
     */
    protected $client;

    /**
     * @param array $options
     * @param HttpClientInterface|null $client
     *
     * @throws InvalidArgumentException if an option is invalid
     */
    public function __construct(array $options, HttpClientInterface $client = null)
    {
        $options = ArrayObject::ensureArrayObject($options);
        
        $options->defaults($this->options);
        $options->validateNotEmpty([
            'merchant_id',
            'password',
            'ssl_merchant_id',
            'ssl_password',
            'notify_url'
        ]);

        if (false === is_bool($options['sandbox'])) {
            throw new InvalidArgumentException('The boolean sandbox option must be set.');
        }

        $this->options = $options;
        $this->client = $client ?: HttpClientFactory::create();
        $this->logger = new NullLogger();
    }

    public function obtainCreditCardToken(): bool
    {
        return true;
    }

    /**
     * Initialize iframe session for payment
     *
     * @param array $fields
     * @return array
     */
    public function initializeIframe(array $fields): array
    {
        $fields['version'] = '1.1';
        $fields['command'] = self::COMMAND_OPEN;
        
        if (!isset($fields['payment_options'])) {
            $fields['payment_options'] = self::PAYMENT_OPTIONS_INIT_IFRAME;
        }

        $this->logger->info('Initializing Payplace iframe', ['fields' => $this->sanitizeLogData($fields)]);

        return $this->decodeResponse($this->doRequest($fields));
    }

    /**
     * Authorize payment with token
     *
     * @param array $fields
     * @return array
     */
    public function authorize(array $fields): array
    {
        $paymentMethod = $fields['payment_method'] ?? 'creditcard';

        if ($paymentMethod === 'elv') {
            $fields['command'] = self::COMMAND_PREAUTHORIZATION;
            $fields['payment_options'] = self::PAYMENT_OPTIONS_ELV;
        } else {
            $fields['command'] = self::COMMAND_PREAUTHORIZATION;
            $fields['payment_options'] = self::PAYMENT_OPTIONS_CREDITCARD;
        }

        $this->logger->info('Authorizing Payplace payment', [
            'orderid' => $fields['orderid'] ?? 'unknown',
            'payment_method' => $paymentMethod
        ]);

        return $this->decodeResponse($this->doRequest($fields));
    }

    /**
     * Capture previously authorized payment
     *
     * @param array $fields
     * @return array
     */
    public function capture(array $fields): array
    {
        $paymentMethod = $fields['payment_method'] ?? 'creditcard';

        $fields['command'] = self::COMMAND_CAPTURE;
        $fields['payment_options'] = ($paymentMethod === 'elv') 
            ? self::PAYMENT_OPTIONS_ELV 
            : self::PAYMENT_OPTIONS_CREDITCARD;

        $this->logger->info('Capturing Payplace payment', [
            'orderid' => $fields['orderid'] ?? 'unknown',
            'trefnum' => $fields['trefnum'] ?? 'unknown'
        ]);

        return $this->decodeResponse($this->doRequest($fields));
    }

    /**
     * Cancel/reverse payment
     *
     * @param array $fields
     * @return array
     */
    public function cancel(array $fields): array
    {
        $paymentMethod = $fields['payment_method'] ?? 'creditcard';

        $fields['command'] = self::COMMAND_REVERSAL;
        $fields['payment_options'] = ($paymentMethod === 'elv') 
            ? self::PAYMENT_OPTIONS_ELV 
            : self::PAYMENT_OPTIONS_CREDITCARD;

        $this->logger->info('Cancelling Payplace payment', [
            'orderid' => $fields['orderid'] ?? 'unknown',
            'trefnum' => $fields['trefnum'] ?? 'unknown'
        ]);

        return $this->decodeResponse($this->doRequest($fields));
    }

    /**
     * Refund payment
     *
     * @param array $fields
     * @return array
     */
    public function refund(array $fields): array
    {
        $paymentMethod = $fields['payment_method'] ?? 'creditcard';

        $fields['command'] = self::COMMAND_REFUND;
        $fields['payment_options'] = ($paymentMethod === 'elv') 
            ? self::PAYMENT_OPTIONS_ELV 
            : self::PAYMENT_OPTIONS_CREDITCARD;

        $this->logger->info('Refunding Payplace payment', [
            'orderid' => $fields['orderid'] ?? 'unknown',
            'trefnum' => $fields['trefnum'] ?? 'unknown',
            'amount' => $fields['amount'] ?? 'unknown'
        ]);

        return $this->decodeResponse($this->doRequest($fields));
    }

    /**
     * Check if response indicates success
     *
     * @param array $response
     * @return bool
     */
    public function isSuccessResponse(array $response): bool
    {
        return isset($response[self::FIELD_POSHERR]) 
            && $response[self::FIELD_POSHERR] === self::POSHERR_SUCCESS
            && (!isset($response[self::FIELD_RC]) || $response[self::FIELD_RC] === self::STATUS_SUCCESS);
    }

    /**
     * Get error message from response
     *
     * @param array $response
     * @return string
     */
    public function getErrorMessage(array $response): string
    {
        return $response[self::FIELD_RMSG] ?? 'Unknown error occurred';
    }

    /**
     * Get configuration option
     *
     * @param string $key
     * @return mixed
     */
    public function getOption(string $key)
    {
        return $this->options[$key] ?? null;
    }

    /**
     * Execute HTTP request to Payplace API
     *
     * @param array $fields
     * @return string
     * @throws HttpException
     */
    protected function doRequest(array $fields): string
    {
        $headers = [
            'Content-Type' => 'application/x-www-form-urlencoded',
            'Authorization' => 'Basic ' . base64_encode($this->options['merchant_id'] . ':' . $this->options['password']),
        ];

        $body = http_build_query($fields, '', '&');

        $request = new Request('POST', $this->getApiEndpoint(), $headers, $body);

        try {
            $response = $this->client->send($request);
        } catch (\Exception $e) {
            $this->logger->error('Payplace API request failed', ['exception' => $e->getMessage()]);
            throw new HttpException('Request to Payplace API failed: ' . $e->getMessage(), 0, $e);
        }

        if ($response->getStatusCode() !== 200) {
            $this->logger->error('Payplace API returned non-200 status', [
                'status_code' => $response->getStatusCode(),
                'body' => $response->getBody()->getContents()
            ]);
            throw new HttpException('Payplace API returned status: ' . $response->getStatusCode());
        }

        $responseBody = $response->getBody()->getContents();
        
        $this->logger->debug('Payplace API response received', ['body_length' => strlen($responseBody)]);

        return $responseBody;
    }

    /**
     * Get API endpoint URL
     *
     * @return string
     */
    protected function getApiEndpoint(): string
    {
        return $this->options['sandbox'] 
            ? 'https://testsystem.payplace.de/web-api/Request.po'
            : 'https://system.payplace.de/web-api/Request.po';
    }

    /**
     * Decode Payplace response
     *
     * @param string $response
     * @return array
     */
    private function decodeResponse(string $response): array
    {
        parse_str($response, $decoded);
        
        // URL decode values
        foreach ($decoded as $key => $value) {
            $decoded[$key] = urldecode($value);
        }

        $this->logger->debug('Decoded Payplace response', ['response' => $this->sanitizeLogData($decoded)]);

        return $decoded;
    }

    /**
     * Sanitize data for logging (remove sensitive information)
     *
     * @param array $data
     * @return array
     */
    private function sanitizeLogData(array $data): array
    {
        $sensitiveFields = ['password', 'creditc', 'cvcode', 'iban', 'token'];
        $sanitized = $data;

        foreach ($sensitiveFields as $field) {
            if (isset($sanitized[$field])) {
                $sanitized[$field] = '***HIDDEN***';
            }
        }

        return $sanitized;
    }
} 