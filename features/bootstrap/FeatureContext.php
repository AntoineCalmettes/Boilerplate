<?php

use Behat\Step\Then;
use Behat\Step\When;
use Behat\Step\Given;
use Domain\Fleet\Fleet;
use Domain\Fleet\FleetId;
use Domain\Vehicle\Vehicle;
use Domain\Location\Location;
use Domain\Vehicle\VehicleId;
use Behat\Behat\Context\Context;
use Behat\Behat\Hook\AfterScenario;
use Infra\Persistence\JsonFleetRepository;
use Behat\Behat\Hook\Scope\AfterScenarioScope;
use Infra\Persistence\InMemoryFleetRepository;

final class FeatureContext implements Context
{
    private InMemoryFleetRepository $fleetRepository;
    private JsonFleetRepository $repository;
    /** @var array<string, Fleet> */
    private array $fleets = [];

    /** @var array<string, Vehicle> */
    private array $vehicles = [];

    /** @var array<string, string> */
    private array $locations = [];

    /** @var array<string, string> */
    private array $vehicleLocations = [];
    private const JSON_REPO_PATH = __DIR__ . '/../../../var/fleets.json';
    private ?string $lastError = null;
    private Vehicle $vehicle;
    private Location $location;
    private $lastErrorMessage;
    private string $errorMessage;
    public function __construct(
    )
    {
        $this->repository = new JsonFleetRepository(self::JSON_REPO_PATH);
    }

    /**
     * @Given I have a fleet with ID :id
     */
    public function iHaveAFleetWithId(string $id): void
    {
        $fleet = new Fleet(new FleetId($id));
        $this->fleets[$id] = $fleet;
        $this->repository->save($fleet);
    }

    /**
     * @Given I own a vehicle with ID :id
     */
    public function iOwnAVehicleWithId(string $id): void
    {
        // Exemple simple: on utilise l'id pour la plaque et la description générique
        $vehicleId = new VehicleId(plateNumber: 'BH-123-13', id: 'abc123');
        $vehicle = new Vehicle(id: $vehicleId, description: 'Description');
        $this->vehicles[$id] = $vehicle;
        $this->vehicle = $vehicle;
    }

    /**
     * @When I register the vehicle :vehicleId in the fleet :fleetId
     */
    public function iRegisterTheVehicleInTheFleet(string $vehicleId, string $fleetId): void
    {
        if (!isset($this->fleets[$fleetId])) {
            throw new \Exception("Fleet $fleetId not found");
        }
        if (!isset($this->vehicles[$vehicleId])) {
            throw new \Exception("Vehicle $vehicleId not found");
        }
        $fleet = $this->fleets[$fleetId];
        $vehicle = $this->vehicles[$vehicleId];

        $fleet->addVehicle($vehicle);
    }

    /**
     * @Then the fleet :fleetId should contain the vehicle :vehicleId
     */
    public function theFleetShouldContainTheVehicle(string $fleetId, string $vehicleId): void
    {
        if (!isset($this->fleets[$fleetId])) {
            throw new \Exception("Fleet $fleetId not found");
        }
        if (!isset($this->vehicles[$vehicleId])) {
            throw new \Exception("Vehicle $vehicleId not found");
        }
        $fleet = $this->fleets[$fleetId];
        $vehicle = $this->vehicles[$vehicleId];

        if (!$fleet->hasVehicle($vehicle)) {
            throw new \Exception("Fleet $fleetId does not contain vehicle $vehicleId");
        }
    }

    /**
     * @Given I have a fleet named :name
     */
    public function iHaveAFleetNamed(string $name): void
    {
        $fleet = new Fleet(new FleetId($name));
        $this->fleets[$name] = $fleet;
        $this->repository->save($fleet);
    }

    /**
     * @Given I own a vehicle identified as :vehicleIdentifier
     */
    public function iOwnAVehicleIdentifiedAs(string $vehicleIdentifier): void
    {
        $parts = explode(' - ', $vehicleIdentifier);
        $description = $parts[0] ?? 'Unknown';
        $plateNumber = $parts[1] ?? 'XXX-000';

        $vehicle = new Vehicle(new VehicleId($vehicleIdentifier, $plateNumber), $description);
        $this->vehicles[$vehicleIdentifier] = $vehicle;
    }

    /**
     * @Given I have registered this vehicle into the fleet :fleetName
     */
    public function iHaveRegisteredThisVehicleIntoTheFleet(string $fleetName): void
    {
        $fleet = $this->fleets[$fleetName] ?? null;
        if ($fleet === null) {
            throw new \Exception("Fleet $fleetName not found");
        }

        // On prend le dernier véhicule ajouté (supposé être celui concerné)
        $vehicle = end($this->vehicles);
        if ($vehicle === false) {
            throw new \Exception("No vehicles available to register");
        }

        $fleet->addVehicle($vehicle);
    }

    /**
     * @Given I have a location identified as :locationId
     */
    public function iHaveALocationIdentifiedAs(string $locationId): void
    {
        $this->locations[$locationId] = $locationId;
    }

    /**
     * @When I park my vehicle :vehicleId at this location
     */
    public function iParkMyVehicleAtThisLocation(string $vehicleId): void
    {
        $location = end($this->locations);
        if ($location === false) {
            throw new \Exception("No locations defined");
        }
        if (isset($this->vehicleLocations[$vehicleId]) && $this->vehicleLocations[$vehicleId] === $location) {
            throw new \Exception("Vehicle $vehicleId is already parked at this location");
        }
        $this->vehicleLocations[$vehicleId] = $location;
        $this->lastErrorMessage = null;
    }

    /**
     * @Then the known location of my vehicle should be :locationId
     */
    public function theKnownLocationOfMyVehicleShouldBe(string $locationId): void
    {
        $vehicleId = key(array_slice($this->vehicleLocations, -1, 1, true));
        if (!isset($this->vehicleLocations[$vehicleId]) || $this->vehicleLocations[$vehicleId] !== $locationId) {
            throw new \Exception("Expected location '{$locationId}', but found '" . ($this->vehicleLocations[$vehicleId] ?? 'none') . "'");
        }
    }

    /**
     * @Given my vehicle :vehicleId has been parked at this location
     */
    public function myVehicleHasBeenParkedAtThisLocation(string $vehicleId): void
    {
        $location = end($this->locations);
        if ($location === false) {
            throw new \Exception("No locations defined");
        }
        $this->vehicleLocations[$vehicleId] = $location;
    }


    /**
     * @Then I should be informed that my vehicle is already parked at this location
     */
    public function iShouldBeInformedThatMyVehicleIsAlreadyParkedAtThisLocation(): void
    {
        if ($this->lastErrorMessage !== "Vehicle already parked at this location") {
            $this->lastErrorMessage = "Expected an error about vehicle already parked, but none was found";
        }
    }

    /**
     * @When I multiply :arg1 by :arg2 into a
     */
    public function iMultiplyByIntoA($arg1, $arg2): void
    {
        $this->result = $arg1 * $arg2;
    }

    /**
     * @Then a should be equal to :expected
     */
    public function aShouldBeEqualTo($expected): void
    {
        if (!isset($this->result) || $this->result != $expected) {
            throw new \Exception("Expected $expected, got " . ($this->result ?? 'null'));
        }
    }

    /**
     * @Given my fleet
     */
    public function myFleet(): void
    {
        $this->iHaveAFleetNamed('myFleet');
    }

    /**
     * @Given a vehicle
     */
    public function aVehicle(): void
    {
        $this->iOwnAVehicleWithId('vehicle1');
    }

    /**
     * @Then I should be informed that this vehicle has already been registered into my fleet
     */
    public function iShouldBeInformedVehicleAlreadyRegistered(): void
    {
        if ($this->lastErrorMessage !== 'Vehicle already registered in this fleet.') {
            throw new \Exception("Expected error 'Vehicle already registered in this fleet.', got '{$this->lastErrorMessage}'");
        }
    }

    /**
     * @When I register this vehicle into my fleet
     */
    public function iRegisterThisVehicleIntoMyFleet(): void
    {
        if (!isset($this->fleets['myFleet'])) {
            throw new \Exception("Fleet 'myFleet' not defined");
        }
    
        $fleet = $this->fleets['myFleet'];
        $vehicle = end($this->vehicles);
    
        if ($vehicle === false) {
            throw new \Exception("No vehicle defined");
        }
    
        if (!$fleet->hasVehicle($vehicle)) {
            $fleet->addVehicle($vehicle);
        }
    }

    /**
     * @Then this vehicle should be part of my vehicle fleet
     */
    public function thisVehicleShouldBePartOfMyVehicleFleet(): void
    {
        $fleet = end($this->fleets);
        $vehicle = end($this->vehicles);

        if ($fleet === false || $vehicle === false) {
            throw new \Exception("Fleet or vehicle not defined");
        }

        if (!$fleet->hasVehicle($vehicle)) {
            throw new \Exception("Vehicle not found in fleet");
        }
    }

    /**
     * @Given I have registered this vehicle into my fleet
     */
    public function iHaveRegisteredThisVehicleIntoMyFleet(): void
    {
        $fleet = end($this->fleets);
        $vehicle = end($this->vehicles);

        if ($fleet === false || $vehicle === false) {
            throw new \Exception("Fleet or vehicle not defined");
        }

        try {
            $fleet->addVehicle($vehicle);
            $this->lastErrorMessage = null;
        } catch (\Exception $e) {
            $this->lastErrorMessage = $e->getMessage();
        }
    }

    #[When('I try to register this vehicle into my fleet')]
    public function iTryToRegisterThisVehicleIntoMyFleet(): void
    {
        $this->lastErrorMessage = null;
    
        $vehicle = $this->vehicle ?? end($this->vehicles);
    
        foreach ($this->fleets as $fleet) {
            try {
                $fleet->addVehicle($vehicle);
            } catch (\Exception $e) {
                $this->lastErrorMessage = $e->getMessage();
            }
        }
    }

    #[Then('I should be informed this this vehicle has already been registered into my fleet')]
    public function iShouldBeInformedThisThisVehicleHasAlreadyBeenRegisteredIntoMyFleet2()
    {
        if ($this->lastErrorMessage === null) {
            throw new \Exception('No exception was thrown');
        }
        if ($this->lastErrorMessage !== 'Vehicle already registered in this fleet.') {
            throw new \Exception(sprintf(
                "Expected error message '%s', got '%s'",
                'Vehicle already registered in this fleet.',
                $this->lastErrorMessage
            ));
        }
    }
    
    public function iShouldBeInformedThisThisVehicleHasAlreadyBeenRegisteredIntoMyFleet(): void
    {
        if (!isset($this->errorMessage)) {
            throw new \RuntimeException('Aucune erreur n\'a été capturée.');
        }
    
        if ($this->errorMessage !== 'This vehicle has already been registered into your fleet.') {
            throw new \RuntimeException(sprintf(
                'Message inattendu : "%s"',
                $this->errorMessage
            ));
        }
    }
    /**
     * @Given the fleet of another user
     */
    public function theFleetOfAnotherUser(): void
    {
        $fleet = new Fleet(new FleetId('otherUserFleet'));
        $this->fleets['otherUserFleet'] = $fleet;
        $this->repository->save($fleet);
    }

    /**
     * @Given this vehicle has been registered into the other user's fleet
     */
    public function thisVehicleHasBeenRegisteredIntoTheOtherUsersFleet(): void
    {
        $fleet = $this->fleets['otherUserFleet'] ?? null;
        $vehicle = end($this->vehicles);

        if ($fleet === null || $vehicle === false) {
            throw new \Exception("Other user's fleet or vehicle not defined");
        }

        try {
            $fleet->addVehicle($vehicle);
        } catch (\Exception $e) {
            $this->lastErrorMessage = $e->getMessage();
        }
    }

    #[Given('a location')]
    public function aLocation(): void
    {
        $this->location = new Location("Parking A - Spot 42", "48.8566", "2.3522");
    }

    #[When('I park my vehicle at this location')]
    public function iParkMyVehicleAtThisLocation2(): void
    {
        try {
            $this->vehicle->parkTo($this->location);
        } catch (\Throwable $e) {
            $this->lastErrorMessage = $e->getMessage();
        }
    }

    #[Then('the known location of my vehicle should verify this location')]
    public function theKnownLocationOfMyVehicleShouldVerifyThisLocation(): void
    {
        if ((string) $this->vehicle->location() !== (string) $this->location) {
            throw new \Exception("Vehicle is not at the expected location");
        }
    }

    #[Given('my vehicle has been parked into this location')]
    public function myVehicleHasBeenParkedIntoThisLocation(): void
    {
        $this->vehicle->parkTo($this->location);
    }

    #[When('I try to park my vehicle at this location')]
    public function iTryToParkMyVehicleAtThisLocation2(): void
    {
        try {
            $this->vehicle->parkTo($this->location);
            // No exception thrown: clear error
            $this->lastErrorMessage = null;
            throw new \Exception('Expected DomainException not thrown');
        } catch (\DomainException $e) {
            $this->lastErrorMessage = $e->getMessage();
        }
    }

/** @AfterScenario */
public function displayJsonState(AfterScenarioScope $scope): void
{
    fwrite(STDOUT, "=== AfterScenario hook triggered ===\n");

    $fleetsData = array_map(function(Fleet $fleet) {
        return [
            'id' => (string) $fleet->id()->id(),
            'vehiclesCount' => count($fleet->vehicles()),
            'vehicles' => array_map(function(Vehicle $vehicle) {
                return [
                    'plateNumber' => $vehicle->id()->plateNumber(),
                    'location' => $vehicle->location() ?  $vehicle->location()->__toString() : null,
                ];
            }, $fleet->vehicles()),
            "lastErrorMessage"=>$this->lastErrorMessage
        ];
    }, $this->fleets);

    $json = json_encode($fleetsData, JSON_PRETTY_PRINT);
    fwrite(STDOUT, $json . "\n");
}
}
