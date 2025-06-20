Feature: Fleet operations

  Scenario: Register a vehicle
    Given I have a fleet with ID "myFleet"
    And I own a vehicle with ID "vehicle1"
    When I register the vehicle "vehicle1" in the fleet "myFleet"
    Then the fleet "myFleet" should contain the vehicle "vehicle1"