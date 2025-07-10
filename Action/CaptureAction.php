<?php

declare(strict_types=1);

namespace Orcaya\Payum\Payplace\Action;

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

        if (
            $model['posherr'] == Api::POSHERR_SUCCESS && 
            isset($model['trefnum'])
        ) {           
            $this->gateway->execute(new \Orcaya\Payum\Payplace\Request\Api\Capture($model));
            return;
        }

        if (isset($model['clientSession']) && $model['status'] != 'authorized') {
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