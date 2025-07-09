<?php

declare(strict_types=1);

namespace Orcaya\Payum\Payplace\Action\Api;

use Orcaya\Payum\Payplace\Request\Api\Authorize;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class AuthorizeAction extends BaseApiAwareAction implements LoggerAwareInterface
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
     * @param Authorize $request
     */
    public function execute($request): void
    {
        RequestNotSupportedException::assertSupports($this, $request);

        $model = ArrayObject::ensureArrayObject($request->getModel());

        $fields = [
            'merchant_id' => $this->getApi()->getOption('merchant_id'),
            'orderid' => $model['orderid'],
            'amount' => $model['amount'],
            'currency' => $model['currency'] ?? 'EUR',
            'payment_method' => $model['payment_method'] ?? 'creditcard',
        ];

        // Use token for authorization
        if (!empty($model['token'])) {
            $fields['token'] = $model['token'];
        }

        // For directdebit payments, use PPAN
        if ($model['payment_method'] === 'directdebit' && !empty($model['ppan'])) {
            $fields['ppan'] = $model['ppan'];
        }
        
        $response = $this->getApi()->authorize($fields);
        $previousStatus = $model['status'] ?? 'new';

        // Handle error responses
        if (isset($response['posherr']) && $response['posherr'] !== '0') {
            $model['status'] = 'failed';
            $model['posherr'] = $response['posherr'];
            $model['rmsg'] = $response['rmsg'] ?? 'Unknown error';
            
            $this->logger->error(sprintf(
                'Payment %s authorization failed: %s (%s)',
                $model['orderid'] ?? 'unknown',
                $response['rmsg'] ?? 'Unknown error',
                $response['posherr']
            ));
            
            return;
        }
        
        // Handle successful responses
        if (isset($response['trefnum'])) {
            $model['status'] = 'authorized';
            $model['trefnum'] = $response['trefnum'];
            
            $this->logger->info(sprintf(
                'Payment %s authorized successfully. Transaction reference: %s',
                $model['orderid'] ?? 'unknown',
                $response['trefnum']
            ));
        }

        // Store all response data in model (automatic persistence by Payum)
        $this->mapResponseToModel($model, $response);
        
        $this->logger->info(sprintf(
            'Payment %s status changed from "%s" to "%s"',
            $model['orderid'] ?? 'unknown',
            $previousStatus,
            $model['status']
        ));
    }

    /**
     * Maps Payplace response to model fields
     */
    protected function mapResponseToModel(ArrayObject $model, array $response): void
    {
        // Basic fields
        $responseFields = [
            'trefnum' => 'trefnum',
            'status' => 'status',
            'rmsg' => 'rmsg',
            'posherr' => 'posherr',
            'amount' => 'amount',
            'currency' => 'currency',
            'payment_method' => 'payment_method',
            'orderid' => 'orderid',
        ];

        foreach ($responseFields as $responseKey => $modelKey) {
            if (isset($response[$responseKey])) {
                $model[$modelKey] = $response[$responseKey];
            }
        }

        // Payment method specific fields
        $paymentMethod = $response['payment_method'] ?? $model['payment_method'] ?? 'creditcard';
        
        if ($paymentMethod === 'directdebit') {
            $directdebitFields = [
                'ppan' => 'ppan',
                'iban_masked' => 'iban_masked',
                'bic' => 'bic',
                'account_holder' => 'account_holder',
            ];
            
            foreach ($directdebitFields as $responseKey => $modelKey) {
                if (isset($response[$responseKey])) {
                    $model[$modelKey] = $response[$responseKey];
                }
            }
        } else {
            $cardFields = [
                'card_brand' => 'card_brand',
                'card_number_masked' => 'card_number_masked',
                'card_holder' => 'card_holder',
            ];
            
            foreach ($cardFields as $responseKey => $modelKey) {
                if (isset($response[$responseKey])) {
                    $model[$modelKey] = $response[$responseKey];
                }
            }
        }

        // Set timestamp
        $model['authorized_at'] = date('Y-m-d H:i:s');
    }

    public function supports($request): bool
    {
        return $request instanceof Authorize && $request->getModel() instanceof \ArrayAccess;
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }
} 