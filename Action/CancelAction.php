<?php

declare(strict_types=1);

namespace Orcaya\Payum\Payplace\Action;

use Payum\Core\Action\ActionInterface;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\GatewayAwareTrait;
use Payum\Core\Request\Cancel;
use Payum\Core\Action\GatewayAwareAction;

class CancelAction extends GatewayAwareAction
{
    use GatewayAwareTrait;

    /**
     * @param Cancel $request
     */
    public function execute($request): void
    {
        RequestNotSupportedException::assertSupports($this, $request);

        $model = ArrayObject::ensureArrayObject($request->getModel());

        if (false === isset($model['trefnum'])) {
            throw new \LogicException('Transaction reference number (trefnum) is required for cancellation.');
        }

        $this->gateway->execute(new \Orcaya\Payum\Payplace\Request\Api\Cancel($model));
        
        if ($this->isSuccessfulCancel($model)) {
            $model['cancelled'] = true;
        }
    }

    public function supports($request): bool
    {
        return $request instanceof Cancel && $request->getModel() instanceof \ArrayAccess;
    }

    private function isSuccessfulCancel(ArrayObject $model): bool
    {
        return isset($model['posherr']) && $model['posherr'] === '0';
    }
} 