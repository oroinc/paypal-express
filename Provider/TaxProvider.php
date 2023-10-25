<?php

namespace Oro\Bundle\PayPalExpressBundle\Provider;

use Oro\Bundle\TaxBundle\Manager\TaxManager;
use Oro\Bundle\TaxBundle\Provider\TaxAmountProvider;
use Oro\Bundle\TaxBundle\Provider\TaxationSettingsProvider;
use Psr\Log\LoggerInterface;

/**
 * Responsible for providing tax amount for payment information.
 *
 * @see \Oro\Bundle\PayPalExpressBundle\Method\Translator\PaymentTransactionTranslator::getPaymentInfo
 */
class TaxProvider
{
    protected TaxManager $taxManager;

    protected TaxationSettingsProvider $taxationSettingsProvider;

    protected LoggerInterface $logger;

    protected TaxAmountProvider $taxAmountProvider;

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
     * @param TaxAmountProvider $taxAmountProvider
     */
    public function setTaxAmountProvider(TaxAmountProvider $taxAmountProvider): void
    {
        $this->taxAmountProvider = $taxAmountProvider;
    }

    /**
     * Return tax if possible, return null if not
     *
     * @return null|float
     */
    public function getTax($entity)
    {
        try {
            return !$this->taxAmountProvider->isTotalIncludedTax()
                ? $this->taxAmountProvider->getExcludedTaxAmount($entity)
                : null;
        } catch (\Throwable $exception) {
            $this->logger->info(
                'Could not load tax amount for entity',
                ['exception' => $exception, 'entity_class' => get_class($entity), 'entity_id' => $entity->getId()]
            );

            return null;
        }
    }
}
