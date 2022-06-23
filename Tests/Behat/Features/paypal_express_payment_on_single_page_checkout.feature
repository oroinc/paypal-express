@container-incompatible
@ticket-BB-17479
@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroPayPalExpressBundle:ProductsAndShoppingListsFixture.yml
Feature:  PayPal Express payment on single page checkout
  In order to purchase goods using PayPal
  As a buyer
  I should be able to use PayPal Express on Single Page Checkout

  Scenario: Feature Background
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |
    And I activate "Single Page Checkout" workflow
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

  Scenario: Successful order payment with PayPal Express
    Given I proceed as the Buyer
    And I signed in as AmandaRCole@example.org on the store frontend
    And I open page with shopping list List 1
    When I click "Create Order"
    And I check "PayPalExpress" on the checkout page
    And I click "Submit Order"
    Then I see the "Thank You" page with "Thank You For Your Purchase!" title
    And I should not see "We were unable to process your payment"
