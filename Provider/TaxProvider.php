<?php

namespace Oro\Bundle\PayPalExpressBundle\Provider;

use Oro\Bundle\TaxBundle\Manager\TaxManager;
use Psr\Log\LoggerInterface;

/**
 * Responsible for provide tax amount for payment information
 * @see \Oro\Bundle\PayPalExpressBundle\Method\Translator\PaymentTransactionTranslator::getPaymentInfo
 */
class TaxProvider
{
    /**
     * @var TaxManager
     */
    protected $taxManager;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @param TaxManager      $taxManager
     * @param LoggerInterface $logger
     */
    public function __construct(TaxManager $taxManager, LoggerInterface $logger)
    {
        $this->taxManager = $taxManager;
        $this->logger     = $logger;
    }

    /**
     * Return tax if possible, return 0 if not
     *
     * @param object $entity
     *
     * @return int
     */
    public function getTax($entity)
    {
        try {
            return $this->taxManager->loadTax($entity)->getTotal()->getTaxAmount();
        } catch (\Throwable $exception) {
            $this->logger->info(
                'Could not load tax amount for entity',
                ['exception' => $exception, 'entity_class' => get_class($entity), 'entity_id' => $entity->getId()]
            );

            return 0;
        }
    }
}
