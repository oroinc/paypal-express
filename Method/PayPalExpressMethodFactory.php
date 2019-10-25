<?php

namespace Oro\Bundle\PayPalExpressBundle\Method;

use Oro\Bundle\PayPalExpressBundle\Method\Config\PayPalExpressConfigInterface;
use Oro\Bundle\PayPalExpressBundle\Method\PaymentAction\PaymentActionExecutor;
use Oro\Bundle\PayPalExpressBundle\Transport\SupportedCurrenciesHelper;

/**
 * {@inheritdoc}
 */
class PayPalExpressMethodFactory implements PayPalExpressMethodFactoryInterface
{
    /**
     * @var PaymentActionExecutor
     */
    protected $payPalActionExecutor;

    /**
     * @var SupportedCurrenciesHelper
     */
    protected $supportedCurrenciesHelper;

    /**
     * @param PaymentActionExecutor $payPalActionExecutor
     * @param SupportedCurrenciesHelper $supportedCurrenciesHelper
     */
    public function __construct(
        PaymentActionExecutor $payPalActionExecutor,
        SupportedCurrenciesHelper $supportedCurrenciesHelper
    ) {
        $this->payPalActionExecutor = $payPalActionExecutor;
        $this->supportedCurrenciesHelper = $supportedCurrenciesHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function create(PayPalExpressConfigInterface $config)
    {
        return new PayPalExpressMethod($config, $this->payPalActionExecutor, $this->supportedCurrenciesHelper);
    }
}
