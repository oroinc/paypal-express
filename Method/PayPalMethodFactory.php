<?php

namespace Oro\Bundle\PayPalExpressBundle\Method;

use Oro\Bundle\PayPalExpressBundle\Method\Config\PayPalExpressConfigInterface;

class PayPalMethodFactory implements PayPalMethodFactoryInterface
{
    /**
     * @var PayPalTransportFacade
     */
    protected $palTransportFacade;

    /**
     * @param PayPalTransportFacade $palTransportFacade
     */
    public function __construct(PayPalTransportFacade $palTransportFacade)
    {
        $this->palTransportFacade = $palTransportFacade;
    }

    /**
     * {@inheritdoc}
     */
    public function create(PayPalExpressConfigInterface $config)
    {
        return new PayPalMethod($this->palTransportFacade, $config);
    }
}
