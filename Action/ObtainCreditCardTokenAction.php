<?php

declare(strict_types=1);

namespace Orcaya\Payum\Payplace\Action;

use Orcaya\Payum\Payplace\Request\ObtainCreditCardToken;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\Action\GatewayAwareAction;

class ObtainCreditCardTokenAction extends GatewayAwareAction
{
    /**
     * @param ObtainCreditCardToken $request
     */
    public function execute($request): void
    {
        RequestNotSupportedException::assertSupports($this, $request);

        $model = ArrayObject::ensureArrayObject($request->getModel());
        
        $this->gateway->execute(new \Orcaya\Payum\Payplace\Request\Api\ObtainCreditCardToken($model));
        return;
    }

    public function supports($request): bool
    {
        return $request instanceof ObtainCreditCardToken && $request->getModel() instanceof \ArrayAccess;
    }
} 