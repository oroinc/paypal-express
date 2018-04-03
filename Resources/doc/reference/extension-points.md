OroPayPalExpressBundle Documentation
==============================

# Responsibilities and Extension points #

### Table of Contents ###

- [Overview](./overview.md)
- [Structure](./structure.md)
- Responsibilities and Extension points

### 


### Anti-corruption layer ###

Responsible for communication with PayPal REST API through PayPalSDK and 
conversion of PayPalExpress DTO to PayPal SDK domain data objects.
Reverse translation was not implemented yet.

Anti-corruption layer includes: "PayPalExpressTransport", "PayPalClient" and "PayPalSDKObjectTranslator"
and couple of DTO objects.

The main goal of this layer is to hide actual way of communication with PayPalRest also it will help
to avoid backward incompatible changes in case if PayPalRest will be changed in the future.
It will be even more helpful in case if reverse integration will be implemented later.

##### PayPalExpressTransport #####

Responsible for interaction with "PayPalClient" and "PayPalSDKObjectTranslator", also responsible for hide PayPal SDK exceptions
from client code.

##### PayPalClient #####

Responsible for interaction with PayPal REST API through PayPal SDK.
Any operation which call PayPal REST resource in result should be placed here.

#### PayPalSDKObjectTranslator ####

Responsible for conversion Anti-corruption layer DTO's to PayPal SDK domain data objects


### Payment Actions layer ###

Responsible for handling of payment actions.
Payment Actions layer includes: "PaymentActionRegistry", "CompletePaymentActionRegistry", "PaymentActionExecutor",
"CompleteVirtualAction", couple of supported actions and complete actions.

##### PaymentActionExecutor #####

Responsible for execute payment action. Will use "PaymentActionRegistry" to get an appropriate payment action.

##### PaymentActionRegistry #####

Responsible for provide payment action by name. You could use this extension point to add your own payment action.
You could check possible payment action here \Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface.
Actions which is not presented in \Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface could be added,
but they will not be supported by default workflows.

##### CompleteVirtualAction #####

Responsible for receive complete payment action by name from "CompletePaymentActionRegistry" and proxy call to actual complete payment action
Complete payment action could be configured in PayPalExpress method integration settings.

##### CompletePaymentActionRegistry #####

Responsible for provide complete payment action by name.
You could use this extension point to add your own complete payment action. 
To register new complete payment action you will need to create new service with tag oro_paypal_express.complete_payment_action.
Complete payment action should implement PaymentActionInterface.
After new service will be created new action will be available in PayPalExpress method integration settings.
By default "Authorize" and "Authorize and capture" actions are implemented.

