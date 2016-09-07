Feature: Get request to subjects

  Scenario: Request
    Given I create a "GET" request to "/subjects"
    And I set the header "Content-Type" with the value "application/json"
    And I set the header "Accept" with the value "application/json"
#    And I set the headers "headers_array"
    And I execute the request
    Then I should get a "200" HTTP response code
