<?php

namespace Oro\Bundle\PayPalExpressBundle\SDK;

use Oro\Bundle\PayPalExpressBundle\SDK\DTO\CredentialsInfo;
use Oro\Bundle\PayPalExpressBundle\SDK\DTO\PaymentInfo;

use PayPal\Api\Amount;
use PayPal\Api\Details;
use PayPal\Api\Item;
use PayPal\Api\ItemList;
use PayPal\Api\Payer;
use PayPal\Api\Payment;
use PayPal\Api\RedirectUrls;
use PayPal\Api\Transaction;
use PayPal\Auth\OAuthTokenCredential;
use PayPal\Rest\ApiContext;

class PayPalObjectsTranslator
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
            ->setDescription($paymentInfo->getDescription())
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
}
