<?php

namespace Oro\Bundle\PayPalExpressBundle\Tests\Unit\Provider;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PayPalExpressBundle\Provider\TaxProvider;
use Oro\Bundle\PayPalExpressBundle\Tests\Unit\Stubs\FooPaymentEntityStub;
use Oro\Bundle\TaxBundle\Exception\TaxationDisabledException;
use Oro\Bundle\TaxBundle\Manager\TaxManager;
use Oro\Bundle\TaxBundle\Model\AbstractResultElement;
use Oro\Bundle\TaxBundle\Model\Result;
use Oro\Bundle\TaxBundle\Model\ResultElement;
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

    protected function setUp(): void
    {
        $this->taxManager = $this->createMock(TaxManager::class);
        $this->taxationSettingsProvider = $this->createMock(TaxationSettingsProvider::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->taxProvider = new TaxProvider($this->taxManager, $this->logger, $this->taxationSettingsProvider);
    }

    /**
     * @dataProvider getTaxTotalShippingTaxProvider
     */
    public function testGetTax(
        bool $isProductPricesIncludeTax,
        bool $isShippingRatesIncludeTax,
        int $totalTax,
        int $shippingTax,
        int $expectedTax
    ): void {
        $entity = new Order();

        $taxTotal = [ResultElement::TAX_AMOUNT => $totalTax, AbstractResultElement::CURRENCY => 'USD'];
        $taxShipping = [ResultElement::TAX_AMOUNT => $shippingTax, AbstractResultElement::CURRENCY => 'USD'];

        $taxResult = Result::jsonDeserialize([Result::TOTAL => $taxTotal, Result::SHIPPING => $taxShipping]);

        $this->taxationSettingsProvider->expects(self::any())
            ->method('isProductPricesIncludeTax')
            ->willReturn($isProductPricesIncludeTax);
        $this->taxationSettingsProvider->expects(self::any())
            ->method('isShippingRatesIncludeTax')
            ->willReturn($isShippingRatesIncludeTax);

        $this->taxManager->expects($this->once())
            ->method('loadTax')
            ->with($entity)
            ->willReturn($taxResult);

        $actualTaxAmount = $this->taxProvider->getTax($entity);
        $this->assertSame($expectedTax, $actualTaxAmount);
    }

    public function getTaxTotalShippingTaxProvider(): array
    {
        return [
            'Both product and shipping not included tax' => [
                'isProductPricesIncludeTax' => false,
                'isShippingRatesIncludeTax' => false,
                'totalTax' => 3,
                'shippingTax' => 1,
                'expectedTax' => 3
            ],
            'Shipping rate not included tax' => [
                'isProductPricesIncludeTax' => true,
                'isShippingRatesIncludeTax' => false,
                'totalTax' => 3,
                'shippingTax' => 1,
                'expectedTax' => 1
            ],
            'Product subtotal not included tax' => [
                'isProductPricesIncludeTax' => false,
                'isShippingRatesIncludeTax' => true,
                'totalTax' => 3,
                'shippingTax' => 1,
                'expectedTax' => 2
            ]
        ];
    }

    public function testGetTaxShouldRecoverFromAnyErrorLogItAndReturnZero(): void
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

    public function testGetTaxWithProductPricesIncludeTax(): void
    {
        $entity = new Order();

        $this->taxationSettingsProvider->expects($this->once())
            ->method('isProductPricesIncludeTax')
            ->willReturn(true);
        $this->taxationSettingsProvider->expects($this->once())
            ->method('isShippingRatesIncludeTax')
            ->willReturn(true);

        $this->taxManager->expects($this->never())
            ->method('loadTax');

        $actualTaxAmount = $this->taxProvider->getTax($entity);
        $this->assertNull($actualTaxAmount);
    }
}
