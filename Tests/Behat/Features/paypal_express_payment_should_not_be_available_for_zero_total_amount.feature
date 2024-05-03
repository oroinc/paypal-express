@regression
@ticket-BB-16307
@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroCheckoutBundle:Shipping.yml
@fixture-OroPayPalBundle:PayPalExpressProduct.yml
@fixture-OroPromotionBundle:100-percent-promotions-with-coupons.yml
@behat-test-env
Feature: PayPal Express payment should not be available for zero total amount
  In order to purchase goods using PayPal Express
  As a buyer
  I should be able to use PayPal Express if order total is greater than zero

  Scenario: Feature Background
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |
    And There are products in the system available for order

  Scenario: Create new PayPal Express Integration
    Given I proceed as the Admin
    And login as administrator
    And I go to System/Integrations/Manage Integrations
    When I click "Create Integration"
    And I select "PayPal Express" from "Type"
    And I fill "PayPal Express Integration Form" with:
      | Name           | PayPalExpress |
      | Payment Action | Authorize     |
      | Label          | PayPalExpress |
      | Short Label    | PPlExpress    |
      | Client ID      | client_id     |
      | Client Secret  | client_secret |
      | Status         | Active        |
    And I save and close form
    Then I should see "Integration saved" flash message

  Scenario: Create new Payment Rule for PayPal Express integration
    Given I go to System/Payment Rules
    When I click "Create Payment Rule"
    And I check "Enabled"
    And I fill in "Name" with "PayPalExpress"
    And I fill in "Sort Order" with "1"
    And I select "PayPalExpress" from "Method"
    And I click "Add Method Button"
    And I save and close form
    Then I should see "Payment rule has been saved" flash message

  Scenario: Start checkout and choose PayPal Express payment method
    Given I proceed as the Buyer
    And I signed in as AmandaRCole@example.org on the store frontend
    And I open page with shopping list List 1
    And I click "Create Order"
    And I select "Fifth avenue, 10115 Berlin, Germany" on the "Billing Information" checkout step and press Continue
    And I select "Fifth avenue, 10115 Berlin, Germany" on the "Shipping Information" checkout step and press Continue
    And I check "Flat Rate" on the "Shipping Method" checkout step and press Continue
    And I check "PayPalExpress" on the "Payment" checkout step and press Continue

  Scenario: Add Coupons for 100% discount
    Given I scroll to "I have a Coupon Code"
    When I click "I have a Coupon Code"
    And I type "coupon-100-order" in "Coupon Code Input"
    And I click "Apply"
    And I type "coupon-100-shipping" in "Coupon Code Input"
    And I click "Apply"
    Then I should see "The selected payment method is not available. Please return to the payment method selection step and select a different one." flash message
    And I should see "coupon-100-order Promotion Order 100 Label" in the "Coupons List" element
    And I should see "coupon-100-shipping Promotion Shipping 100 Label" in the "Coupons List" element
    And I should see "Total $0.00"

  Scenario: Ensure payment method is not available anymore
    Given on the "Order Review" checkout step I go back to "Edit Payment"
    Then I should see "No payment methods are available, please contact us to complete the order submission."
    And I should not see "PayPalExpress"
