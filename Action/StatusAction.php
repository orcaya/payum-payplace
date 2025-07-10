<?php

declare(strict_types=1);

namespace Orcaya\Payum\Payplace\Action;

use Orcaya\Payum\Payplace\Api;
use Payum\Core\Action\ActionInterface;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\Request\GetStatusInterface;
use Payum\Core\Action\GatewayAwareAction;

class StatusAction extends GatewayAwareAction
{
    /**
     * @param GetStatusInterface $request
     */
    public function execute($request): void
    {
        RequestNotSupportedException::assertSupports($this, $request);

        $model = ArrayObject::ensureArrayObject($request->getModel());

        if (false === isset($model['posherr'])) {
            $request->markNew();
            return;
        }

        if ($model['posherr'] == Api::POSHERR_SUCCESS) {
            if (isset($model['status'])) {
                switch ($model['status']) {
                    case 'captured':
                        $request->markCaptured();
                        break;
                    case 'authorized':
                        if (isset($model['trefnum'])) {
                            $request->markAuthorized();
                        } else {
                            $request->markPending();
                        }
                        break;
                    case 'pending':
                        $request->markPending();
                        break;
                    case 'failed':
                        $request->markFailed();
                        break;
                    case 'refunded':
                        $request->markRefunded();
                        break;
                    case 'candeled':
                        $request->markCanceled();
                        break;
                    default:
                        $request->markFailed();
                        break;
                }
            }
        } else {
            $request->markFailed();
        }

        // Handle cancelled transactions
        if (isset($model['cancelled']) && $model['cancelled'] === true) {
            $request->markCanceled();
        }

        // Handle refunded transactions
        if (isset($model['refunded']) && $model['refunded'] === true) {
            $request->markRefunded();
        }
    }

    public function supports($request): bool
    {
        return $request instanceof GetStatusInterface && $request->getModel() instanceof \ArrayAccess;
    }
} 