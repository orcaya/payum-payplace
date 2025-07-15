<?php

declare(strict_types=1);

namespace Orcaya\Payum\Payplace\Action;

use Orcaya\Payum\Payplace\Request\ObtainCreditCardToken;
use Orcaya\Payum\Payplace\Request\ObtainDirectDebitToken;
use Orcaya\Payum\Payplace\Request\ObtainDirectDebitMandate;
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
        $paymentMethod = $model['payment_method'] ?? 'creditcard';
        
        $model['successurl'] = $request->getToken()->getAfterUrl();
        $model['errorurl'] = $request->getToken()->getTargetUrl() . '?canceled=1';
        $model['backurl'] = $request->getToken()->getTargetUrl() . '?canceled=1';
        
        $httpRequest = new GetHttpRequest();
        $this->gateway->execute($httpRequest);
        
        if (isset($httpRequest->query['canceled'])) {
            $model[Api::FIELD_STATUS] = 'canceled';

            return;
        }
        
        $model['clientSession'] = $model['number'];
        
        if ($paymentMethod == 'directdebit') {
            if(isset($model['mandate_accepted']) && $model['mandate_accepted'] == '1') {
                $this->gateway->execute(new ObtainDirectDebitToken($model));
            } else {
                $this->gateway->execute(new ObtainDirectDebitMandate($model));
            }
            
        } else {
            $this->gateway->execute(new ObtainCreditCardToken($model));
        }
    }

    public function supports($request): bool
    {
        return $request instanceof Authorize && $request->getModel() instanceof \ArrayAccess;
    }
} 