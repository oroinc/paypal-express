<?php

namespace Oro\Bundle\PayPalExpressBundle\Tests\Unit\Provider;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PayPalExpressBundle\Provider\TaxProvider;
use Oro\Bundle\PayPalExpressBundle\Tests\Unit\Stubs\FooPaymentEntityStub;
use Oro\Bundle\TaxBundle\Exception\TaxationDisabledException;
use Oro\Bundle\TaxBundle\Manager\TaxManager;
use Oro\Bundle\TaxBundle\Model\Result;
use Oro\Bundle\TaxBundle\Model\ResultElement;
use Oro\Bundle\TaxBundle\Provider\TaxationSettingsProvider;
use Psr\Log\LoggerInterface;

class TaxProviderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var TaxProvider
     */
    protected $taxProvider;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|LoggerInterface
     */
    protected $logger;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|TaxManager
     */
    protected $taxManager;

    /**
     * @var TaxationSettingsProvider|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $taxationSettingsProvider;

    protected function setUp(): void
    {
        $this->taxManager = $this->createMock(TaxManager::class);
        $this->taxationSettingsProvider = $this->createMock(TaxationSettingsProvider::class);

        $this->logger = $this->createMock(LoggerInterface::class);

        $this->taxProvider = new TaxProvider($this->taxManager, $this->logger, $this->taxationSettingsProvider);
    }

    public function testGetTax()
    {
        $entity = new Order();

        $expectedTaxAmount = 2;

        $taxTotal = [ResultElement::TAX_AMOUNT => $expectedTaxAmount, ResultElement::CURRENCY => 'USD'];

        $taxResult = Result::jsonDeserialize([Result::TOTAL => $taxTotal]);

        $this->taxationSettingsProvider->expects($this->once())
            ->method('isProductPricesIncludeTax')
            ->willReturn(false);

        $this->taxManager->expects($this->once())
            ->method('loadTax')
            ->with($entity)
            ->willReturn($taxResult);

        $actualTaxAmount = $this->taxProvider->getTax($entity);
        $this->assertSame($expectedTaxAmount, $actualTaxAmount);
    }

    public function testGetTaxShouldRecoverFromAnyErrorLogItAndReturnZero()
    {
        $entity = new FooPaymentEntityStub();

        $id = 42;
        $entity->setId($id);

        $this->taxationSettingsProvider->expects($this->once())
            ->method('isProductPricesIncludeTax')
            ->willReturn(false);

        $exception = new TaxationDisabledException();
        $this->taxManager->expects($this->once())
            ->method('loadTax')
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

    public function testGetTaxWithProductPricesIncludeTax()
    {
        $entity = new Order();

        $this->taxationSettingsProvider->expects($this->once())
            ->method('isProductPricesIncludeTax')
            ->willReturn(true);

        $this->taxManager->expects($this->never())
            ->method('loadTax');

        $actualTaxAmount = $this->taxProvider->getTax($entity);
        $this->assertNull($actualTaxAmount);
    }
}
