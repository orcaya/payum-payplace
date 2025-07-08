<?php

declare(strict_types=1);

namespace Orcaya\Payum\Payplace\Action\Api;

use Orcaya\Payum\Payplace\Api;
use Payum\Core\Action\ActionInterface;
use Payum\Core\ApiAwareInterface;
use Payum\Core\ApiAwareTrait;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\GatewayAwareTrait;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

abstract class BaseApiAwareAction implements ActionInterface, ApiAwareInterface, GatewayAwareInterface, LoggerAwareInterface
{
    use ApiAwareTrait;
    use GatewayAwareTrait;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    public function __construct()
    {
        $this->apiClass = Api::class;
        $this->logger = new NullLogger();
    }

    /**
     * @return Api
     */
    protected function getApi(): Api
    {
        return $this->api;
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }
} 