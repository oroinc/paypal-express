@regression
@ticket-BB-14089
@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroPayPalBundle:PayPalExpressProductsWithTaxes.yml
@behat-test-env
Feature: Paypal Express payment should not have tax line when price includes tax
  In order to complete checkout with products which prices are already include tax
  As a buyer
  I want to be able to see correct checkout total information on PayPal site

  Scenario: Feature Background
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |
    And There are products in the system available for order
    And I enable configuration options:
      | oro_tax.product_prices_include_tax |
    And I set configuration property "oro_tax.use_as_base_by_default" to "destination"

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

  Scenario: Proceed PayPal Express Checkout without separate "tax" line item
    Given I proceed as the Buyer
    And I signed in as AmandaRCole@example.org on the store frontend
    And I open page with shopping list List 1
    And I click "Create Order"
    And I select "ORO, Third avenue, TALLAHASSEE FL US 32003" on the "Billing Information" checkout step and press Continue
    And I select "ORO, Third avenue, TALLAHASSEE FL US 32003" on the "Shipping Information" checkout step and press Continue
    And I check "Flat Rate" on the "Shipping Method" checkout step and press Continue
    And I check "PayPalExpress" on the "Payment" checkout step and press Continue
    Then I should see Checkout Totals with data:
      | Subtotal | $10.00  |
      | Shipping | $3.00   |
      | Tax      | $0.91   |
    And I should see "Total: $13.00"
    When I click "Submit Order"
    Then I should not see the following products before pay:
      | NAME | DESCRIPTION |
      | Tax  |  Tax        |
    And I see the "Thank You" page with "Thank You For Your Purchase!" title
