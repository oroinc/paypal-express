<?php

namespace Oro\Bundle\PayPalExpressBundle\Method;

use Oro\Bundle\PaymentBundle\Context\PaymentContextInterface;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Oro\Bundle\PayPalExpressBundle\Method\Config\PayPalExpressConfigInterface;
use Oro\Bundle\PayPalExpressBundle\Method\PaymentAction\PaymentActionExecutor;
use Oro\Bundle\PayPalExpressBundle\Transport\SupportedCurrenciesHelper;

/**
 * Entry point of PayPal Express Payment Method which provides implicit interface for PayPal Express Payment Actions.
 * Required by {@see \Oro\Bundle\PaymentBundle\OroPaymentBundle}
 */
class PayPalExpressMethod implements PaymentMethodInterface
{
    private const AMOUNT_PRECISION = 2;

    /**
     * @var PayPalExpressConfigInterface
     */
    protected $config;

    /**
     * @var PaymentActionExecutor
     */
    protected $paymentActionExecutor;

    /**
     * @var SupportedCurrenciesHelper
     */
    protected $supportedCurrenciesHelper;

    /**
     * @param PayPalExpressConfigInterface $config
     * @param PaymentActionExecutor        $paymentActionExecutor
     * @param SupportedCurrenciesHelper    $supportedCurrenciesHelper
     */
    public function __construct(
        PayPalExpressConfigInterface $config,
        PaymentActionExecutor $paymentActionExecutor,
        SupportedCurrenciesHelper $supportedCurrenciesHelper
    ) {
        $this->config = $config;
        $this->paymentActionExecutor = $paymentActionExecutor;
        $this->supportedCurrenciesHelper = $supportedCurrenciesHelper;
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
        $isApplicable = $this->supportedCurrenciesHelper->isSupportedCurrency($context->getCurrency());
        if (!$isApplicable) {
            return false;
        }

        if (!method_exists($context, 'getTotal')) {
            return true;
        }
        $amount = round($context->getTotal(), self::AMOUNT_PRECISION);
        $zeroAmount = round(0, self::AMOUNT_PRECISION);

        return !($amount === $zeroAmount);
    }

    /**
     * {@inheritdoc}
     */
    public function supports($actionName)
    {
        return $this->paymentActionExecutor->isActionSupported($actionName);
    }
}
