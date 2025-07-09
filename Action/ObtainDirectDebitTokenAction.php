<?php

declare(strict_types=1);

namespace Orcaya\Payum\Payplace\Action;

use Orcaya\Payum\Payplace\Request\ObtainDirectDebitToken;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\Action\GatewayAwareAction;

class ObtainDirectDebitTokenAction extends GatewayAwareAction
{
    /**
     * @param ObtainDirectDebitToken $request
     */
    public function execute($request): void
    {
        RequestNotSupportedException::assertSupports($this, $request);

        $model = ArrayObject::ensureArrayObject($request->getModel());
        
        $this->gateway->execute(new \Orcaya\Payum\Payplace\Request\Api\ObtainDirectDebitToken($model));
        return;
    }

    public function supports($request): bool
    {
        return $request instanceof ObtainDirectDebitToken && $request->getModel() instanceof \ArrayAccess;
    }
} 