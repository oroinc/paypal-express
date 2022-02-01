<?php

namespace Oro\Bundle\PayPalExpressBundle\Tests\Behat\Mock\Transport;

use Oro\Bundle\PayPalBundle\Tests\Behat\Mock\PayPal\Payflow\Client\NVPClientMock;
use Oro\Bundle\PayPalExpressBundle\Transport\DTO\ItemInfo;
use Oro\Bundle\PayPalExpressBundle\Transport\DTO\PaymentInfo;
use Oro\Bundle\PayPalExpressBundle\Transport\DTO\RedirectRoutesInfo;
use Oro\Bundle\PayPalExpressBundle\Transport\PayPalSDKObjectTranslator as _PayPalSDKObjectTranslator;
use Psr\Cache\CacheItemPoolInterface;

class PayPalSDKObjectTranslator extends _PayPalSDKObjectTranslator
{
    private const LINE_ITEM_CACHE_KEY = NVPClientMock::LINE_ITEM_CACHE_KEY;

    private CacheItemPoolInterface $cache;

    public function __construct(CacheItemPoolInterface $cache)
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

        $cacheItem = $this->cache->getItem(self::LINE_ITEM_CACHE_KEY);
        if ($cacheItem->isHit()) {
            $filteredLineItems = array_merge($filteredLineItems, $cacheItem->get());
        }
        $cacheItem->set($filteredLineItems);
        $this->cache->save($cacheItem);

        return parent::getPayment($paymentInfo, $redirectRoutesInfo);
    }
}
