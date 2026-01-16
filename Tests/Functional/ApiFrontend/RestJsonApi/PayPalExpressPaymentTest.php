<?php

namespace Oro\Bundle\PayPalExpressBundle\Tests\Functional\ApiFrontend\RestJsonApi;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Tests\Functional\ApiFrontend\DataFixtures\LoadCheckoutData;
use Oro\Bundle\CustomerBundle\Tests\Functional\ApiFrontend\DataFixtures\LoadAdminCustomerUserData;
use Oro\Bundle\FrontendBundle\Tests\Functional\ApiFrontend\FrontendRestJsonApiTestCase;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Oro\Bundle\PaymentBundle\Provider\PaymentTransactionProvider;
use Oro\Bundle\PayPalExpressBundle\Tests\Functional\DataFixtures\LoadPayPalExpressPaymentMethodData;
use Symfony\Component\HttpFoundation\Response;

/**
 * @dbIsolationPerTest
 */
class PayPalExpressPaymentTest extends FrontendRestJsonApiTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([
            LoadAdminCustomerUserData::class,
            LoadCheckoutData::class,
            LoadPayPalExpressPaymentMethodData::class
        ]);
    }

    private function getPaymentMethod(int $checkoutId, string $type = 'oro_paypal_express'): string
    {
        $response = $this->getSubresource(
            ['entity' => 'checkouts', 'id' => (string)$checkoutId, 'association' => 'availablePaymentMethods']
        );
        $responseData = self::jsonToArray($response->getContent());
        foreach ($responseData['data'] as $paymentMethod) {
            if (str_starts_with($paymentMethod['id'], $type)) {
                return $paymentMethod['id'];
            }
        }
        throw new \RuntimeException(sprintf('The "%s" payment method was not found.', $type));
    }

    private function getShippingMethod(int $checkoutId): array
    {
        $response = $this->getSubresource(
            ['entity' => 'checkouts', 'id' => (string)$checkoutId, 'association' => 'availableShippingMethods']
        );
        $responseData = self::jsonToArray($response->getContent());
        $shippingMethodData = $responseData['data'][0];
        $shippingMethodTypeData = reset($shippingMethodData['attributes']['types']);

        return [$shippingMethodData['id'], $shippingMethodTypeData['id']];
    }

    private function prepareCheckoutForPayment(
        int $checkoutId,
        string $paymentType = 'oro_paypal_express',
        ?string $paymentMethod = null,
        ?string $shippingMethod = null,
        ?string $shippingMethodType = null
    ): void {
        if (null === $paymentMethod) {
            $paymentMethod = $this->getPaymentMethod($checkoutId, $paymentType);
        }
        if (null === $shippingMethod) {
            [$shippingMethod, $shippingMethodType] = $this->getShippingMethod($checkoutId);
        }
        $this->patch(
            ['entity' => 'checkouts', 'id' => (string)$checkoutId],
            [
                'data' => [
                    'type' => 'checkouts',
                    'id' => (string)$checkoutId,
                    'attributes' => [
                        'paymentMethod' => $paymentMethod,
                        'shippingMethod' => $shippingMethod,
                        'shippingMethodType' => $shippingMethodType
                    ]
                ]
            ]
        );
    }

    private function sendPaymentRequest(int $checkoutId): Response
    {
        return $this->postSubresource(
            ['entity' => 'checkouts', 'id' => (string)$checkoutId, 'association' => 'paymentPayPalExpress'],
            [
                'meta' => [
                    'successUrl' => 'http://example.com/success',
                    'failureUrl' => 'http://example.com/failure'
                ]
            ],
            [],
            false
        );
    }

    private function sendPaymentTransactionRequest(
        Response $response,
        string $urlType,
        string $additionalRequestParameters = ''
    ): void {
        $responseData = self::jsonToArray($response->getContent());
        $url = $responseData['errors'][0]['meta']['data'][$urlType];
        self::getClient()->request('GET', $url . $additionalRequestParameters);
        self::assertResponseStatusCodeEquals($this->client->getResponse(), 302);
    }

    private function setPaymentTransactionStatus(int $checkoutId, string $action): void
    {
        /** @var Checkout $checkout */
        $checkout = $this->getEntityManager()->find(Checkout::class, $checkoutId);
        /** @var PaymentTransactionProvider $paymentTransactionProvider */
        $paymentTransactionProvider = self::getContainer()->get('oro_payment.provider.payment_transaction');
        $paymentTransaction = $paymentTransactionProvider->getPaymentTransaction($checkout->getOrder());
        $paymentTransaction->setAction($action);
        $paymentTransaction->setSuccessful(true);
        $paymentTransaction->setActive(true);
        $paymentTransaction->setReference('test');

        $paymentTransactionProvider->savePaymentTransaction($paymentTransaction);
    }

    public function testTryToPayPalExpressPaymentWhenSuccessUrlIsNotProvided(): void
    {
        $checkoutId = $this->getReference('checkout.ready_for_completion')->getId();
        $this->prepareCheckoutForPayment($checkoutId);
        $response = $this->postSubresource(
            ['entity' => 'checkouts', 'id' => (string)$checkoutId, 'association' => 'paymentPayPalExpress'],
            [
                'meta' => [
                    'failureUrl' => 'http://example.com/failure'
                ]
            ],
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title' => 'not blank constraint',
                'detail' => 'This value should not be blank.',
                'source' => ['pointer' => '/meta/successUrl']
            ],
            $response
        );
    }

    public function testTryToPayPalExpressPaymentWhenFailureUrlIsNotProvided(): void
    {
        $checkoutId = $this->getReference('checkout.ready_for_completion')->getId();
        $this->prepareCheckoutForPayment($checkoutId);
        $response = $this->postSubresource(
            ['entity' => 'checkouts', 'id' => (string)$checkoutId, 'association' => 'paymentPayPalExpress'],
            [
                'meta' => [
                    'successUrl' => 'http://example.com/success'
                ]
            ],
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title' => 'not blank constraint',
                'detail' => 'This value should not be blank.',
                'source' => ['pointer' => '/meta/failureUrl']
            ],
            $response
        );
    }

    public function testTryToPayPalExpressPaymentWhenEmptyPaymentRequest(): void
    {
        $checkoutId = $this->getReference('checkout.ready_for_completion')->getId();
        $this->prepareCheckoutForPayment($checkoutId);
        $response = $this->postSubresource(
            ['entity' => 'checkouts', 'id' => (string)$checkoutId, 'association' => 'paymentPayPalExpress'],
            [],
            [],
            false
        );
        $this->assertResponseValidationErrors(
            [
                [
                    'title' => 'not blank constraint',
                    'detail' => 'This value should not be blank.',
                    'source' => ['pointer' => '/meta/successUrl']
                ],
                [
                    'title' => 'not blank constraint',
                    'detail' => 'This value should not be blank.',
                    'source' => ['pointer' => '/meta/failureUrl']
                ]
            ],
            $response
        );
    }

    public function testTryToPayPalExpressPaymentWithNullValuesForRequiredParameters(): void
    {
        $checkoutId = $this->getReference('checkout.ready_for_completion')->getId();
        $this->prepareCheckoutForPayment($checkoutId);
        $response = $this->postSubresource(
            ['entity' => 'checkouts', 'id' => (string)$checkoutId, 'association' => 'paymentPayPalExpress'],
            [
                'meta' => [
                    'successUrl' => null,
                    'failureUrl' => null
                ]
            ],
            [],
            false
        );
        $this->assertResponseValidationErrors(
            [
                [
                    'title' => 'not blank constraint',
                    'detail' => 'This value should not be blank.',
                    'source' => ['pointer' => '/meta/successUrl']
                ],
                [
                    'title' => 'not blank constraint',
                    'detail' => 'This value should not be blank.',
                    'source' => ['pointer' => '/meta/failureUrl']
                ]
            ],
            $response
        );
    }

    public function testTryToPayPalExpressPaymentForNotReadyToPaymentCheckout(): void
    {
        $checkoutId = $this->getReference('checkout.ready_for_completion')->getId();
        $response = $this->postSubresource(
            ['entity' => 'checkouts', 'id' => (string)$checkoutId, 'association' => 'paymentPayPalExpress'],
            [],
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title' => 'payment constraint',
                'detail' => 'The checkout is not ready for payment.',
                'meta' => [
                    'validatePaymentUrl' => $this->getUrl(
                        'oro_frontend_rest_api_subresource',
                        ['entity' => 'checkouts', 'id' => (string)$checkoutId, 'association' => 'payment'],
                        true
                    )
                ]
            ],
            $response
        );
    }

    public function testTryToPayPalExpressPaymentForNotSupportedPaymentMethod(): void
    {
        $checkoutId = $this->getReference('checkout.ready_for_completion')->getId();
        $this->prepareCheckoutForPayment($checkoutId, 'payment_term');
        $response = $this->postSubresource(
            ['entity' => 'checkouts', 'id' => (string)$checkoutId, 'association' => 'paymentPayPalExpress'],
            [],
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title' => 'access denied exception',
                'detail' => 'The payment method is not supported.'
            ],
            $response,
            Response::HTTP_FORBIDDEN
        );
    }

    public function testPayPalExpressPaymentForInitialRequest(): void
    {
        $checkoutId = $this->getReference('checkout.ready_for_completion')->getId();
        $this->prepareCheckoutForPayment($checkoutId);
        $response = $this->sendPaymentRequest($checkoutId);
        // redirect URL is set by the mock
        // @see Oro\Bundle\PayPalExpressBundle\Tests\Functional\Environment\Transport\PayPalClientMock
        $this->assertResponseValidationError(
            [
                'title' => 'payment action constraint',
                'detail' => 'Payment should be completed on the merchant\'s page,'
                    . ' follow the link provided in the error details.',
                'meta' => [
                    'data' => [
                        'purchaseRedirectUrl' => 'http://paypal.com/redirect'
                    ]
                ]
            ],
            $response
        );
    }

    public function testPayPalExpressPaymentWhenPaymentError(): void
    {
        $checkoutId = $this->getReference('checkout.ready_for_completion')->getId();
        $this->prepareCheckoutForPayment($checkoutId);
        $paymentRequestResponse = $this->sendPaymentRequest($checkoutId);

        // check that it's not possible to alter payment in progress process
        $response = $this->postSubresource(
            ['entity' => 'checkouts', 'id' => (string)$checkoutId, 'association' => 'paymentPayPalExpress'],
            [
                'meta' => [
                    'successUrl' => 'http://example.com/success',
                    'failureUrl' => 'http://example.com/failure'
                ]
            ],
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title' => 'payment status constraint',
                'detail' => 'Payment is being processed.'
                    . ' Please follow the payment provider\'s instructions to complete.'
            ],
            $response
        );
        /** @var Checkout $checkout */
        $checkout = $this->getEntityManager()->find(Checkout::class, $checkoutId);
        self::assertTrue($checkout->isPaymentInProgress());
        self::assertNotNull($checkout->getOrder());

        // emulate PayPal notification to error URL
        $this->sendPaymentTransactionRequest($paymentRequestResponse, 'errorUrl');

        // make API payment request after payment failed with error
        $response = $this->postSubresource(
            ['entity' => 'checkouts', 'id' => (string)$checkoutId, 'association' => 'paymentPayPalExpress'],
            [
                'meta' => [
                    'successUrl' => 'http://example.com/success',
                    'failureUrl' => 'http://example.com/failure'
                ]
            ],
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title' => 'payment constraint',
                'detail' => 'Payment failed, please try again or select a different payment method.'
            ],
            $response
        );
        /** @var Checkout $checkout */
        $checkout = $this->getEntityManager()->find(Checkout::class, $checkoutId);
        self::assertFalse($checkout->isPaymentInProgress());
        self::assertNull($checkout->getOrder());
    }

    public function testPayPalExpressPaymentWhenPaymentSuccessful(): void
    {
        $checkoutId = $this->getReference('checkout.ready_for_completion')->getId();
        $paymentMethod = $this->getPaymentMethod($checkoutId);
        [$shippingMethod, $shippingMethodType] = $this->getShippingMethod($checkoutId);
        $this->prepareCheckoutForPayment(
            $checkoutId,
            'oro_paypal_express',
            $paymentMethod,
            $shippingMethod,
            $shippingMethodType
        );
        $response = $this->sendPaymentRequest($checkoutId);
        $this->assertResponseValidationError(
            [
                'title' => 'payment action constraint',
                'detail' => 'Payment should be completed on the merchant\'s page,'
                    . ' follow the link provided in the error details.',
                'meta' => [
                    'data' => [
                        'purchaseRedirectUrl' => 'http://paypal.com/redirect'
                    ]
                ]
            ],
            $response
        );

        // check that it's not possible to alter payment in progress process
        $response = $this->postSubresource(
            ['entity' => 'checkouts', 'id' => (string)$checkoutId, 'association' => 'paymentPayPalExpress'],
            [
                'meta' => [
                    'successUrl' => 'http://example.com/success',
                    'failureUrl' => 'http://example.com/failure'
                ]
            ],
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title' => 'payment status constraint',
                'detail' => 'Payment is being processed.'
                    . ' Please follow the payment provider\'s instructions to complete.'
            ],
            $response
        );

        // set payment transaction reference to be able to emulate payment completion.
        $this->setPaymentTransactionStatus($checkoutId, PaymentMethodInterface::AUTHORIZE);

        // make API payment request after success payment to finish checkout process
        $response = $this->postSubresource(
            ['entity' => 'checkouts', 'id' => (string)$checkoutId, 'association' => 'paymentPayPalExpress'],
            [
                'meta' => [
                    'successUrl' => 'http://example.com/success',
                    'failureUrl' => 'http://example.com/failure'
                ]
            ]
        );
        /** @var Checkout $checkout */
        $checkout = $this->getEntityManager()->find(Checkout::class, $checkoutId);
        self::assertFalse($checkout->isPaymentInProgress());
        self::assertInstanceOf(Order::class, $checkout->getOrder());
        $responseData = self::jsonToArray($response->getContent());
        $this->assertResponseContains('order_for_ready_for_completion_checkout.yml', $response);
        self::assertEquals($paymentMethod, $responseData['data']['attributes']['paymentMethod'][0]['code']);
        self::assertEquals($shippingMethod, $responseData['data']['attributes']['shippingMethod']['code']);
        self::assertEquals($shippingMethodType, $responseData['data']['attributes']['shippingMethod']['type']);
        self::assertNotEmpty($responseData['data']['relationships']['billingAddress']['data']);
        self::assertNotEmpty($responseData['data']['relationships']['shippingAddress']['data']);
        self::assertCount(2, $responseData['data']['relationships']['lineItems']['data']);
    }
}
