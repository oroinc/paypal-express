<?php

namespace Oro\Bundle\PayPalExpressBundle\Transport;

use Oro\Bundle\PayPalExpressBundle\Transport\DTO\ApiContextInfo;
use Oro\Bundle\PayPalExpressBundle\Transport\DTO\CredentialsInfo;
use Oro\Bundle\PayPalExpressBundle\Transport\DTO\ErrorInfo;
use Oro\Bundle\PayPalExpressBundle\Transport\DTO\PaymentInfo;
use Oro\Bundle\PayPalExpressBundle\Transport\DTO\RedirectRoutesInfo;
use PayPal\Api\Authorization;
use PayPal\Api\Capture;
use PayPal\Api\Payment;
use PayPal\Api\PaymentExecution;
use PayPal\Auth\OAuthTokenCredential;
use PayPal\Exception\PayPalConnectionException;
use PayPal\Rest\ApiContext;

/**
 * Responsible for translation:
 * -
 *   from: {@see PaymentInfo} and {@see RedirectRoutesInfo}
 *   to: {@see Payment}
 * -
 *   from: {@see ApiContextInfo}
 *   to: {@see ApiContext}
 * -
 *   from: {@see CredentialsInfo}
 *   to: {@see OAuthTokenCredential}
 * -
 *   from: {@see PaymentInfo}
 *   to: {@see PaymentExecution}
 * -
 *   from: {@see PaymentInfo}
 *   to: {@see Authorization}
 * -
 *   from: {@see PaymentInfo}
 *   to: {@see Capture}
 * -
 *   from: {@see PayPalConnectionException}
 *   to: {@see ErrorInfo}
 */
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

    /**
     * @param PayPalConnectionException $exception
     *
     * @return ErrorInfo
     */
    public function getErrorInfo(PayPalConnectionException $exception);
}
