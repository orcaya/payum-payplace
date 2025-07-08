<?php

declare(strict_types=1);

namespace Orcaya\Payum\Payplace\Action;

use Orcaya\Payum\Payplace\Request\Api\InitializeIframe;
use Orcaya\Payum\Payplace\Request\ObtainCreditCardToken;
use Orcaya\Payum\Payplace\Request\ObtainDirectDebitToken;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\Request\Authorize;
use Payum\Core\Request\GetHttpRequest;
use Payum\Core\Security\GenericTokenFactoryAwareInterface;
use Orcaya\Payum\Payplace\Api;
use Payum\Core\Action\GatewayAwareAction;
use Payum\Core\Security\GenericTokenFactoryAwareTrait;

class AuthorizeAction extends GatewayAwareAction implements GenericTokenFactoryAwareInterface
{
    use GenericTokenFactoryAwareTrait;

    /**
     * @param Authorize $request
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
        
        if (isset($httpRequest->query['canceled'])) {
            $model[Api::FIELD_STATUS] = 'canceled';

            return;
        }
        
        // Step 1: Check if we have a token from Payplace iframe
        if (!empty($model['token'])) {
            // Now authorize with the token
            $this->gateway->execute(new \Orcaya\Payum\Payplace\Request\Api\Authorize($model));
            return;
        }

        // Step 2: If no iframe session exists, create one
        if (false === isset($model['clientSession']) && $model['payment_method'] == 'elv') {
            $this->gateway->execute(new InitializeIframe($model));
        } else {
            $model['clientSession'] = $model['number'];
        }
        
        // Step 3: Show iframe form to collect payment data
        if (isset($model['clientSession']) && !isset($model['token'])) {
            // Determine payment method and use appropriate token action
            $paymentMethod = $model['payment_method'] ?? 'creditcard';
            
            if ($paymentMethod === 'elv') {
                $this->gateway->execute(new ObtainDirectDebitToken($model));
            } else {
                $this->gateway->execute(new ObtainCreditCardToken($model));
            }
        }
    }

    public function supports($request): bool
    {
        return $request instanceof Authorize && $request->getModel() instanceof \ArrayAccess;
    }
} 