<?php

declare(strict_types=1);

namespace Orcaya\Payum\Payplace\Action\Api;

use Orcaya\Payum\Payplace\Request\Api\Cancel;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;

class CancelAction extends BaseApiAwareAction
{
    /**
     * @param Cancel $request
     */
    public function execute($request): void
    {
        RequestNotSupportedException::assertSupports($this, $request);

        $model = ArrayObject::ensureArrayObject($request->getModel());

        $fields = [
            'merchant_id' => $this->getApi()->getOption('merchant_id'),
            'orderid' => $model['orderid'],
            'trefnum' => $model['trefnum'],
            'payment_method' => $model['payment_method'] ?? 'creditcard',
        ];

        $response = $this->getApi()->cancel($fields);

        $model['status'] = 'canceled';
        $model['canceled'] = true;

        $model->replace(array_merge($model->getArrayCopy(), $response));
    }

    public function supports($request): bool
    {
        return $request instanceof Cancel && $request->getModel() instanceof \ArrayAccess;
    }
} 