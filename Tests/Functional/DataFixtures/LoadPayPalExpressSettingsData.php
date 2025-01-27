<?php

namespace Oro\Bundle\PayPalExpressBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\PayPalExpressBundle\Entity\PayPalExpressSettings;
use Oro\Bundle\PayPalExpressBundle\Method\PaymentAction\Complete\AuthorizeOnCompleteAction;
use Oro\Bundle\SecurityBundle\Encoder\SymmetricCrypterInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class LoadPayPalExpressSettingsData extends AbstractFixture implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    private const array DATA = [
        [
            'clientId' => 'YxBU5pnHF6qNArI7Nt5yNqy4EgGWAU3K1w0eN6q77GZhNtu5cotSRWwZ',
            'clientSecret' => 'TxBU5pnHF6qNArI7Nt5yNqy4EgGWAU3K1w0eN6q77GZhNtu5cotSRWwZ',
            'label' => 'foo label',
            'shortLabel' => 'foo short label',
            'reference' => 'oro_paypal_express.settings.foo'
        ],
        [
            'clientId' => 'ZxBU5pnHF6qNArI7Nt5yNqy4EgGWAU3K1w0eN6q77GZhNtu5cotSRWwZ',
            'clientSecret' => 'LxBU5pnHF6qNArI7Nt5yNqy4EgGWAU3K1w0eN6q77GZhNtu5cotSRWwZ',
            'label' => 'bar label',
            'shortLabel' => 'bar short label',
            'reference' => 'oro_paypal_express.settings.bar'
        ],
        [
            'clientId' => 'KxBU5pnHF6qNArI7Nt5yNqy4EgGWAU3K1w0eN6q77GZhNtu5cotSRWwZ',
            'clientSecret' => 'NxBU5pnHF6qNArI7Nt5yNqy4EgGWAU3K1w0eN6q77GZhNtu5cotSRWwZ',
            'label' => 'baz label',
            'shortLabel' => 'baz short label',
            'reference' => 'oro_paypal_express.settings.baz'
        ]
    ];

    #[\Override]
    public function load(ObjectManager $manager): void
    {
        /** @var SymmetricCrypterInterface $encoder */
        $encoder = $this->container->get('oro_security.encoder.default');
        foreach (self::DATA as $item) {
            $settings = new PayPalExpressSettings();
            $settings->setPaymentAction(AuthorizeOnCompleteAction::NAME);
            $settings->setClientId($encoder->encryptData($item['clientId']));
            $settings->setClientSecret($encoder->encryptData($item['clientSecret']));
            $settings->setLabels(new ArrayCollection([$this->createLocalizedFallbackValue($item['label'])]));
            $settings->setShortLabels(new ArrayCollection([$this->createLocalizedFallbackValue($item['shortLabel'])]));

            $manager->persist($settings);
            $this->setReference($item['reference'], $settings);
        }
        $manager->flush();
    }

    private function createLocalizedFallbackValue(string $value): LocalizedFallbackValue
    {
        $localizedFallbackValue = new LocalizedFallbackValue();
        $localizedFallbackValue->setString($value);

        return $localizedFallbackValue;
    }
}
