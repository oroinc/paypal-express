<?php

namespace Oro\Bundle\PayPalExpressBundle\Method\Translator;

use Oro\Bundle\PaymentBundle\Model\Surcharge;
use Oro\Bundle\PaymentBundle\Provider\ExtractOptionsProvider;
use Oro\Bundle\PayPalExpressBundle\Transport\DTO\ItemInfo;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\LineItemsAwareInterface;

use Symfony\Component\Translation\TranslatorInterface;

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
            $result[] = new ItemInfo(
                $lineItem->getName(),
                $lineItem->getCurrency(),
                (int)$lineItem->getQty(),
                $lineItem->getCost()
            );
        }

        /**
         * We could not send discount to paypal in current api version
         * and should add product with negative amount as workaround for it
         */
        if ($surcharge->getDiscountAmount() != 0) {
            $result[] = new ItemInfo(
                $this->translator->trans(static::DISCOUNT_ITEM_LABEL),
                $currency,
                1,
                $surcharge->getDiscountAmount()
            );
        }

        return $result;
    }
}
