<?php

namespace Oro\Bundle\PayPalExpressBundle\Tests\Unit\Provider;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PayPalExpressBundle\Provider\TaxProvider;
use Oro\Bundle\PayPalExpressBundle\Tests\Unit\Stubs\FooPaymentEntityStub;
use Oro\Bundle\TaxBundle\Exception\TaxationDisabledException;
use Oro\Bundle\TaxBundle\Manager\TaxManager;
use Oro\Bundle\TaxBundle\Model\Result;
use Oro\Bundle\TaxBundle\Model\ResultElement;

use Psr\Log\LoggerInterface;

class TaxProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var TaxProvider
     */
    protected $taxProvider;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|LoggerInterface
     */
    protected $logger;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|TaxManager
     */
    protected $taxManager;

    protected function setUp()
    {
        $this->taxManager = $this->createMock(TaxManager::class);

        $this->logger = $this->createMock(LoggerInterface::class);
        
        $this->taxProvider = new TaxProvider($this->taxManager, $this->logger);
    }

    public function testGetTax()
    {
        $entity = new Order();

        $expectedTaxAmount = 2;

        $taxTotal = [ResultElement::TAX_AMOUNT => $expectedTaxAmount, ResultElement::CURRENCY => 'USD'];

        $taxResult = Result::jsonDeserialize([Result::TOTAL => $taxTotal]);

        $this->taxManager->expects($this->once())
            ->method('loadTax')
            ->with($entity)
            ->willReturn($taxResult);

        $actualTaxAmount = $this->taxProvider->getTax($entity);
        $this->assertEquals($expectedTaxAmount, $actualTaxAmount);
    }

    public function testGetTaxShouldRecoverFromAnyErrorLogItAndReturnZero()
    {
        $entity = new FooPaymentEntityStub();

        $id = 42;
        $entity->setId($id);

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
        $this->assertEquals(0, $actualTaxAmount);
    }
}
