<?php

namespace Oro\Bundle\PayPalExpressBundle\Method\Translator;

use Oro\Bundle\PayPalExpressBundle\Method\Config\PayPalExpressConfigInterface;
use Oro\Bundle\PayPalExpressBundle\Transport\DTO\ApiContextInfo;
use Oro\Bundle\PayPalExpressBundle\Transport\DTO\CredentialsInfo;

/**
 * Translates {@see PayPalExpressConfigInterface} to {@see ApiContextInfo}.
 */
class MethodConfigTranslator
{
    /**
     * @param PayPalExpressConfigInterface $config
     *
     * @return ApiContextInfo
     */
    public function getApiContextInfo(PayPalExpressConfigInterface $config)
    {
        $credentialsInfo = new CredentialsInfo($config->getClientId(), $config->getClientSecret());

        return new ApiContextInfo($credentialsInfo, $config->isSandbox());
    }
}
