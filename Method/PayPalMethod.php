<?php

namespace Oro\Bundle\PayPalExpressBundle\Method;

use Oro\Bundle\PaymentBundle\Context\PaymentContextInterface;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Oro\Bundle\PayPalBundle\Method\Config\PayPalConfigInterface;
use Oro\Bundle\PayPalExpressBundle\Exception\RuntimeException;
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
        if (!$paymentTransaction->isActive()) {
            return [];
        }

        switch ($action) {
            case self::PURCHASE:
                return $this->purchase($paymentTransaction);
            case self::CHARGE:
                return $this->charge($paymentTransaction);
            case self::AUTHORIZE:
                return $this->authorize($paymentTransaction);
            case self::CAPTURE:
                return $this->capture($paymentTransaction);
            case self::COMPLETE:
                return $this->complete($paymentTransaction);
        }

        throw new RuntimeException(sprintf('Unsupported action "%s" executed', $action));
    }

    /**
     * @param PaymentTransaction $paymentTransaction
     *
     * @return array
     * @throws \Oro\Bundle\PayPalExpressBundle\Exception\ExceptionInterface
     */
    protected function purchase(PaymentTransaction $paymentTransaction)
    {

    }

    /**
     * @param PaymentTransaction $paymentTransaction
     *
     * @return array
     * @throws \Oro\Bundle\PayPalExpressBundle\Exception\ExceptionInterface
     */
    protected function charge(PaymentTransaction $paymentTransaction)
    {
        return [];
    }

    /**
     * @param PaymentTransaction $paymentTransaction
     *
     * @return array
     * @throws \Oro\Bundle\PayPalExpressBundle\Exception\ExceptionInterface
     */
    protected function authorize(PaymentTransaction $paymentTransaction)
    {
        return [];
    }


    /**
     * @param PaymentTransaction $paymentTransaction
     *
     * @return array
     * @throws \Oro\Bundle\PayPalExpressBundle\Exception\ExceptionInterface
     */
    protected function capture(PaymentTransaction $paymentTransaction)
    {
        return [];
    }

    /**
     * @param PaymentTransaction $paymentTransaction
     *
     * @return array
     * @throws \Oro\Bundle\PayPalExpressBundle\Exception\ExceptionInterface
     */
    protected function complete(PaymentTransaction $paymentTransaction)
    {
        $paymentId = '';
        $payerId = '';

        $this->payPalTransportFacade->executePayPalPayment($paymentTransaction, $this->config, $paymentId, $payerId);

        return [];
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
