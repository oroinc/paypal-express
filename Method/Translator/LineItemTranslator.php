<?php

namespace Oro\Bundle\PayPalExpressBundle\Method\Translator;

use Oro\Bundle\CurrencyBundle\Rounding\RoundingServiceInterface;
use Oro\Bundle\LocaleBundle\Formatter\NumberFormatter;
use Oro\Bundle\PaymentBundle\Model\LineItemOptionModel;
use Oro\Bundle\PaymentBundle\Model\Surcharge;
use Oro\Bundle\PayPalBundle\OptionsProvider\OptionsProviderInterface;
use Oro\Bundle\PayPalExpressBundle\Transport\DTO\ItemInfo;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\LineItemsAwareInterface;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Translates {@see LineItemOptionModel} to {@see ItemInfo}.
 */
class LineItemTranslator
{
    const DISCOUNT_ITEM_LABEL = 'oro.paypal_express.discount.pay_pal_item.label';
    const TOTAL_ITEM_LABEL = 'oro.paypal_express.total.pay_pal_item.label';
    const PAYPAL_NAME_LIMIT = 36;

    /**
     * @var OptionsProviderInterface
     */
    protected $optionsProvider;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @var NumberFormatter
     */
    protected $currencyFormatter;

    /**
     * @var RoundingServiceInterface
     */
    protected $rounder;

    /**
     * @param OptionsProviderInterface $optionsProvider
     * @param TranslatorInterface    $translator
     */
    public function __construct(OptionsProviderInterface $optionsProvider, TranslatorInterface $translator)
    {
        $this->optionsProvider = $optionsProvider;
        $this->translator = $translator;
    }

    /**
     * @param NumberFormatter $currencyFormatter
     */
    public function setCurrencyFormatter(NumberFormatter $currencyFormatter)
    {
        $this->currencyFormatter = $currencyFormatter;
    }

    /**
     * @param RoundingServiceInterface $roundingService
     */
    public function setRounder(RoundingServiceInterface $roundingService)
    {
        $this->rounder = $roundingService;
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
        $lineItems = $this->optionsProvider->getLineItemOptions($paymentItem);

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
            $lineItem->getQty(),
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
        // PayPal doesn't support float quantities and prices with precision more than 2.
        // Multiply qty by cost and add information about actual qty and price to line item name
        if ($quantity != 1 && ($this->isPrecisionMoreThan($quantity, 0) || $this->isPrecisionMoreThan($price, 2))) {
            $additionalNameInfo = sprintf(
                ' - %sx%s',
                $this->currencyFormatter->formatCurrency($price, $currency),
                $quantity
            );

            $name = sprintf(
                '%s%s',
                // we can't use multibyte string functions here
                // because PayPal doesn't use multibyte when calculating string length
                substr($name, 0, self::PAYPAL_NAME_LIMIT - strlen($additionalNameInfo)),
                $additionalNameInfo
            );

            // Update cost and qty to have 1 item with cost = price * qty
            $price *= $quantity;
            $quantity = 1.0;
        }

        return new ItemInfo(
            $name,
            $currency,
            (int)$quantity,
            $this->roundForPayPal($price)
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

    /**
     * @param string $currency
     * @param float $subtotal
     *
     * @return ItemInfo
     */
    public function createTotalLineItem($currency, $subtotal)
    {
        return $this->createItemInfo(
            $this->translator->trans(static::TOTAL_ITEM_LABEL),
            $currency,
            1,
            $subtotal
        );
    }

    /**
     * @param float $number
     * @param int $precision
     * @return bool
     */
    protected function isPrecisionMoreThan($number, $precision): bool
    {
        return (bool) ($number - round($number, $precision));
    }

    /**
     * @param float $number
     * @return float|int
     */
    public function roundForPayPal($number)
    {
        $precision = $this->rounder->getPrecision();

        if ($precision > 2) {
            $precision = 2;
        }

        return $this->rounder->round($number, $precision);
    }
}
