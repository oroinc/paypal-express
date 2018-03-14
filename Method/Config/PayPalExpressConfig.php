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
     * @var bool
     */
    protected $isSandbox;

    /**
     * @param string $label
     * @param string $shortLabel
     * @param string $adminLabel
     * @param string $clientId
     * @param string $clientSecret
     * @param bool   $isSandbox
     */
    public function __construct($label, $shortLabel, $adminLabel, $clientId, $clientSecret, $isSandbox)
    {
        $this->label        = $label;
        $this->shortLabel   = $shortLabel;
        $this->adminLabel   = $adminLabel;
        $this->clientId     = $clientId;
        $this->clientSecret = $clientSecret;
        $this->isSandbox    = $isSandbox;
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
     * @return bool
     */
    public function isSandbox()
    {
        return $this->isSandbox;
    }

    /**
     * @return string
     */
    public function getPaymentMethodIdentifier()
    {
        // TODO: Implement getPaymentMethodIdentifier() method.
    }
}
