<?php

namespace Oro\Bundle\PayPalExpressBundle\Method;

use Oro\Bundle\PaymentBundle\Context\PaymentContextInterface;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Oro\Bundle\PayPalExpressBundle\Method\Config\PayPalExpressConfigInterface;
use Oro\Bundle\PayPalExpressBundle\Method\PaymentAction\PaymentActionExecutor;

class PayPalMethod implements PaymentMethodInterface
{
    /**
     * @var PayPalExpressConfigInterface
     */
    protected $config;

    /**
     * @var PaymentActionExecutor
     */
    protected $paymentActionExecutor;

    /**
     * @param PayPalExpressConfigInterface $config
     * @param PaymentActionExecutor        $paymentActionExecutor
     */
    public function __construct(
        PayPalExpressConfigInterface $config,
        PaymentActionExecutor $paymentActionExecutor
    ) {
        $this->config = $config;
        $this->paymentActionExecutor = $paymentActionExecutor;
    }

    /**
     * {@inheritdoc}
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
     * {@inheritdoc}
     */
    public function supports($actionName)
    {
        return $this->paymentActionExecutor->isActionSupported($actionName);
    }
}
