OroPayPalExpressBundle Documentation
==============================

# Overview #

### Table of Contents ###

- Overview
- [Structure](./structure.md)
- [Responsibilities and Extension points](./extension-points.md)


Bundle provides an integration between OroCommerce and PayPal through PayPal REST Api
New Payment method was defined to allow buyers in OroCommerce pay using PayPal.

The main difference from payment methods defined in OroPayPalBundle 
is the way how it is communicated with PayPal

OroPayPalBundle uses Payflow Gateway, instead this bundle use PayPal Rest API to communicate with PayPal.

Both way has theirs restrictions. You can see more details [here](https://developer.paypal.com/docs/classic/howto_product_matrix)

Under the hood this bundle use PayPal SDK to communicate with PayPal REST API, you can find its documentation [here](http://paypal.github.io/PayPal-PHP-SDK/sample/)


