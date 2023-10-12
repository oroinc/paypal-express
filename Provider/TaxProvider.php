<?php

namespace Oro\Bundle\PayPalExpressBundle\Provider;

use Oro\Bundle\TaxBundle\Provider\TaxAmountProvider;
use Psr\Log\LoggerInterface;

/**
 * Responsible for providing tax amount for payment information.
 *
 * @see \Oro\Bundle\PayPalExpressBundle\Method\Translator\PaymentTransactionTranslator::getPaymentInfo
 */
class TaxProvider
{
    public function __construct(
        private TaxAmountProvider $taxAmountProvider,
        protected LoggerInterface $logger
    ) {
    }

    /**
     * Return tax if possible, return null if not
     */
    public function getTax($entity): null|int|float
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
