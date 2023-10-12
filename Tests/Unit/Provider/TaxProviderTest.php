<?php

namespace Oro\Bundle\PayPalExpressBundle\Tests\Unit\Provider;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PayPalExpressBundle\Provider\TaxProvider;
use Oro\Bundle\PayPalExpressBundle\Tests\Unit\Stubs\FooPaymentEntityStub;
use Oro\Bundle\TaxBundle\Exception\TaxationDisabledException;
use Oro\Bundle\TaxBundle\Manager\TaxManager;
use Oro\Bundle\TaxBundle\Provider\TaxAmountProvider;
use Oro\Bundle\TaxBundle\Provider\TaxationSettingsProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class TaxProviderTest extends TestCase
{
    private TaxProvider $taxProvider;

    private LoggerInterface|MockObject $logger;

    private TaxManager|MockObject $taxManager;

    private TaxationSettingsProvider|MockObject $taxationSettingsProvider;

    private TaxAmountProvider|MockObject $taxAmountProvider;

    protected function setUp(): void
    {
        $this->taxManager = $this->createMock(TaxManager::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->taxationSettingsProvider = $this->createMock(TaxationSettingsProvider::class);
        $this->taxAmountProvider = $this->createMock(TaxAmountProvider::class);

        $this->taxProvider = new TaxProvider($this->taxManager, $this->logger, $this->taxationSettingsProvider);
        $this->taxProvider->setTaxAmountProvider($this->taxAmountProvider);
    }

    public function testGetTax(): void
    {
        $entity = new Order();
        $tax = 12.34;

        $this->taxAmountProvider->expects($this->once())
            ->method('isTotalIncludedTax')
            ->willReturn(false);

        $this->taxAmountProvider->expects($this->once())
            ->method('getExcludedTaxAmount')
            ->willReturn($tax);

        $actualTaxAmount = $this->taxProvider->getTax($entity);
        $this->assertSame($tax, $actualTaxAmount);
    }

    public function testGetTaxShouldRecoverFromAnyErrorLogItAndReturnZero(): void
    {
        $entity = new FooPaymentEntityStub();

        $id = 42;
        $entity->setId($id);

        $this->taxAmountProvider->expects($this->once())
            ->method('isTotalIncludedTax')
            ->willReturn(false);

        $exception = new TaxationDisabledException();
        $this->taxAmountProvider->expects($this->once())
            ->method('getExcludedTaxAmount')
            ->willThrowException($exception);

        $this->logger->expects($this->once())
            ->method('info')
            ->with(
                'Could not load tax amount for entity',
                ['exception' => $exception, 'entity_class' => FooPaymentEntityStub::class, 'entity_id' => $id]
            );

        $actualTaxAmount = $this->taxProvider->getTax($entity);
        $this->assertNull($actualTaxAmount);
    }

    public function testGetTaxWithProductPricesIncludeTax(): void
    {
        $entity = new Order();

        $this->taxAmountProvider->expects($this->once())
            ->method('isTotalIncludedTax')
            ->willReturn(true);

        $this->taxAmountProvider->expects($this->never())
            ->method('getExcludedTaxAmount');

        $actualTaxAmount = $this->taxProvider->getTax($entity);
        $this->assertNull($actualTaxAmount);
    }
}
