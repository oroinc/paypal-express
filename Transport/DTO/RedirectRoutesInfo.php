<?php

namespace Oro\Bundle\PayPalExpressBundle\Transport\DTO;

/**
 * Represents information about PayPal REST API payment redirection routes.
 */
class RedirectRoutesInfo
{
    /**
     * Route where PayPal will redirect user after payment approve
     *
     * @var string
     */
    protected $successRoute;

    /**
     * Route where PayPal will redirect user after payment cancel
     *
     * @var string
     */
    protected $failedRoute;

    /**
     * @param string $successRoute
     * @param string $failedRoute
     */
    public function __construct($successRoute, $failedRoute)
    {
        $this->successRoute = $successRoute;
        $this->failedRoute  = $failedRoute;
    }

    /**
     * @return string
     */
    public function getSuccessRoute()
    {
        return $this->successRoute;
    }

    /**
     * @return string
     */
    public function getFailedRoute()
    {
        return $this->failedRoute;
    }
}
