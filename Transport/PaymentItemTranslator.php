<?php

namespace Oro\Bundle\PayPalExpressBundle\Transport;

use Oro\Bundle\CurrencyBundle\Entity\PriceAwareInterface;
use Oro\Bundle\PayPalExpressBundle\Transport\DTO\ItemInfo;
use Oro\Bundle\ProductBundle\Model\ProductLineItemInterface;

class PaymentItemTranslator
{
    /**
     * @param object   $lineItem
     * @param string   $currency
     *
     * @return ItemInfo|null
     */
    public function getPaymentItemInfo($lineItem, $currency)
    {
        if (!$lineItem instanceof ProductLineItemInterface || !$lineItem instanceof PriceAwareInterface) {
            return null;
        }

        $lineItem->getProduct();

        $result = new ItemInfo(
            $lineItem->getProduct()->getName()->getString(),
            $currency,
            $lineItem->getQuantity(),
            $lineItem->getPrice()->getValue()
        );

        return $result;
    }
}
