<?php

declare(strict_types=1);

namespace Orcaya\Payum\Payplace\Action\Api;

use Orcaya\Payum\Payplace\Request\Api\Refund;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;

class RefundAction extends BaseApiAwareAction
{
    /**
     * @param Refund $request
     */
    public function execute($request): void
    {
        RequestNotSupportedException::assertSupports($this, $request);

        $model = ArrayObject::ensureArrayObject($request->getModel());

        $fields = [
            'merchant_id' => $this->getApi()->getOption('merchant_id'),
            'orderid' => $model['orderid'],
            'trefnum' => $model['trefnum'],
            'amount' => $model['refund_amount'] ?? $model['amount'],
            'payment_method' => $model['payment_method'] ?? 'creditcard',
            'currency' => empty($model['currency']) ? 'EUR' : $model['currency'],
        ];

        if (!empty($model['ppan'])) {
            $fields['ppan'] = $model['ppan'];
        }

        $response = $this->getApi()->refund($fields);

        if ($response['posherr'] == '0') {
            $model['posherr'] = $response['posherr'];
            $model['rc'] = $response['rc'];
            $model['rmsg'] = $response['rmsg'];
            $model['refunded'] = true;
            $model['status'] = 'refunded';
        } {

        }

        

        $model->replace(array_merge($model->getArrayCopy(), $response));
    }

    public function supports($request): bool
    {
        return $request instanceof Refund && $request->getModel() instanceof \ArrayAccess;
    }
} 