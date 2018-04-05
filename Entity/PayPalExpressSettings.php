<?php

namespace Oro\Bundle\PayPalExpressBundle\Entity;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\IntegrationBundle\Entity\Transport;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * Represents entity for PayPal Express payment method integration settings.
 *
 * @ORM\Entity(repositoryClass="Oro\Bundle\PayPalExpressBundle\Entity\Repository\PayPalExpressSettingsRepository")
 */
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

    /**
     * @var string
     *
     * @ORM\Column(name="pp_express_client_id", type="string", length=255, nullable=false)
     */
    protected $clientId;

    /**
     * @var string
     *
     * @ORM\Column(name="pp_express_client_secret", type="string", length=255, nullable=false)
     */
    protected $clientSecret;

    /**
     * @var boolean
     *
     * @ORM\Column(name="pp_express_sandbox_mode", type="boolean", options={"default"=false})
     */
    protected $sandboxMode = false;


    /**
     * @var Collection|LocalizedFallbackValue[]
     *
     * @ORM\ManyToMany(
     *      targetEntity="Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue",
     *      cascade={"ALL"},
     *      orphanRemoval=true
     * )
     * @ORM\JoinTable(
     *      name="oro_pp_express_label",
     *      joinColumns={
     *          @ORM\JoinColumn(name="transport_id", referencedColumnName="id", onDelete="CASCADE")
     *      },
     *      inverseJoinColumns={
     *          @ORM\JoinColumn(name="localized_value_id", referencedColumnName="id", onDelete="CASCADE", unique=true)
     *      }
     * )
     */
    protected $labels;

    /**
     * @var Collection|LocalizedFallbackValue[]
     *
     * @ORM\ManyToMany(
     *      targetEntity="Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue",
     *      cascade={"ALL"},
     *      orphanRemoval=true
     * )
     * @ORM\JoinTable(
     *      name="oro_pp_express_short_label",
     *      joinColumns={
     *          @ORM\JoinColumn(name="transport_id", referencedColumnName="id", onDelete="CASCADE")
     *      },
     *      inverseJoinColumns={
     *          @ORM\JoinColumn(name="localized_value_id", referencedColumnName="id", onDelete="CASCADE", unique=true)
     *      }
     * )
     */
    protected $shortLabels;

    /**
     * @var string
     *
     * @ORM\Column(name="pp_express_payment_action", type="string", length=255, nullable=false)
     */
    protected $paymentAction;

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
