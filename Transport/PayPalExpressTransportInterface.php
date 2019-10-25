<?php

namespace Oro\Bundle\PayPalExpressBundle\Transport;

use Oro\Bundle\PayPalExpressBundle\Exception\ExceptionInterface;
use Oro\Bundle\PayPalExpressBundle\Transport\DTO\ApiContextInfo;
use Oro\Bundle\PayPalExpressBundle\Transport\DTO\PaymentInfo;
use Oro\Bundle\PayPalExpressBundle\Transport\DTO\RedirectRoutesInfo;

/**
 * Represents public interface for transport.
 */
interface PayPalExpressTransportInterface
{
    /**
     * @param PaymentInfo        $paymentInfo
     * @param ApiContextInfo     $apiContextInfo
     * @param RedirectRoutesInfo $redirectRoutesInfo
     *
     * @return string Link where user should approve payment
     * @throws ExceptionInterface
     */
    public function setupPayment(
        PaymentInfo $paymentInfo,
        ApiContextInfo $apiContextInfo,
        RedirectRoutesInfo $redirectRoutesInfo
    );

    /**
     * @param PaymentInfo     $paymentInfo
     * @param ApiContextInfo  $apiContextInfo
     * @throws ExceptionInterface
     */
    public function executePayment(PaymentInfo $paymentInfo, ApiContextInfo $apiContextInfo);

    /**
     * @param PaymentInfo    $paymentInfo
     * @param ApiContextInfo $apiContextInfo
     * @throws ExceptionInterface
     */
    public function authorizePayment(PaymentInfo $paymentInfo, ApiContextInfo $apiContextInfo);

    /**
     * @param PaymentInfo    $paymentInfo
     * @param ApiContextInfo $apiContextInfo
     * @throws ExceptionInterface
     */
    public function capturePayment(PaymentInfo $paymentInfo, ApiContextInfo $apiContextInfo);
}
