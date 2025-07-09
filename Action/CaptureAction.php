<?php

declare(strict_types=1);

namespace Orcaya\Payum\Payplace\Action;

use Orcaya\Payum\Payplace\Request\Api\InitializeIframe;
use Orcaya\Payum\Payplace\Request\ObtainCreditCardToken;
use Orcaya\Payum\Payplace\Request\ObtainDirectDebitToken;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\GatewayAwareTrait;
use Payum\Core\Request\Capture;
use Payum\Core\Request\GetHttpRequest;
use Payum\Core\Security\GenericTokenFactoryAwareInterface;
use Payum\Core\Security\GenericTokenFactoryAwareTrait;
use Payum\Core\Action\GatewayAwareAction;
use Orcaya\Payum\Payplace\Api;

class CaptureAction extends GatewayAwareAction implements GenericTokenFactoryAwareInterface
{
    use GatewayAwareTrait;
    use GenericTokenFactoryAwareTrait;

    /**
     * @param Capture $request
     */
    public function execute($request): void
    {
        RequestNotSupportedException::assertSupports($this, $request);

        $model = ArrayObject::ensureArrayObject($request->getModel());

        $model['successurl'] = $request->getToken()->getAfterUrl();
        $model['errorurl'] = $request->getToken()->getTargetUrl() . '?canceled=1';
        $model['backurl'] = $request->getToken()->getTargetUrl() . '?canceled=1';

        $httpRequest = new GetHttpRequest();
        $this->gateway->execute($httpRequest);

        // Step 1: Check if we have a token from Payplace iframe
        if (!empty($model['token'])) {
            // Now authorize with the token
            $this->gateway->execute(new \Orcaya\Payum\Payplace\Request\Api\Authorize($model));
            
            if (isset($model['trefnum'])) {
                // Authorization successful, now capture
                $this->gateway->execute(new \Orcaya\Payum\Payplace\Request\Api\Capture($model));
                $model['captured'] = true;
            }
            return;
        }

        // Step 2: If no iframe session exists, create one
        if (false === isset($model['clientSession'])) {
            $this->gateway->execute(new InitializeIframe($model));
        }

        // Step 3: Show iframe form to collect payment data
        if (isset($model['clientSession']) && !isset($model['token'])) {
            // Determine payment method and use appropriate token action
            $paymentMethod = $model['payment_method'] ?? 'creditcard';
            
            if ($paymentMethod === 'directdebit') {
                $this->gateway->execute(new ObtainDirectDebitToken($model));
            } else {
                $this->gateway->execute(new ObtainCreditCardToken($model));
            }
        }
    }

    public function supports($request): bool
    {
        return $request instanceof Capture && $request->getModel() instanceof \ArrayAccess;
    }
} 