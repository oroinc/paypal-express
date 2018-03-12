<?php

namespace Oro\Bundle\PayPalExpressBundle\Transport;

use Oro\Bundle\PayPalExpressBundle\Transport\DTO\CredentialsInfo;
use Oro\Bundle\PayPalExpressBundle\Transport\DTO\PaymentInfo;

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
use PayPal\Rest\ApiContext;

class PayPalSDKObjectTranslator
{
    /**
     * Convert Payment DTO into PayPal SDK Payment object
     *
     * @param PaymentInfo $paymentInfo
     * @param string      $successRoute Route where PayPal will redirect user after payment approve
     * @param string      $failedRoute Route where PayPal will redirect user after payment cancel
     *
     * @return Payment
     */
    public function getPayment(PaymentInfo $paymentInfo, $successRoute, $failedRoute)
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
            ->setTax($paymentInfo->getTax())
            ->setSubtotal($paymentInfo->getSubtotal());

        $amount = new Amount();
        $amount->setCurrency($paymentInfo->getCurrency())
            ->setTotal($paymentInfo->getTotalAmount())
            ->setDetails($details);

        $invoiceNumber = uniqid();
        $transaction = new Transaction();
        $transaction->setAmount($amount)
            ->setItemList($itemList)
            ->setInvoiceNumber($invoiceNumber);

        $payment = new Payment();
        $payment->setIntent("order")
            ->setTransactions([$transaction])
            ->setPayer($payer);

        $redirectUrls = new RedirectUrls();
        $redirectUrls->setReturnUrl($successRoute)
            ->setCancelUrl($failedRoute);

        $payment
            ->setRedirectUrls($redirectUrls);

        return $payment;
    }

    /**
     * @param CredentialsInfo $credentialsInfo
     *
     * @return ApiContext
     */
    public function getApiContext(CredentialsInfo $credentialsInfo)
    {
        $apiContext = new ApiContext(
            new OAuthTokenCredential($credentialsInfo->getClientId(), $credentialsInfo->getClientSecret())
        );

        return $apiContext;
    }

    /**
     * @param PaymentInfo $paymentInfo
     *
     * @return PaymentExecution
     */
    public function getPaymentExecution(PaymentInfo $paymentInfo)
    {
        $execution = new PaymentExecution();
        $execution->setPayerId($paymentInfo->getPayerId());

        return $execution;
    }

    /**
     * @param PaymentInfo $paymentInfo
     *
     * @return Authorization
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
     * @param PaymentInfo $paymentInfo
     *
     * @return Capture
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
}
