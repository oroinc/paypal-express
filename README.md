# PayPal Express OroCommerce Integration

This extensions provides an implementation of PayPal Express payment integration that can be used in European countries (please check PayPal website in your country to confirm availability of PayPal Express in your country).

## Installation

This extension can be added to an existing installation of OroCommerce:

Use composer to add the package code:

```
composer require oro/paypal-express
```

Perform the installation:

```
php bin/console oro:platform:update --env=prod
```

## Documentation

Detailed instructions on how to enable and configure PayPal Express integration are available in the [OroCommerce documentation](https://oroinc.com/orocommerce/doc/current/configuration-guide/payment/payment-methods/paypal-express).
Documentation for developers is located in [this bundle's resources folder](./Resources/doc/index.md).
