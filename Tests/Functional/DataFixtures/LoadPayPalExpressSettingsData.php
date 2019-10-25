<?php

namespace Oro\Bundle\PayPalExpressBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\PayPalExpressBundle\Entity\PayPalExpressSettings;
use Oro\Bundle\SecurityBundle\Encoder\Mcrypt;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class LoadPayPalExpressSettingsData extends AbstractFixture implements ContainerAwareInterface
{
    /**
     * @var array
     */
    protected $data = [
        [
            'clientId'     => 'YxBU5pnHF6qNArI7Nt5yNqy4EgGWAU3K1w0eN6q77GZhNtu5cotSRWwZ',
            'clientSecret' => 'TxBU5pnHF6qNArI7Nt5yNqy4EgGWAU3K1w0eN6q77GZhNtu5cotSRWwZ',
            'label'        => 'foo label',
            'shortLabel'   => 'foo short label',
            'reference'    => 'oro_paypal_express.settings.foo'
        ],
        [
            'clientId'     => 'ZxBU5pnHF6qNArI7Nt5yNqy4EgGWAU3K1w0eN6q77GZhNtu5cotSRWwZ',
            'clientSecret' => 'LxBU5pnHF6qNArI7Nt5yNqy4EgGWAU3K1w0eN6q77GZhNtu5cotSRWwZ',
            'label'        => 'bar label',
            'shortLabel'   => 'bar short label',
            'reference'    => 'oro_paypal_express.settings.bar'
        ],
        [
            'clientId'     => 'KxBU5pnHF6qNArI7Nt5yNqy4EgGWAU3K1w0eN6q77GZhNtu5cotSRWwZ',
            'clientSecret' => 'NxBU5pnHF6qNArI7Nt5yNqy4EgGWAU3K1w0eN6q77GZhNtu5cotSRWwZ',
            'label'        => 'baz label',
            'shortLabel'   => 'baz short label',
            'reference'    => 'oro_paypal_express.settings.baz'
        ],
    ];

    /**
     * @var Mcrypt
     */
    protected $encoder;

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        foreach ($this->data as $item) {
            $settings = new PayPalExpressSettings();
            $settings->setClientId($this->encoder->encryptData($item['clientId']));
            $settings->setClientSecret($this->encoder->encryptData($item['clientSecret']));
            $label = new LocalizedFallbackValue();
            $label->setString($item['label']);
            $label->setText($item['label']);
            $settings->setLabels(new ArrayCollection([$label]));
            $shortLabels = new LocalizedFallbackValue();
            $shortLabels->setString($item['shortLabel']);
            $shortLabels->setText($item['shortLabel']);
            $settings->setShortLabels(new ArrayCollection([$shortLabels]));

            $manager->persist($settings);
            $this->setReference($item['reference'], $settings);
        }

        $manager->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->encoder = $container->get('oro_security.encoder.default');
    }
}
