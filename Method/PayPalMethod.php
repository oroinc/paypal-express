<?php

namespace Oro\Bundle\PayPalExpressBundle\Method;

use Oro\Bundle\PaymentBundle\Context\PaymentContextInterface;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Oro\Bundle\PayPalBundle\Method\Config\PayPalConfigInterface;
use Oro\Bundle\PayPalExpressBundle\Method\Config\PayPalExpressConfigInterface;
use Oro\Bundle\PayPalExpressBundle\Method\PaymentAction\PaymentActionExecutor;

class PayPalMethod implements PaymentMethodInterface
{
    /**
     * @var PayPalTransportFacadeInterface
     */
    protected $payPalTransportFacade;

    /**
     * @var PayPalConfigInterface
     */
    protected $config;

    /**
     * @var PaymentActionExecutor
     */
    protected $paymentActionExecutor;

    /**
     * @param PayPalTransportFacadeInterface $payPalTransportFacade
     * @param PayPalExpressConfigInterface   $config
     * @param PaymentActionExecutor          $paymentActionExecutor
     */
    public function __construct(
        PayPalTransportFacadeInterface $payPalTransportFacade,
        PayPalExpressConfigInterface $config,
        PaymentActionExecutor $paymentActionExecutor
    ) {
        $this->payPalTransportFacade = $payPalTransportFacade;
        $this->config                = $config;
        $this->paymentActionExecutor = $paymentActionExecutor;
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Oro\Bundle\PayPalExpressBundle\Exception\ExceptionInterface
     */
    public function execute($action, PaymentTransaction $paymentTransaction)
    {
        return $this->paymentActionExecutor->executeAction($action, $paymentTransaction, $this->config);
    }

    /**
     * {@inheritdoc}
     */
    public function getIdentifier()
    {
        return $this->config->getPaymentMethodIdentifier();
    }

    /**
     * {@inheritdoc}
     */
    public function isApplicable(PaymentContextInterface $context)
    {
        return true;
    }

    /**
     * @param string $actionName
     *
     * @return bool
     */
    public function supports($actionName)
    {
        return $this->paymentActionExecutor->isActionSupported($actionName);
    }
}
