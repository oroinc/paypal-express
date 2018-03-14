<?php

namespace Oro\Bundle\PayPalExpressBundle\Method\Config;

class PayPalExpressConfig implements PayPalExpressConfigInterface
{
    /**
     * @var string
     */
    protected $label;

    /**
     * @var string
     */
    protected $shortLabel;

    /**
     * @var string
     */
    protected $adminLabel;

    /**
     * Decrypted Client Id
     *
     * @var string
     */
    protected $clientId;

    /**
     * Decrypted Client Secret
     *
     * @var string
     */
    protected $clientSecret;

    /**
     * @var string
     */
    protected $paymentMethodIdentifier;

    /**
     * @var bool
     */
    protected $isSandbox;

    /**
     * @param string $label
     * @param string $shortLabel
     * @param string $adminLabel
     * @param string $clientId
     * @param string $clientSecret
     * @param string $paymentMethodIdentifier
     * @param bool   $isSandbox
     */
    public function __construct(
        $label,
        $shortLabel,
        $adminLabel,
        $clientId,
        $clientSecret,
        $paymentMethodIdentifier,
        $isSandbox
    ) {
        $this->label                   = $label;
        $this->shortLabel              = $shortLabel;
        $this->adminLabel              = $adminLabel;
        $this->clientId                = $clientId;
        $this->clientSecret            = $clientSecret;
        $this->isSandbox               = $isSandbox;
        $this->paymentMethodIdentifier = $paymentMethodIdentifier;
    }


    /**
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * @return string
     */
    public function getShortLabel()
    {
        return $this->shortLabel;
    }

    /**
     * @return string
     */
    public function getAdminLabel()
    {
        return $this->adminLabel;
    }

    /**
     * @return string
     */
    public function getClientId()
    {
        return $this->clientId;
    }

    /**
     * @return string
     */
    public function getClientSecret()
    {
        return $this->clientSecret;
    }

    /**
     * @return string
     */
    public function getPaymentMethodIdentifier()
    {
        return $this->paymentMethodIdentifier;
    }

    /**
     * @return bool
     */
    public function isSandbox()
    {
        return $this->isSandbox;
    }
}
