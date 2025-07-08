<?php

declare(strict_types=1);

namespace Orcaya\Payum\Payplace\Action;

use Payum\Core\Action\ActionInterface;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\Model\PaymentInterface;
use Payum\Core\Request\Convert;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Action\GatewayAwareAction;

class ConvertPaymentAction extends GatewayAwareAction
{
    /**
     * @param Convert $request
     */
    public function execute($request): void
    {
        RequestNotSupportedException::assertSupports($this, $request);

        /** @var PaymentInterface $payment */
        $payment = $request->getSource();
        
        $paymentDetails = ArrayObject::ensureArrayObject($payment->getDetails());
        $additionalDetails = [
            'payment_method' => $paymentDetails['payment_method'],
            'gateway' => $paymentDetails['gateway'],
            'orderid' => $paymentDetails['orderid'], 
            'reference' => $payment->getNumber(),
            'amount' => $payment->getTotalAmount() * 100,
            'currency' => $payment->getCurrencyCode(),
            'customer_email' => $payment->getClientEmail(),
            'description' => $payment->getDescription(),
        ];
        $details = array_merge($additionalDetails, $paymentDetails->toUnsafeArray());

        $request->setResult((array)$details);
    }

    public function supports($request)
    {
        return
            $request instanceof Convert &&
            $request->getSource() instanceof PaymentInterface &&
            $request->getTo() == 'array';
    }
} 