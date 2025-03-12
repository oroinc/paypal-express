# Oro\Bundle\CheckoutBundle\Entity\Checkout

## SUBRESOURCES

### paymentPayPalExpress

#### add_subresource

Execute checkout payment with PayPal Express payment method.

The PayPal Express payment method requires the client to be redirected to an external payment provider's URL. The initial request to this subresource will start the payment process. After the payment is completed, PayPal will redirect the client back to the application's URL. This should prompt a second request to the subresource to finalize the checkout or to manage any payment errors. If the payment is successful, the Order resource will be returned as a response.

Follow the [Storefront Checkout API Guide](https://doc.oroinc.com/api/checkout-api/#paypal-express-payment) for more details about the checkout process using the API.

{@request:json_api}
Example of the request:

```JSON
{
  "meta": {
      "successUrl": "https://my-application.ltd/checkout/payment/paypal-express/success",
      "failureUrl": "https://my-application.ltd/checkout/payment/paypal-express/failure"
  }
}
```

Example of a response when the checkout is not ready for payment:
```JSON
{
    "errors": [
        {
            "status": "400",
            "title": "payment constraint",
            "detail": "The checkout is not ready for payment.",
            "meta": {
                "validatePaymentUrl": "https://oro-application.ltd/api/checkouts/1/payment"
            }
        }
    ]
}
```

Example of a response when initial payment request is sent:
```JSON
{
    "errors": [
        {
            "status": "400",
            "title": "payment action constraint",
            "detail": "Payment should be completed on the merchant's page, follow the link provided in the error details.",
            "meta": {
                "data": {
                    "paymentMethod": "oro_paypal_express_1",
                    "paymentMethodSupportsValidation": false,
                    "errorUrl": "http://oro-application.ltd/payment/callback/error/e111111c-1111-1111-1abc-11dc1d1111f1",
                    "returnUrl": "http://oro-application.ltd/payment/callback/return/e111111c-1111-1111-1abc-11dc1d1111f1",
                    "failureUrl": "https://my-application.ltd/checkout/payment/paypal-express/failure",
                    "successUrl": "https://my-application.ltd/checkout/payment/paypal-express/success",
                    "purchaseRedirectUrl": "http://paypal-express-domain.com/redirectUrl"
                }
            }
        }
    ]
}
```

Example of a response when payment is still in progress:
```JSON
{
    "errors": [
        {
            "status": "400",
            "title": "payment status constraint",
            "detail": "Payment is being processed. Please follow the payment provider's instructions to complete it."
        }
    ]
}
```

Example of a response when payment fails with an error:
```JSON
{
    "errors": [
        {
            "status": "400",
            "title": "payment constraint",
            "detail": "Payment failed, please try again or select a different payment method."
        }
    ]
}
```
{@/request}


# Oro\Bundle\PayPalExpressBundle\Api\Model\PayPalExpressPaymentRequest

## FIELDS

### successUrl

The URL where PayPal Express should direct customers after a successful payment completion.

### failureUrl

The URL to which PayPal Express should direct customers when a payment fails.
