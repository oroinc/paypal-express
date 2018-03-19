<?php

namespace Oro\Bundle\PayPalExpressBundle\Method;

use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PayPalExpressBundle\Exception\ExceptionInterface;
use Oro\Bundle\PayPalExpressBundle\Method\Config\PayPalExpressConfigInterface;

interface PayPalTransportFacadeInterface
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
     * @param PaymentTransaction           $paymentTransaction
     * @param PayPalExpressConfigInterface $payPalExpressConfig
     * @param string                       $paymentId
     * @param string                       $payerId
     * @throws ExceptionInterface
     */
    public function executePayPalPayment(
        PaymentTransaction $paymentTransaction,
        PayPalExpressConfigInterface $payPalExpressConfig,
        $paymentId,
        $payerId
    );

    /**
     * @param PaymentTransaction           $paymentTransaction
     * @param PayPalExpressConfigInterface $config
     *
     * @throws ExceptionInterface
     */
    public function capturePayment(PaymentTransaction $paymentTransaction, PayPalExpressConfigInterface $config);

    /**
     * @param PaymentTransaction           $paymentTransaction
     * @param PayPalExpressConfigInterface $config
     *
     * @throws ExceptionInterface
     */
    public function authorizePayment(PaymentTransaction $paymentTransaction, PayPalExpressConfigInterface $config);

    /**
     * @param PaymentTransaction           $paymentTransaction
     * @param PayPalExpressConfigInterface $config
     *
     * @throws ExceptionInterface
     */
    public function authorizeAndCapture(PaymentTransaction $paymentTransaction, PayPalExpressConfigInterface $config);
}
