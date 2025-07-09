<?php

declare(strict_types=1);

namespace Orcaya\Payum\Payplace;

use Orcaya\Payum\Payplace\Action\Api\AuthorizeAction as ApiAuthorizeAction;
use Orcaya\Payum\Payplace\Action\Api\CancelAction as ApiCancelAction;
use Orcaya\Payum\Payplace\Action\Api\CaptureAction as ApiCaptureAction;
use Orcaya\Payum\Payplace\Action\Api\RefundAction as ApiRefundAction;
use Orcaya\Payum\Payplace\Action\Api\ObtainCreditCardTokenAction as ApiObtainCreditCardTokenAction;
use Orcaya\Payum\Payplace\Action\Api\ObtainDirectDebitTokenAction as ApiObtainDirectDebitTokenAction;
use Orcaya\Payum\Payplace\Action\AuthorizeAction;
use Orcaya\Payum\Payplace\Action\CancelAction;
use Orcaya\Payum\Payplace\Action\CaptureAction;
use Orcaya\Payum\Payplace\Action\ConvertPaymentAction;
use Orcaya\Payum\Payplace\Action\NotifyAction;
use Orcaya\Payum\Payplace\Action\ObtainCreditCardTokenAction;
use Orcaya\Payum\Payplace\Action\ObtainDirectDebitTokenAction;
use Orcaya\Payum\Payplace\Action\RefundAction;
use Orcaya\Payum\Payplace\Action\StatusAction;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\GatewayFactory;

class PayplaceGatewayFactory extends GatewayFactory
{
    protected function populateConfig(ArrayObject $config): void
    {
        $config->defaults([
            'payum.factory_name' => 'payplace',
            'payum.factory_title' => 'Payplace',
            'payum.action.capture' => new CaptureAction(),
            'payum.action.authorize' => new AuthorizeAction(),
            'payum.action.refund' => new RefundAction(),
            'payum.action.cancel' => new CancelAction(),
            'payum.action.notify' => new NotifyAction(),
            'payum.action.status' => new StatusAction(),
            'payum.action.convert_payment' => new ConvertPaymentAction(),
            'payum.action.obtain_credit_card_token' => new ObtainCreditCardTokenAction(),
            'payum.action.obtain_direct_debit_token' => new ObtainDirectDebitTokenAction(),

            'payum.action.api.authorize' => new ApiAuthorizeAction(),
            'payum.action.api.capture' => new ApiCaptureAction(),
            'payum.action.api.refund' => new ApiRefundAction(),
            'payum.action.api.cancel' => new ApiCancelAction(),
            'payum.action.api.obtain_credit_card_token' => new ApiObtainCreditCardTokenAction(),
            'payum.action.api.obtain_direct_debit_token' => new ApiObtainDirectDebitTokenAction(),
        ]);

        if (false == $config['payum.api']) {
            $config['payum.default_options'] = [
                'merchant_id' => '',
                'password' => '',
                'sandbox' => true,
                'ssl_merchant_id' => '',
                'ssl_password' => '',
                'use_3dsecure' => true,
                'iframe_width' => '100%',
                'iframe_height' => '500px',
                'notify_url' => '',
            ];

            $config->defaults($config['payum.default_options']);
            $config['payum.required_options'] = ['merchant_id', 'password', 'ssl_merchant_id', 'ssl_password', 'notify_url'];

            $config['payum.api'] = function (ArrayObject $config) {
                $config->validateNotEmpty($config['payum.required_options']);

                $api = new Api(
                    (array) $config,
                    $config['payum.http_client']
                );

                return $api;
            };
        }
    }
} 