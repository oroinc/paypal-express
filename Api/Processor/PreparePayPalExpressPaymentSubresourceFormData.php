<?php

namespace Oro\Bundle\PayPalExpressBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Form\Extension\ValidationExtension;
use Oro\Bundle\ApiBundle\Processor\Subresource\ChangeSubresourceContext;
use Oro\Bundle\PayPalExpressBundle\Api\Model\PayPalExpressPaymentRequest;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Prepares the form data for the checkout PayPal Express payment sub-resource.
 */
class PreparePayPalExpressPaymentSubresourceFormData implements ProcessorInterface
{
    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var ChangeSubresourceContext $context */

        if ($context->hasResult()) {
            // the form data are already prepared
            return;
        }

        $associationName = $context->getAssociationName();
        $context->setRequestData([$associationName => $context->getRequestData()]);
        $context->setResult([$associationName => new PayPalExpressPaymentRequest()]);

        $formOptions = $context->getFormOptions() ?? [];
        $formOptions[ValidationExtension::ENABLE_FULL_VALIDATION] = true;
        $context->setFormOptions($formOptions);
    }
}
