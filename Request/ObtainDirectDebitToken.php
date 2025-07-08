<?php

declare(strict_types=1);

namespace Orcaya\Payum\Payplace\Request;

use Payum\Core\Request\Generic;

class ObtainDirectDebitToken extends Generic
{
    /**
     * @var string
     */
    private $paymentMethod = 'elv';

    /**
     * Get the payment method for this request
     *
     * @return string
     */
    public function getPaymentMethod(): string
    {
        return $this->paymentMethod;
    }
} 