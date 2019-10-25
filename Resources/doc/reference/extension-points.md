OroPayPalExpressBundle Documentation
==============================

# Responsibilities and Extension points #

### Table of Contents ###

- [Overview](./overview.md)
- [Structure](./structure.md)
- Responsibilities and Extension points

### 

### Anti-Corruption layer ###

Responsible for communication with [PayPal REST API](https://developer.paypal.com/docs/api/overview/) through 
[PayPal PHP SDK](https://github.com/paypal/PayPal-PHP-SDK) and conversion of OroPayPalExpress DTO to PayPal SDK domain data objects.
Note, a reverse translation is not implemented.

Anti-corruption layer includes: 
[PayPalExpressTransport](../../../Transport/PayPalExpressTransport.php),
[PayPalClient](../../../Transport/PayPalClient.php), 
[PayPalSDKObjectTranslator](../../../Transport/PayPalSDKObjectTranslator.php),
and couple of DTO objects.

The primary goal of this layer is to hide actual way of communication with PayPal REST API. Also, it will help
to avoid backward incompatible changes in case if PayPal will change its REST API in the future.

And finally, it will be helpful for implementation of the reverse side integration with PayPal.

##### PayPalExpressTransport #####

Responsible for interaction with 
[PayPalClient](../../../Transport/PayPalClient.php) and [PayPalSDKObjectTranslator](../../../Transport/PayPalSDKObjectTranslator.php).
Also, responsible for hiding and wrapping PayPal SDK exceptions in client code of OroCommerce.

##### PayPalClient #####

Responsible for interaction with [PayPal REST API](https://developer.paypal.com/docs/api/overview/) 
through [PayPal PHP SDK](https://github.com/paypal/PayPal-PHP-SDK).

Any operation which calls PayPal REST API resource in result should be added in the client.

#### PayPalSDKObjectTranslator ####

Responsible for conversion Anti-corruption layer DTO's to PayPal PHP SDK domain data objects.

### Payment Actions layer ###

Responsible for handling of payment actions.
Payment Actions layer includes: 
[PaymentActionRegistry](../../../Method/PaymentAction/PaymentActionRegistry.php), 
[CompletePaymentActionRegistry](../../../Method/PaymentAction/Complete/CompletePaymentActionRegistry.php),
[PaymentActionExecutor](../../../Method/PaymentAction/PaymentActionExecutor.php),
[CompleteVirtualAction](../../../Method/PaymentAction/CompleteVirtualAction.php),
and supported actions and complete actions implementing [PaymentActionInterface](../../../Method/PaymentAction/PaymentActionInterface.php).

##### PaymentActionExecutor #####

Responsible for execute payment action. It uses 
[CompletePaymentActionRegistry](../../../Method/PaymentAction/Complete/CompletePaymentActionRegistry.php) 
to get an appropriate payment action.

##### PaymentActionRegistry #####

Responsible for providing payment action by name. It can be used as an extension point to add custom payment action.
Possible payment action could be found in constants of `\Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface`.
Actions which are not presented in the interface could be added, but they will not be supported by default workflows.

##### CompleteVirtualAction #####

Responsible for receiving complete payment action by name from 
[CompletePaymentActionRegistry](../../../Method/PaymentAction/Complete/CompletePaymentActionRegistry.php) 
and delegating call to actual complete payment action. 

##### CompletePaymentActionRegistry #####

Responsible for providing complete payment action by name.

Complete payment action is configured in PayPal Express method integration settings in a field with label "Payment Action". 
By default, 2 possible complete actions are available: 
- Authorize ([AuthorizeAndCaptureAction](../../../Method/PaymentAction/Complete/AuthorizeAndCaptureAction.php))
- Authorize and capture ([AuthorizeOnCompleteAction](../../../Method/PaymentAction/Complete/AuthorizeOnCompleteAction.php))

To register a new complete payment action, create a new service with tag `oro_paypal_express.complete_payment_action` 
(class of the service must implement [PaymentActionInterface](../../../Method/PaymentAction/PaymentActionInterface.php)).

After a new service is registered, it will be available in integration settings of PayPal Express payment method.
