@regression
@behat-test-env
@ticket-BB-14983
@ticket-BB-16182
@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroPayPalExpressBundle:ProductsAndShoppingListsFixture.yml
Feature: PayPal Express payment should not fail with Product Prices Include Tax option
  In order to be able to make purchases
  As a buyer
  I want to be able to pay for orders using PayPal Express when the tax is included/excluded into product price
  and shipping rates

  Scenario: Create new PayPal Express Integration
    Given I login as AmandaRCole@example.org the "Buyer" at "first_session" session
    And I login as administrator and use in "second_session" as "Admin"
    When I go to System/Integrations/Manage Integrations
    And I click "Create Integration"
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
    And I should see PayPalExpress in grid
    When I go to System/Configuration
    And I follow "Commerce/Taxation/Tax Calculation" on configuration sidebar
    And uncheck "Use default" for "Use as Base by Default" field
    And uncheck "Use default" for "Product Prices Include Tax" field
    And I fill form with:
      | Use as Base by Default     | Destination |
      | Product Prices Include Tax | true        |
    And I save form
    Then I should see "Configuration saved" flash message
    When I follow "Commerce/Taxation/Shipping" on configuration sidebar
    And uncheck "Use default" for "Tax Code" field
    And uncheck "Use default" for "Shipping Rates Include Tax" field
    And I fill "Tax Shipping Form" with:
      | Tax Code                   | taxable_items |
      | Shipping Rates Include Tax | true          |
    And I save form
    Then I should see "Configuration saved" flash message

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

  Scenario: Successful order payment with PayPal Express, product prices include tax and Shipping rates include tax
    Given There are products in the system available for order
    And I operate as the Buyer
    When I open page with shopping list List 1
    And I click "Create Order"
    And I select "Fifth avenue, 10115 Berlin, Germany" on the "Billing Information" checkout step and press Continue
    And I select "Fifth avenue, 10115 Berlin, Germany" on the "Shipping Information" checkout step and press Continue
    And I check "Flat Rate" on the "Shipping Method" checkout step and press Continue
    And I check "PayPalExpress" on the "Payment" checkout step and press Continue
    And I should see "Subtotal $10.00"
    And I should see "Shipping $3.00"
    And I should see "Tax $1.07"
    And I should see "Total $13.00"
    And I click "Submit Order"
    Then I see the "Thank You" page with "Thank You For Your Purchase!" title
    And I should not see "We were unable to process your payment"

  Scenario: Successful order payment with PayPal Express, product prices exclude tax and Shipping rates include tax
    Given I operate as the Admin
    When I go to System/Configuration
    And I follow "Commerce/Taxation/Tax Calculation" on configuration sidebar
    And I uncheck "Product Prices Include Tax"
    And I save form
    Then I should see "Configuration saved" flash message

    When I operate as the Buyer
    And I open page with shopping list List 2
    And I click "Create Order"
    And I select "Fifth avenue, 10115 Berlin, Germany" on the "Billing Information" checkout step and press Continue
    And I select "Fifth avenue, 10115 Berlin, Germany" on the "Shipping Information" checkout step and press Continue
    And I check "Flat Rate" on the "Shipping Method" checkout step and press Continue
    And I check "PayPalExpress" on the "Payment" checkout step and press Continue
    Then I should see "Subtotal $10.00"
    And I should see "Shipping $3.00"
    And I should see "Tax $1.15"
    And I should see "Total $13.90"
    And I click "Submit Order"
    Then I see the "Thank You" page with "Thank You For Your Purchase!" title
    And I should not see "We were unable to process your payment"

  Scenario: Successful order payment with PayPal Express, product prices exclude tax and Shipping rates exclude tax
    Given I operate as the Admin
    When I go to System/Configuration
    When I follow "Commerce/Taxation/Shipping" on configuration sidebar
    And I uncheck "Shipping Rates Include Tax"
    And I save form
    Then I should see "Configuration saved" flash message

    When I operate as the Buyer
    And I open page with shopping list List 3
    And I click "Create Order"
    And I select "Fifth avenue, 10115 Berlin, Germany" on the "Billing Information" checkout step and press Continue
    And I select "Fifth avenue, 10115 Berlin, Germany" on the "Shipping Information" checkout step and press Continue
    And I check "Flat Rate" on the "Shipping Method" checkout step and press Continue
    And I check "PayPalExpress" on the "Payment" checkout step and press Continue
    Then I should see "Subtotal $10.00"
    And I should see "Shipping $3.00"
    And I should see "Tax $1.17"
    And I should see "Total $14.17"
    And I click "Submit Order"
    Then I see the "Thank You" page with "Thank You For Your Purchase!" title
    And I should not see "We were unable to process your payment"

  Scenario: Successful order payment with PayPal Express, product prices include tax and Shipping rates exclude tax
    Given I operate as the Admin
    When I go to System/Configuration
    And I follow "Commerce/Taxation/Tax Calculation" on configuration sidebar
    And I check "Product Prices Include Tax"
    And I save form
    Then I should see "Configuration saved" flash message

    When I operate as the Buyer
    And I open page with shopping list List 4
    And I click "Create Order"
    And I select "Fifth avenue, 10115 Berlin, Germany" on the "Billing Information" checkout step and press Continue
    And I select "Fifth avenue, 10115 Berlin, Germany" on the "Shipping Information" checkout step and press Continue
    And I check "Flat Rate" on the "Shipping Method" checkout step and press Continue
    And I check "PayPalExpress" on the "Payment" checkout step and press Continue
    Then I should see "Subtotal $10.00"
    And I should see "Shipping $3.00"
    And I should see "Tax $1.10"
    And I should see "Total $13.27"
    And I click "Submit Order"
    Then I see the "Thank You" page with "Thank You For Your Purchase!" title
    And I should not see "We were unable to process your payment"
