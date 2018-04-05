<?php

namespace Oro\Bundle\PayPalExpressBundle\Method\Translator;

use Oro\Bundle\PaymentBundle\Model\LineItemOptionModel;
use Oro\Bundle\PaymentBundle\Model\Surcharge;
use Oro\Bundle\PaymentBundle\Provider\ExtractOptionsProvider;
use Oro\Bundle\PayPalExpressBundle\Transport\DTO\ItemInfo;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\LineItemsAwareInterface;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Translates {@see LineItemOptionModel} to {@see ItemInfo}.
 */
class LineItemTranslator
{
    const DISCOUNT_ITEM_LABEL = 'oro.paypal_express.discount.pay_pal_item.label';

    /**
     * @var ExtractOptionsProvider
     */
    protected $optionsProvider;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @param ExtractOptionsProvider $optionsProvider
     * @param TranslatorInterface    $translator
     */
    public function __construct(ExtractOptionsProvider $optionsProvider, TranslatorInterface $translator)
    {
        $this->optionsProvider = $optionsProvider;
        $this->translator = $translator;
    }

    /**
     * @param LineItemsAwareInterface $paymentItem
     * @param Surcharge               $surcharge
     * @param string                  $currency
     *
     * @return ItemInfo[]
     */
    public function getPaymentItems(LineItemsAwareInterface $paymentItem, Surcharge $surcharge, $currency)
    {
        $lineItems = $this->optionsProvider->getLineItemPaymentOptions($paymentItem);
        if (!$lineItems) {
            return [];
        }

        $result = [];
        foreach ($lineItems as $lineItem) {
            if ($this->isLineItemShouldBeIgnored($lineItem)) {
                continue;
            }

            $result[] = $this->createItemInfoByLineItem($lineItem);
        }

        $this->addAdditionalPaymentItems($result, $surcharge, $currency);

        return $result;
    }

    /**
     * Workaround to skip line items which is not actually line items. For example tax line item should be skipped
     * since it is added to PayPal's payment info directly.
     *
     * @see \Oro\Bundle\TaxBundle\EventListener\ExtractLineItemPaymentOptionsListener::onExtractLineItemPaymentOptions
     * @see \Oro\Bundle\PayPalExpressBundle\Method\Translator\PaymentTransactionTranslator::getPaymentInfo
     *
     * @param LineItemOptionModel $lineItem
     * @return string
     */
    protected function isLineItemShouldBeIgnored(LineItemOptionModel $lineItem)
    {
        return $this->isTaxLineItem($lineItem);
    }

    /**
     *
     * @param LineItemOptionModel $lineItem
     * @return bool
     */
    protected function isTaxLineItem(LineItemOptionModel $lineItem)
    {
        return !$lineItem->getCurrency();
    }

    /**
     * @param LineItemOptionModel $lineItem
     * @return ItemInfo
     */
    protected function createItemInfoByLineItem(LineItemOptionModel $lineItem)
    {
        return $this->createItemInfo(
            $lineItem->getName(),
            $lineItem->getCurrency(),
            (int)$lineItem->getQty(),
            $lineItem->getCost()
        );
    }

    /**
     * @param string $name
     * @param string $currency
     * @param float $quantity
     * @param float $price
     * @return ItemInfo
     */
    protected function createItemInfo($name, $currency, $quantity, $price)
    {
        return new ItemInfo(
            $name,
            $currency,
            (int)$quantity,
            $price
        );
    }

    /**
     * @param ItemInfo[] $paymentItemsInfo
     * @param Surcharge $surcharge
     * @param string $currency
     */
    protected function addAdditionalPaymentItems(array &$paymentItemsInfo, Surcharge $surcharge, $currency)
    {
        $this->addDiscountLineItemIfApplicable($paymentItemsInfo, $surcharge, $currency);
    }

    /**
     * We could not send discount to PayPal in current API version and should add product with negative amount as a
     * workaround.
     *
     * @param ItemInfo[] $paymentItemsInfo
     * @param Surcharge $surcharge
     * @param string $currency
     */
    protected function addDiscountLineItemIfApplicable(array &$paymentItemsInfo, Surcharge $surcharge, $currency)
    {
        if ($this->hasDiscount($surcharge)) {
            $paymentItemsInfo[] = $this->createDiscountLineItem($currency, $surcharge->getDiscountAmount());
        }
    }

    /**
     * @param Surcharge $surcharge
     * @return bool
     */
    protected function hasDiscount(Surcharge $surcharge)
    {
        return $surcharge->getDiscountAmount() != 0;
    }

    /**
     * @param string $currency
     * @param float $discountAmount
     * @return ItemInfo
     */
    protected function createDiscountLineItem($currency, $discountAmount)
    {
        return $this->createItemInfo(
            $this->translator->trans(static::DISCOUNT_ITEM_LABEL),
            $currency,
            1,
            $discountAmount
        );
    }
}
