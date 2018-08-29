<?php

namespace Oro\Bundle\PayPalExpressBundle\Transport;

use Oro\Bundle\PayPalExpressBundle\Transport\DTO\ApiContextInfo;
use Oro\Bundle\PayPalExpressBundle\Transport\DTO\CredentialsInfo;
use Oro\Bundle\PayPalExpressBundle\Transport\DTO\ErrorInfo;
use Oro\Bundle\PayPalExpressBundle\Transport\DTO\PaymentInfo;
use Oro\Bundle\PayPalExpressBundle\Transport\DTO\RedirectRoutesInfo;
use PayPal\Api\Amount;
use PayPal\Api\Authorization;
use PayPal\Api\Capture;
use PayPal\Api\Details;
use PayPal\Api\Item;
use PayPal\Api\ItemList;
use PayPal\Api\Payer;
use PayPal\Api\Payment;
use PayPal\Api\PaymentExecution;
use PayPal\Api\RedirectUrls;
use PayPal\Api\Transaction;
use PayPal\Auth\OAuthTokenCredential;
use PayPal\Exception\PayPalConnectionException;
use PayPal\Rest\ApiContext;

/**
 * {@inheritdoc}
 */
class PayPalSDKObjectTranslator implements PayPalSDKObjectTranslatorInterface
{
    const MOD_SANDBOX = 'sandbox';
    const MOD_LIVE = 'live';
    const APPLICATION_PARTNER_ID = 'OroCommerce_SP';

    /**
     * {@inheritdoc}
     */
    public function getPayment(PaymentInfo $paymentInfo, RedirectRoutesInfo $redirectRoutesInfo)
    {
        $payer = new Payer();
        $payer->setPaymentMethod($paymentInfo->getMethod());

        $itemList = new ItemList();
        foreach ($paymentInfo->getItems() as $itemInfo) {
            $item = new Item();

            $item->setName($itemInfo->getName());
            $item->setCurrency($itemInfo->getCurrency());
            $item->setQuantity($itemInfo->getQuantity());
            $item->setPrice($itemInfo->getPrice());

            $itemList->addItem($item);
        }

        $details = new Details();
        $details->setShipping($paymentInfo->getShipping())
            ->setSubtotal($paymentInfo->getSubtotal());

        if (null !== $paymentInfo->getTax()) {
            $details->setTax($paymentInfo->getTax());
        }

        $amount = new Amount();
        $amount->setCurrency($paymentInfo->getCurrency())
            ->setTotal($paymentInfo->getTotalAmount())
            ->setDetails($details);

        $invoiceNumber = $paymentInfo->getInvoiceNumber();
        $transaction = new Transaction();
        $transaction->setAmount($amount)
            ->setItemList($itemList)
            ->setInvoiceNumber($invoiceNumber);

        $payment = new Payment();
        $payment->setIntent("order")
            ->setTransactions([$transaction])
            ->setPayer($payer);

        $redirectUrls = new RedirectUrls();
        $redirectUrls->setReturnUrl($redirectRoutesInfo->getSuccessRoute())
            ->setCancelUrl($redirectRoutesInfo->getFailedRoute());

        $payment
            ->setRedirectUrls($redirectUrls);

        return $payment;
    }

    /**
     * {@inheritdoc}
     */
    public function getApiContext(ApiContextInfo $apiContextInfo)
    {
        $credentials = $this->getApiCredentials($apiContextInfo->getCredentialsInfo());
        $apiContext = new ApiContext($credentials);
        $apiContext->setConfig(['mode' => $apiContextInfo->isSandbox() ? static::MOD_SANDBOX : static::MOD_LIVE]);
        /**
         * Apply workaround for issue with invalid ssl constant in pay_pal sdk
         */
        $apiContext->setConfig(['http.CURLOPT_SSLVERSION' => CURL_SSLVERSION_TLSv1]);
        $apiContext->addRequestHeader('PayPal-Partner-Attribution-Id', static::APPLICATION_PARTNER_ID);

        return $apiContext;
    }

    /**
     * {@inheritdoc}
     */
    public function getApiCredentials(CredentialsInfo $credentialsInfo)
    {
        return new OAuthTokenCredential($credentialsInfo->getClientId(), $credentialsInfo->getClientSecret());
    }

    /**
     * {@inheritdoc}
     */
    public function getPaymentExecution(PaymentInfo $paymentInfo)
    {
        $execution = new PaymentExecution();
        $execution->setPayerId($paymentInfo->getPayerId());

        return $execution;
    }

    /**
     * {@inheritdoc}
     */
    public function getAuthorization(PaymentInfo $paymentInfo)
    {
        $amount = new Amount();
        $amount->setCurrency($paymentInfo->getCurrency())
            ->setTotal($paymentInfo->getTotalAmount());

        $authorization = new Authorization();
        $authorization->setAmount($amount);

        return $authorization;
    }

    /**
     * {@inheritdoc}
     */
    public function getCapturedDetails(PaymentInfo $paymentInfo)
    {
        $captureDetails = new Capture();
        $amount = new Amount();
        $amount->setCurrency($paymentInfo->getCurrency())
            ->setTotal($paymentInfo->getTotalAmount());
        $captureDetails->setAmount($amount);
        $captureDetails->setIsFinalCapture(true);

        return $captureDetails;
    }

    /**
     * {@inheritdoc}
     */
    public function getErrorInfo(PayPalConnectionException $exception)
    {
        $message = $exception->getMessage();
        $name = $exception->getCode();
        $details = '';
        $link = '';
        $debugId = '';

        $rawData = $exception->getData();
        if ($rawData) {
            $parsedExceptionData = json_decode($rawData, true);
            if ($parsedExceptionData) {
                if (isset($parsedExceptionData['message'])) {
                    $message = $parsedExceptionData['message'];
                }
                if (isset($parsedExceptionData['name'])) {
                    $name = $parsedExceptionData['name'];
                }
                if (isset($parsedExceptionData['details'])) {
                    $details = $parsedExceptionData['details'];
                }
                if (isset($parsedExceptionData['information_link'])) {
                    $link = $parsedExceptionData['information_link'];
                }
                if (isset($parsedExceptionData['debug_id'])) {
                    $debugId = $parsedExceptionData['debug_id'];
                }
            }
        }

        $errorInfo = new ErrorInfo(
            $message,
            $name,
            $details,
            $link,
            $debugId,
            $rawData
        );

        return $errorInfo;
    }
}
