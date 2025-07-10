<?php

declare(strict_types=1);

namespace Orcaya\Payum\Payplace\Action;

use Payum\Core\Action\ActionInterface;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\GatewayAwareTrait;
use Payum\Core\Request\Refund;
use Payum\Core\Action\GatewayAwareAction;

class RefundAction extends GatewayAwareAction
{
    use GatewayAwareTrait;

    /**
     * @param Refund $request
     */
    public function execute($request): void
    {
        RequestNotSupportedException::assertSupports($this, $request);

        $model = ArrayObject::ensureArrayObject($request->getModel());

        if (false === isset($model['trefnum'])) {
            throw new \LogicException('Transaction reference number (trefnum) is required for refund.');
        }

        $this->gateway->execute(new \Orcaya\Payum\Payplace\Request\Api\Refund($model));
    }

    public function supports($request): bool
    {
        return $request instanceof Refund && $request->getModel() instanceof \ArrayAccess;
    }
} 