<?php

namespace Oro\Bundle\PayPalExpressBundle\Transport;

use Oro\Bundle\PayPalExpressBundle\Transport\DTO\ApiContextInfo;
use Oro\Bundle\PayPalExpressBundle\Transport\DTO\CredentialsInfo;
use Oro\Bundle\PayPalExpressBundle\Transport\DTO\PaymentInfo;
use Oro\Bundle\PayPalExpressBundle\Transport\DTO\RedirectRoutesInfo;
use PayPal\Api\Authorization;
use PayPal\Api\Capture;
use PayPal\Api\Payment;
use PayPal\Api\PaymentExecution;
use PayPal\Auth\OAuthTokenCredential;
use PayPal\Rest\ApiContext;

interface PayPalSDKObjectTranslatorInterface
{
    /**
     * Convert Payment DTO into PayPal SDK Payment object
     *
     * @param PaymentInfo        $paymentInfo
     * @param RedirectRoutesInfo $redirectRoutesInfo
     *
     * @return Payment
     */
    public function getPayment(PaymentInfo $paymentInfo, RedirectRoutesInfo $redirectRoutesInfo);

    /**
     * @param ApiContextInfo $apiContextInfo
     *
     * @return ApiContext
     */
    public function getApiContext(ApiContextInfo $apiContextInfo);

    /**
     * @param CredentialsInfo $credentialsInfo
     *
     * @return OAuthTokenCredential
     */
    public function getApiCredentials(CredentialsInfo $credentialsInfo);

    /**
     * @param PaymentInfo $paymentInfo
     *
     * @return PaymentExecution
     */
    public function getPaymentExecution(PaymentInfo $paymentInfo);

    /**
     * @param PaymentInfo $paymentInfo
     *
     * @return Authorization
     */
    public function getAuthorization(PaymentInfo $paymentInfo);

    /**
     * @param PaymentInfo $paymentInfo
     *
     * @return Capture
     */
    public function getCapturedDetails(PaymentInfo $paymentInfo);
}
