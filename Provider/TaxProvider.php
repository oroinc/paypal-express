<?php

namespace Oro\Bundle\PayPalExpressBundle\Provider;

use Oro\Bundle\TaxBundle\Manager\TaxManager;
use Oro\Bundle\TaxBundle\Provider\TaxationSettingsProvider;
use Psr\Log\LoggerInterface;

/**
 * Responsible for providing tax amount for payment information.
 *
 * @see \Oro\Bundle\PayPalExpressBundle\Method\Translator\PaymentTransactionTranslator::getPaymentInfo
 */
class TaxProvider
{
    public function __construct(
        protected TaxManager $taxManager,
        protected LoggerInterface $logger,
        protected TaxationSettingsProvider $taxationSettingsProvider
    ) {
    }

    /**
     * Return tax if possible, return null if not
     */
    public function getTax($entity): ?int
    {
        try {
            if ($this->taxationSettingsProvider->isProductPricesIncludeTax()
                && $this->taxationSettingsProvider->isShippingRatesIncludeTax()) {
                return null;
            }

            $tax = $this->taxManager->loadTax($entity);
            $shippingTax = $tax->getShipping()->getTaxAmount();
            $productTax = $tax->getTotal()->getTaxAmount() - $shippingTax;
            return ($this->taxationSettingsProvider->isProductPricesIncludeTax() ? 0 : $productTax)
                + ($this->taxationSettingsProvider->isShippingRatesIncludeTax() ? 0 : $shippingTax);
        } catch (\Throwable $exception) {
            $this->logger->info(
                'Could not load tax amount for entity',
                ['exception' => $exception, 'entity_class' => get_class($entity), 'entity_id' => $entity->getId()]
            );

            return null;
        }
    }
}
