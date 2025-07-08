<?php

declare(strict_types=1);

namespace Orcaya\Payum\Payplace\Action\Api;

use Orcaya\Payum\Payplace\Request\Api\InitializeIframe;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\Request\Generic;

class InitializeIframeAction extends BaseApiAwareAction
{
    /**
     * @param InitializeIframe $request
     */
    public function execute($request): void
    {
        RequestNotSupportedException::assertSupports($this, $request);

        $model = ArrayObject::ensureArrayObject($request->getModel());

        $fields = [
            'merchant_id' => $this->getApi()->getOption('merchant_id'),
            'orderid' => $model['orderid'],
            'amount' => $model['amount'],
            'currency' => $model['currency'] ?? 'EUR',
            'payment_method' => $model['payment_method'] ?? 'creditcard',
            'successurl' => $model['successurl'],
            'errorurl' => $model['errorurl'],
            'backurl' => $model['backurl'],
            'notificationurl' => $model['notificationurl'],
        ];

        // Add optional customer data
        if (!empty($model['customer_email'])) {
            $fields['customer'] = $model['customer_email'];
        }
        
        // Add payment method specific options
        if ($model['payment_method'] == 'creditcard' && $this->getApi()->getOption('use_3dsecure')) {
            $fields['payment_options'] = $this->getApi()::PAYMENT_OPTIONS_3DSECURE20 . ';' . $this->getApi()::PAYMENT_OPTIONS_INIT_IFRAME;
        } else {
            $fields['payment_options'] = $this->getApi()::PAYMENT_OPTIONS_INIT_IFRAME;
        }
        
        $response = $this->getApi()->initializeIframe($fields);
        
        $model['clientSession'] = $response['clientSession'];
        $model['clientConfiguration'] = $response['clientConfiguration'];
    }

    public function supports($request): bool
    {
        return $request instanceof InitializeIframe && $request->getModel() instanceof \ArrayAccess;
    }
} 