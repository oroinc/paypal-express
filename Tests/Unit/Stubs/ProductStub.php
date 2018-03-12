<?php

namespace Oro\Bundle\PayPalExpressBundle\Tests\Unit\Stubs;

use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\ProductBundle\Entity\Product;

class ProductStub extends Product
{
    /**
     * @param Localization|null $localization
     * @return \Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue|void
     */
    public function getName(Localization $localization = null)
    {
        if ($this->names->isEmpty()) {
            return;
        }

        return $this->names->first();
    }
}
