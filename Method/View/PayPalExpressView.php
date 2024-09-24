<?php

namespace Oro\Bundle\PayPalExpressBundle\Method\View;

use Oro\Bundle\PaymentBundle\Context\PaymentContextInterface;
use Oro\Bundle\PaymentBundle\Method\View\PaymentMethodViewInterface;
use Oro\Bundle\PayPalExpressBundle\Method\Config\PayPalExpressConfigInterface;

/**
 * View for PayPal Express payment method. Required by {@see \Oro\Bundle\PaymentBundle\OroPaymentBundle}.
 */
class PayPalExpressView implements PaymentMethodViewInterface
{
    /**
     * @var PayPalExpressConfigInterface
     */
    protected $config;

    public function __construct(PayPalExpressConfigInterface $config)
    {
        $this->config = $config;
    }

    #[\Override]
    public function getOptions(PaymentContextInterface $context)
    {
        return [];
    }

    #[\Override]
    public function getBlock()
    {
        return '_payment_methods_paypal_express_widget';
    }

    #[\Override]
    public function getLabel()
    {
        return $this->config->getLabel();
    }

    #[\Override]
    public function getShortLabel()
    {
        return $this->config->getShortLabel();
    }

    #[\Override]
    public function getAdminLabel()
    {
        return $this->config->getAdminLabel();
    }

    #[\Override]
    public function getPaymentMethodIdentifier()
    {
        return $this->config->getPaymentMethodIdentifier();
    }
}
