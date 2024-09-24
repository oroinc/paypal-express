<?php

namespace Oro\Bundle\PayPalExpressBundle\Entity;

use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\IntegrationBundle\Entity\Transport;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\PayPalExpressBundle\Entity\Repository\PayPalExpressSettingsRepository;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * Represents entity for PayPal Express payment method integration settings.
 */
#[ORM\Entity(repositoryClass: PayPalExpressSettingsRepository::class)]
class PayPalExpressSettings extends Transport
{
    const CLIENT_ID_SETTING_KEY = 'client_id';
    const CLIENT_SECRET_SETTING_KEY = 'client_secret';
    const SANDBOX_MOD_SETTING_KEY = 'test_mod';
    const LABELS_SETTING_KEY = 'labels';
    const SHORT_LABELS_SETTING_KEY = 'short_labels';
    const PAYMENT_ACTION_KEY = 'payment_action';

    /**
     * @var ParameterBag
     */
    protected $settings;

    #[ORM\Column(name: 'pp_express_client_id', type: Types::STRING, length: 255, nullable: false)]
    protected ?string $clientId = null;

    #[ORM\Column(name: 'pp_express_client_secret', type: Types::STRING, length: 255, nullable: false)]
    protected ?string $clientSecret = null;

    #[ORM\Column(name: 'pp_express_sandbox_mode', type: Types::BOOLEAN, options: ['default' => false])]
    protected ?bool $sandboxMode = false;

    /**
     * @var Collection<int, LocalizedFallbackValue>
     */
    #[ORM\ManyToMany(targetEntity: LocalizedFallbackValue::class, cascade: ['ALL'], orphanRemoval: true)]
    #[ORM\JoinTable(name: 'oro_pp_express_label')]
    #[ORM\JoinColumn(name: 'transport_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[ORM\InverseJoinColumn(name: 'localized_value_id', referencedColumnName: 'id', unique: true, onDelete: 'CASCADE')]
    protected ?Collection $labels = null;

    /**
     * @var Collection<int, LocalizedFallbackValue>
     */
    #[ORM\ManyToMany(targetEntity: LocalizedFallbackValue::class, cascade: ['ALL'], orphanRemoval: true)]
    #[ORM\JoinTable(name: 'oro_pp_express_short_label')]
    #[ORM\JoinColumn(name: 'transport_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[ORM\InverseJoinColumn(name: 'localized_value_id', referencedColumnName: 'id', unique: true, onDelete: 'CASCADE')]
    protected ?Collection $shortLabels = null;

    #[ORM\Column(name: 'pp_express_payment_action', type: Types::STRING, length: 255, nullable: false)]
    protected ?string $paymentAction = null;

    /**
     * @return string
     */
    public function getClientId()
    {
        return $this->clientId;
    }

    /**
     * @param string $clientId
     */
    public function setClientId($clientId)
    {
        $this->clientId = $clientId;
    }

    /**
     * @return string
     */
    public function getClientSecret()
    {
        return $this->clientSecret;
    }

    /**
     * @param string $clientSecret
     */
    public function setClientSecret($clientSecret)
    {
        $this->clientSecret = $clientSecret;
    }

    /**
     * @return bool
     */
    public function isSandboxMode()
    {
        return $this->sandboxMode;
    }

    /**
     * @param bool $sandboxMode
     */
    public function setSandboxMode($sandboxMode)
    {
        $this->sandboxMode = $sandboxMode;
    }

    /**
     * @return Collection|LocalizedFallbackValue[]
     */
    public function getLabels()
    {
        return $this->labels;
    }

    /**
     * @param Collection|LocalizedFallbackValue[] $labels
     */
    public function setLabels($labels)
    {
        $this->labels = $labels;
    }

    /**
     * @return Collection|LocalizedFallbackValue[]
     */
    public function getShortLabels()
    {
        return $this->shortLabels;
    }

    /**
     * @param Collection|LocalizedFallbackValue[] $shortLabels
     */
    public function setShortLabels($shortLabels)
    {
        $this->shortLabels = $shortLabels;
    }

    /**
     * @return string
     */
    public function getPaymentAction()
    {
        return $this->paymentAction;
    }

    /**
     * @param string $paymentAction
     */
    public function setPaymentAction($paymentAction)
    {
        $this->paymentAction = $paymentAction;
    }

    /**
     * @return ParameterBag
     */
    #[\Override]
    public function getSettingsBag()
    {
        if (null === $this->settings) {
            $this->settings = new ParameterBag(
                [
                    self::CLIENT_ID_SETTING_KEY     => $this->getClientId(),
                    self::CLIENT_SECRET_SETTING_KEY => $this->getClientSecret(),
                    self::SANDBOX_MOD_SETTING_KEY   => $this->isSandboxMode(),
                    self::LABELS_SETTING_KEY        => $this->getLabels(),
                    self::SHORT_LABELS_SETTING_KEY  => $this->getShortLabels(),
                    self::PAYMENT_ACTION_KEY        => $this->getPaymentAction(),
                ]
            );
        }

        return $this->settings;
    }
}
