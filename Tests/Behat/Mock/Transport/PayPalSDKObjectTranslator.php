<?php

namespace Oro\Bundle\PayPalExpressBundle\Tests\Behat\Mock\Transport;

use Doctrine\Common\Cache\Cache;
use Oro\Bundle\PayPalBundle\Tests\Behat\Mock\PayPal\Payflow\Client\NVPClientMock;
use Oro\Bundle\PayPalExpressBundle\Transport\DTO\ItemInfo;
use Oro\Bundle\PayPalExpressBundle\Transport\DTO\PaymentInfo;
use Oro\Bundle\PayPalExpressBundle\Transport\DTO\RedirectRoutesInfo;
use Oro\Bundle\PayPalExpressBundle\Transport\PayPalSDKObjectTranslator as _PayPalSDKObjectTranslator;

class PayPalSDKObjectTranslator extends _PayPalSDKObjectTranslator
{
    private const LINE_ITEM_CACHE_KEY = NVPClientMock::LINE_ITEM_CACHE_KEY;

    /**
     * @var Cache
     */
    private $cache;

    public function __construct($cache)
    {
        $this->cache = $cache;
    }

    /**
     * @param PaymentInfo $paymentInfo
     * @param RedirectRoutesInfo $redirectRoutesInfo
     *
     * @return \PayPal\Api\Payment
     */
    public function getPayment(PaymentInfo $paymentInfo, RedirectRoutesInfo $redirectRoutesInfo)
    {
        $filteredLineItems = array_map(function (ItemInfo $value) {
            return $value->getName();
        }, $paymentInfo->getItems());

        if ($this->cache->contains(self::LINE_ITEM_CACHE_KEY)) {
            $filteredLineItems = array_merge($filteredLineItems, $this->cache->fetch(self::LINE_ITEM_CACHE_KEY));
        }
        $this->cache->save(self::LINE_ITEM_CACHE_KEY, $filteredLineItems);

        return parent::getPayment($paymentInfo, $redirectRoutesInfo);
    }
}
