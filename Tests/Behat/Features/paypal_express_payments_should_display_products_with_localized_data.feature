@regression
@ticket-BB-15562
@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroPayPalExpressBundle:ProductsAndShoppingListsFixture.yml
@behat-test-env
Feature: Paypal Express Payments should display products with localized data
  In order to be able to see localized products in the payment
  As a buyer
  I want to be able created pay and see product name and description in the payment,
  in the localization that used in current moment

  Scenario: Feature Background
    Given I enable the existing localizations
    And There are products in the system available for order

  Scenario: Create new PayPal Express Integration
    Given I login as AmandaRCole@example.org the "Buyer" at "first_session" session
    And I login as administrator and use in "second_session" as "Admin"
    And I go to System/Integrations/Manage Integrations
    And I click "Create Integration"
    And I select "PayPal Express" from "Type"
    And I fill "PayPal Express Integration Form" with:
      | Name           | PayPalExpress |
      | Label          | PayPalExpress |
      | Short Label    | PayPalExpress |
      | Payment Action | Authorize     |
      | Client ID      | client_id     |
      | Client Secret  | client_secret |
      | Status         | Active        |
    When I save and close form
    Then I should see "Integration saved" flash message
    And I should see PayPalExpress in grid

  Scenario: Create new Payment Rule for PayPal Express integration
    Given I go to System/Payment Rules
    And I click "Create Payment Rule"
    And I check "Enabled"
    And I fill in "Name" with "PayPalExpress"
    And I fill in "Sort Order" with "1"
    And I select "PayPalExpress" from "Method"
    And I click "Add Method Button"
    When I save and close form
    Then I should see "Payment rule has been saved" flash message

  Scenario: Successful order payment with PayPal Express and verify localized product data
    Given I operate as the Buyer
    And I am on the homepage
    And I select "Zulu" localization
    And I open page with shopping list List 1
    And I click "Create Order"
    And I select "Fifth avenue, 10115 Berlin, Germany" on the "Billing Information" checkout step and press Continue
    And I select "Fifth avenue, 10115 Berlin, Germany" on the "Shipping Information" checkout step and press Continue
    And I check "Flat Rate" on the "Shipping Method" checkout step and press Continue
    And I check "PayPalExpress" on the "Payment" checkout step and press Continue
    When I click "Submit Order"
    Then I should see the following products before pay:
      | NAME               |
      | SKU123 ZU product1 |
    And I see the "Thank You" page with "Thank You For Your Purchase!" title
    And I should not see "We were unable to process your payment"
