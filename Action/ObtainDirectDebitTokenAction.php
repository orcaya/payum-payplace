<?php

declare(strict_types=1);

namespace Orcaya\Payum\Payplace\Action;

use Orcaya\Payum\Payplace\Request\ObtainDirectDebitToken;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\GatewayAwareTrait;
use Payum\Core\Reply\HttpResponse;
use Payum\Core\Request\GetHttpRequest;
use Payum\Core\Request\RenderTemplate;
use Payum\Core\Action\GatewayAwareAction;

class ObtainDirectDebitTokenAction extends GatewayAwareAction
{
    use GatewayAwareTrait;

    /**
     * @var string
     */
    protected $templateName;

    /**
     * @param string $templateName
     */
    public function __construct(string $templateName)
    {
        $this->templateName = $templateName;
    }

    /**
     * @param ObtainDirectDebitToken $request
     */
    public function execute($request): void
    {
        RequestNotSupportedException::assertSupports($this, $request);

        $model = ArrayObject::ensureArrayObject($request->getModel());

        $httpRequest = new GetHttpRequest();
        $this->gateway->execute($httpRequest);

        // Check if we already have a token from the form submission
        if (!empty($httpRequest->request['token'])) {
            $model['token'] = $httpRequest->request['token'];
            $model['payment_method'] = 'elv';
            $model['payment_type'] = $httpRequest->request['payment_type'] ?? 'bankAccount';
            
            // For ELV payments, also get PPAN
            if (!empty($httpRequest->request['ppan'])) {
                $model['ppan'] = $httpRequest->request['ppan'];
            }
            
            return;
        }

        // Ensure the payment method is set correctly
        $model['payment_method'] = 'elv';

        $renderTemplate = new RenderTemplate($this->templateName, [
            'model' => $model,
            'actionUrl' => $httpRequest->uri,
        ]);

        $this->gateway->execute($renderTemplate);

        throw new HttpResponse($renderTemplate->getResult());
    }

    public function supports($request): bool
    {
        return $request instanceof ObtainDirectDebitToken && $request->getModel() instanceof \ArrayAccess;
    }
} 