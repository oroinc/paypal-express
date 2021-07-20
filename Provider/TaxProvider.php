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
    /**
     * @var TaxManager
     */
    protected $taxManager;

    /**
     * @var TaxationSettingsProvider
     */
    protected $taxationSettingsProvider;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    public function __construct(
        TaxManager $taxManager,
        LoggerInterface $logger,
        TaxationSettingsProvider $taxationSettingsProvider
    ) {
        $this->taxManager = $taxManager;
        $this->logger = $logger;
        $this->taxationSettingsProvider = $taxationSettingsProvider;
    }

    /**
     * Return tax if possible, return null if not
     *
     * @param object $entity
     *
     * @return null|int
     */
    public function getTax($entity)
    {
        try {
            if ($this->taxationSettingsProvider->isProductPricesIncludeTax()) {
                return null;
            }

            return $this->taxManager->loadTax($entity)->getTotal()->getTaxAmount();
        } catch (\Throwable $exception) {
            $this->logger->info(
                'Could not load tax amount for entity',
                ['exception' => $exception, 'entity_class' => get_class($entity), 'entity_id' => $entity->getId()]
            );

            return null;
        }
    }
}
