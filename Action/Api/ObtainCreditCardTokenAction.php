<?php

declare(strict_types=1);

namespace Orcaya\Payum\Payplace\Action\Api;

use Orcaya\Payum\Payplace\Request\Api\ObtainCreditCardToken;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\Reply\HttpResponse;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class ObtainCreditCardTokenAction extends BaseApiAwareAction implements LoggerAwareInterface
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    public function __construct()
    {
        parent::__construct();
        $this->logger = new NullLogger();
    }

    /**
     * @param ObtainCreditCardToken $request
     */
    public function execute($request): void
    {
        RequestNotSupportedException::assertSupports($this, $request);

        $model = ArrayObject::ensureArrayObject($request->getModel());
        $model['status'] = 'pending';
        $model['posherr'] = '0';

        $formServiceUrl = $this->getFormServiceUrlWithParameters($model);

        throw new HttpResponse($formServiceUrl);
    }

    public function supports($request): bool
    {
        return $request instanceof ObtainCreditCardToken && $request->getModel() instanceof \ArrayAccess;
    }

    protected function getFormServiceUrl(): string
    {
        return $this->getApi()->getOption('sandbox')
            ? 'https://testsystem.payplace.de/web-api/SSLPayment.po'
            : 'https://system.payplace.de/web-api/SSLPayment.po';
    }

    protected function getFormServiceUrlWithParameters($model): string
    {
        $hmacParamters = [
        ];

        $formServiceParameters = [
            'amount' => number_format($model['amount'] / 100, 2, ',', ''),
            'basketid' => $model['orderid'],
            'command' => 'sslform',
            'currency' => $model['currency'],
            'date' => date('Ymd_H:i:s'),
            'orderid' => $model['number'],
            'paymentmethod' => 'creditcard',
            'sessionid' => $model['clientSession'],
            'sslmerchant' => $this->getApi()->getOption('ssl_merchant_id'),
            'transactiontype' => 'preauthorization',
            'version' => '2.0',
            'locale' => 'de',
            'payment_options' => '3dsecure20;mobile',
            'tdsCustomerEmail' => $model['customer_email'],
            'tdsCustomerBillingAddress.city' => $model['city'],
            'tdsCustomerBillingAddress.country' => $model['country'],
            'tdsCustomerBillingAddress.line1' => $model['street'],
            'tdsCustomerBillingAddress.postCode' => $model['zip'],
            'tdsCustomerShippingAddress.city' => $model['city'],
            'tdsCustomerShippingAddress.country' => $model['country'],
            'tdsCustomerShippingAddress.line1' => $model['street'],
            'tdsCustomerShippingAddress.postCode' => $model['zip'],
            'notifyurl' => $this->getApi()->getOption('notify_url'),
        ];

        ksort($hmacParamters);
        ksort($formServiceParameters);

        $hmac = $this->getHmac($this->getApi()->getOption('ssl_password'), $formServiceParameters);

        $formServiceParameters = [
            ...$hmacParamters,
            ...$formServiceParameters
        ];
        $formServiceParameters['hmac1'] = $hmac;

        if ($this->getApi()->getOption('sandbox') == false) {
            $formServiceParameters['notifyurl'] = $this->getApi()->getOption('notify_url');
        }

        // Build URL with proper encoding
        $queryPairs = [];
        foreach ($formServiceParameters as $parameter => $parameterValue) {
            if ($parameter === 'hmac1') {
                // HMAC is already hex-encoded, don't encode again
                $queryPairs[] = $parameter . '=' . $parameterValue;
            } else {
                // Use standard urlencode for final URL building
                $queryPairs[] = $parameter . '=' . urlencode($parameterValue);
            }
        }

        $formServiceUrl = $this->getFormServiceUrl() . '?' . implode('&', $queryPairs);

        return $formServiceUrl;
    }

    protected function getHmac($key, $parameters): string
    {
        $message = http_build_query(data: $parameters, encoding_type: PHP_QUERY_RFC3986);
        $hmac = hash_hmac('sha256', $message, $key);

        return $hmac;
    }
}
