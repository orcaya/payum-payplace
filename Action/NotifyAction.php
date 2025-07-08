<?php

declare(strict_types=1);

namespace Orcaya\Payum\Payplace\Action;

use Payum\Core\Action\ActionInterface;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\Request\GetHttpRequest;
use Payum\Core\Request\Notify;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\GatewayAwareTrait;
use Payum\Core\Action\GatewayAwareAction;

class NotifyAction extends GatewayAwareAction
{
    use GatewayAwareTrait;

    /**
     * @param Notify $request
     */
    public function execute($request): void
    {
        RequestNotSupportedException::assertSupports($this, $request);

        $model = ArrayObject::ensureArrayObject($request->getModel());

        $httpRequest = new GetHttpRequest();
        $this->gateway->execute($httpRequest);

        // Update model with notification data
        if (!empty($httpRequest->request)) {
            foreach ($httpRequest->request as $key => $value) {
                $model[$key] = $value;
            }
        }

        // Mark payment as captured if notification indicates success
        if (isset($model['posherr']) && $model['posherr'] === '0' && isset($model['trefnum'])) {
            $model['captured'] = true;
        }
    }

    public function supports($request): bool
    {
        return $request instanceof Notify && $request->getModel() instanceof \ArrayAccess;
    }
} 