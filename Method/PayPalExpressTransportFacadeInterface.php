<?php

namespace Oro\Bundle\PayPalExpressBundle\Method;

use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PayPalExpressBundle\Exception\ExceptionInterface;
use Oro\Bundle\PayPalExpressBundle\Method\Config\PayPalExpressConfigInterface;

/**
 * Represents {@see PayPalExpressTransportFacade} public interface.
 */
interface PayPalExpressTransportFacadeInterface
{
    /**
     * @param PaymentTransaction           $paymentTransaction
     * @param PayPalExpressConfigInterface $config
     *
     * @return string
     * @throws ExceptionInterface
     */
    public function getPayPalPaymentRoute(PaymentTransaction $paymentTransaction, PayPalExpressConfigInterface $config);

    /**
     * @throws ExceptionInterface
     */
    public function executePayPalPayment(
        PaymentTransaction $paymentTransaction,
        PayPalExpressConfigInterface $payPalExpressConfig
    );

    /**
     * @throws ExceptionInterface
     */
    public function capturePayment(
        PaymentTransaction $paymentTransaction,
        PaymentTransaction $authorizedTransaction,
        PayPalExpressConfigInterface $config
    );

    /**
     * @throws ExceptionInterface
     */
    public function authorizePayment(PaymentTransaction $paymentTransaction, PayPalExpressConfigInterface $config);
}
