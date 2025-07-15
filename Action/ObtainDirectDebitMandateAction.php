<?php

declare(strict_types=1);

namespace Orcaya\Payum\Payplace\Action;

use Orcaya\Payum\Payplace\Request\ObtainDirectDebitMandate;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\Action\GatewayAwareAction;
use Payum\Core\Reply\HttpResponse;

class ObtainDirectDebitMandateAction extends GatewayAwareAction
{
    public const MANDATE_PLACEHOLDERS = [
        '##firstname##',
        '##lastname##',
        '##street##',
        '##street_number##',
        '##zip##',
        '##city##'        
    ];

    public const MANDATE_TEXT = '<h3>SEPA-Lastschriftmandat</h3><br><p>Ich ermächtige den Zahlungsempfänger (<b>VfB Stuttgart 1893 AG</b>), 
    Zahlungen von meinem Konto mittels Lastschrift einzuziehen.<br><br><b>Hinweis:</b>Ich kann innerhalb von acht Wochen, beginnend mit dem Belastungsdatum, 
    die Erstattung des belasteten Betrages verlangen.<br>Es gelten dabei die mit meinem Kreditinstitut vereinbarten Bedingungen.<br><br>
    <b>Zahlungspflichtiger</b><br><br>
    ##firstname## ##lastname##<br>
    ##street## ##street_number##<br>
    ##zip## ##city##<br><br>
    Die Daten des zu belastenden Kontos werden im nächsten Schritt angegeben.';

    /**
     * @param ObtainDirectDebitToken $request
     */
    public function execute($request): mixed
    {
        RequestNotSupportedException::assertSupports($this, $request);

        $model = ArrayObject::ensureArrayObject($request->getModel());
        
        throw new HttpResponse([
            'mandate_text' => str_replace(
                    self::MANDATE_PLACEHOLDERS,
                    [
                        $model['firstname'],
                        $model['lastname'],
                        $model['street'],
                        $model['street_number'],
                        $model['zip'],
                        $model['city']
                    ],
                    self::MANDATE_TEXT
                )
            ]
        );
    }

    public function supports($request): bool
    {
        return $request instanceof ObtainDirectDebitMandate && $request->getModel() instanceof \ArrayAccess;
    }
} 