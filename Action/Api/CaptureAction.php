<?php

declare(strict_types=1);

namespace Orcaya\Payum\Payplace\Action\Api;

use Orcaya\Payum\Payplace\Request\Api\Capture;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class CaptureAction extends BaseApiAwareAction implements LoggerAwareInterface
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
     * @param Capture $request
     */
    public function execute($request): void
    {
        RequestNotSupportedException::assertSupports($this, $request);

        $model = ArrayObject::ensureArrayObject($request->getModel());
        $previousStatus = $model['status'] ?? 'unknown';

        $fields = [
            'merchant_id' => $this->getApi()->getOption('merchant_id'),
            'orderid' => $model['number'],
            'trefnum' => $model['payplace_trefnum'] ?? $model['trefnum'],
            'payment_method' => $model['payment_method'] ?? 'creditcard',
            'currency' => empty($model['currency']) ? 'EUR' : $model['currency'],
        ];

        if (!empty($model['ppan'])) {
            $fields['ppan'] = $model['ppan'];
        }

        // Optional amount for partial captures
        if (!empty($model['capture_amount'])) {
            $fields['amount'] = $model['capture_amount'];
        }

        $response = $this->getApi()->capture($fields);
        
        // Handle error responses
        if (isset($response['posherr']) && $response['posherr'] != '0') {
            $model['status'] = 'failed';
            $model['posherr'] = $response['posherr'];
            $model['rc'] = $response['rc'];
            $model['rmsg'] = $response['rmsg'] ?? 'Unknown error';
            
            $this->logger->error(sprintf(
                'Payment %s capture failed: %s (%s)',
                $model['orderid'] ?? 'unknown',
                $response['rmsg'] ?? 'Unknown error',
                $response['posherr']
            ));
            
            return;
        }
        
        // Handle successful responses
        if (isset($response['posherr']) && $response['posherr'] == '0') {
            $model['status'] = 'captured';
            $model['capture_status'] = 'captured';
            $model['rc'] = $response['rc'] ?? '000';
            $model['captured_at'] = date('Y-m-d H:i:s');
            $model['captured'] = true;
            $model['posherr'] = $response['posherr'];
            $model['rmsg'] = $response['rmsg'];
            
            $this->logger->info(sprintf(
                'Payment %s captured successfully. Amount: %s',
                $model['orderid'] ?? 'unknown',
                $response['captured_amount'] ?? 'full'
            ));
        }
        
        $this->logger->info(sprintf(
            'Payment %s capture status changed from "%s" to "%s"',
            $model['orderid'] ?? 'unknown',
            $previousStatus,
            $model['status']
        ));
    }

    public function supports($request): bool
    {
        return $request instanceof Capture && $request->getModel() instanceof \ArrayAccess;
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }
} 