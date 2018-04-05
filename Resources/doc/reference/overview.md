OroPayPalExpressBundle Documentation
==============================

# Overview #

### Table of Contents ###

- Overview
- [Structure](./structure.md)
- [Responsibilities and Extension points](./extension-points.md)

The bundle provides integration between OroCommerce and PayPal through [PayPal REST API](https://developer.paypal.com/docs/api/overview/).

The main difference between PayPal payment methods defined in OroPayPalBundle and OroPayPalExpressBundle is the way how they are communicating with PayPal.

While OroPayPalBundle is using [Payflow Gateway](https://developer.paypal.com/docs/classic/products/payflow-gateway/),
OroPayPalExpressBundle is using [PayPal REST API](https://developer.paypal.com/docs/api/overview/).

Both ways have their restrictions. You can see more details [here](https://developer.paypal.com/docs/classic/howto_product_matrix)

Under the hood, this bundle is using [PayPal PHP SDK](https://github.com/paypal/PayPal-PHP-SDK) to communicate with [PayPal REST API](https://developer.paypal.com/docs/api/overview/). 
You can find its documentation [here](http://paypal.github.io/PayPal-PHP-SDK/sample/).


