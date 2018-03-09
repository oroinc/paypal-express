<?php

namespace Oro\Bundle\PayPalExpressBundle\Transport;

use Oro\Bundle\CurrencyBundle\Entity\PriceAwareInterface;
use Oro\Bundle\PayPalExpressBundle\Transport\DTO\ItemInfo;
use Oro\Bundle\ProductBundle\Model\ProductLineItemInterface;
use Oro\Component\Checkout\LineItem\CheckoutLineItemInterface;

class PaymentItemTranslator
{
    /**
     * @param object   $lineItem
     * @param string   $currency
     * @param ItemInfo $item
     *
     * @return bool
     */
    public function tryGetPaymentItemInfo($lineItem, $currency, ItemInfo &$item = null)
    {
        if (!$lineItem instanceof ProductLineItemInterface || !$lineItem instanceof CheckoutLineItemInterface) {
            return false;
        }

        if (!$lineItem instanceof PriceAwareInterface) {
            return false;
        }

        $item = new ItemInfo(
            $lineItem->getProduct()->getName()->getString(),
            $currency,
            $lineItem->getQuantity(),
            $lineItem->getPrice()->getValue()
        );

        return true;
    }
}
