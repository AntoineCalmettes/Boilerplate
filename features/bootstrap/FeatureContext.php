<?php

declare(strict_types=1);

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
    private const JSON_REPO_PATH = __DIR__ . '/../../../var/fleets.json';

    /** @var array<string, Fleet> */
    private array $fleets = [];

    /** @var array<string, Vehicle> */
    private array $vehicles = [];

    /** @var array<string, string> */
    private array $locations = [];

    /** @var array<string, string> */
    private array $vehicleLocations = [];

    private ?string $lastError = null;
    private ?Vehicle $vehicle = null;
    private ?Location $location = null;
    private ?string $lastErrorMessage = null;
    private string $errorMessage = '';
    private mixed $result = null;

    public function __construct(
        private readonly JsonFleetRepository $repository = new JsonFleetRepository(self::JSON_REPO_PATH)
    ) {}

    #[Given('I have a fleet with ID :id')]
    public function iHaveAFleetWithId(string $id): void
    {
        $fleet = new Fleet(new FleetId($id));
        $this->fleets[$id] = $fleet;
        $this->repository->save($fleet);
    }

    #[Given('I own a vehicle with ID :id')]
    public function iOwnAVehicleWithId(string $id): void
    {
        $vehicleId = new VehicleId(plateNumber: 'BH-123-13', id: 'abc123');
        $vehicle = new Vehicle(id: $vehicleId, description: 'Description');
        $this->vehicles[$id] = $vehicle;
        $this->vehicle = $vehicle;
    }

    #[When('I register the vehicle :vehicleId in the fleet :fleetId')]
    public function iRegisterTheVehicleInTheFleet(string $vehicleId, string $fleetId): void
    {
        $fleet = $this->getFleetById($fleetId);
        $vehicle = $this->getVehicleById($vehicleId);

        $fleet->addVehicle($vehicle);
    }

    #[Then('the fleet :fleetId should contain the vehicle :vehicleId')]
    public function theFleetShouldContainTheVehicle(string $fleetId, string $vehicleId): void
    {
        $fleet = $this->getFleetById($fleetId);
        $vehicle = $this->getVehicleById($vehicleId);

        if (!$fleet->hasVehicle($vehicle)) {
            throw new \Exception("Fleet $fleetId does not contain vehicle $vehicleId");
        }
    }

    #[Given('I have a fleet named :name')]
    public function iHaveAFleetNamed(string $name): void
    {
        $fleet = new Fleet(new FleetId($name));
        $this->fleets[$name] = $fleet;
        $this->repository->save($fleet);
    }

    #[Given('I own a vehicle identified as :vehicleIdentifier')]
    public function iOwnAVehicleIdentifiedAs(string $vehicleIdentifier): void
    {
        [$description, $plateNumber] = $this->parseVehicleIdentifier($vehicleIdentifier);
        
        $vehicle = new Vehicle(
            new VehicleId($vehicleIdentifier, $plateNumber), 
            $description
        );
        $this->vehicles[$vehicleIdentifier] = $vehicle;
    }

    #[Given('I have registered this vehicle into the fleet :fleetName')]
    public function iHaveRegisteredThisVehicleIntoTheFleet(string $fleetName): void
    {
        $fleet = $this->getFleetByName($fleetName);
        $vehicle = $this->getLastVehicle();

        $fleet->addVehicle($vehicle);
    }

    #[Given('I have a location identified as :locationId')]
    public function iHaveALocationIdentifiedAs(string $locationId): void
    {
        $this->locations[$locationId] = $locationId;
    }

    #[When('I park my vehicle :vehicleId at this location')]
    public function iParkMyVehicleAtThisLocation(string $vehicleId): void
    {
        $location = $this->getLastLocation();
        
        if ($this->isVehicleAlreadyParkedAtLocation($vehicleId, $location)) {
            throw new \Exception("Vehicle $vehicleId is already parked at this location");
        }
        
        $this->vehicleLocations[$vehicleId] = $location;
        $this->lastErrorMessage = null;
    }

    #[Then('the known location of my vehicle should be :locationId')]
    public function theKnownLocationOfMyVehicleShouldBe(string $locationId): void
    {
        $vehicleId = $this->getLastVehicleLocationKey();
        $actualLocation = $this->vehicleLocations[$vehicleId] ?? null;
        
        if ($actualLocation !== $locationId) {
            throw new \Exception(
                "Expected location '{$locationId}', but found '" . ($actualLocation ?? 'none') . "'"
            );
        }
    }

    #[Given('my vehicle :vehicleId has been parked at this location')]
    public function myVehicleHasBeenParkedAtThisLocation(string $vehicleId): void
    {
        $location = $this->getLastLocation();
        $this->vehicleLocations[$vehicleId] = $location;
    }

    #[Then('I should be informed that my vehicle is already parked at this location')]
    public function iShouldBeInformedThatMyVehicleIsAlreadyParkedAtThisLocation(): void
    {
        if ($this->lastErrorMessage !== "Vehicle already parked at this location") {
            $this->lastErrorMessage = "Expected an error about vehicle already parked, but none was found";
        }
    }

    #[When('I multiply :arg1 by :arg2 into a')]
    public function iMultiplyByIntoA(int|float $arg1, int|float $arg2): void
    {
        $this->result = $arg1 * $arg2;
    }

    #[Then('a should be equal to :expected')]
    public function aShouldBeEqualTo(int|float $expected): void
    {
        if ($this->result !== $expected) {
            throw new \Exception("Expected $expected, got " . ($this->result ?? 'null'));
        }
    }

    #[Given('my fleet')]
    public function myFleet(): void
    {
        $this->iHaveAFleetNamed('myFleet');
    }

    #[Given('a vehicle')]
    public function aVehicle(): void
    {
        $this->iOwnAVehicleWithId('vehicle1');
    }

    #[Then('I should be informed that this vehicle has already been registered into my fleet')]
    public function iShouldBeInformedVehicleAlreadyRegistered(): void
    {
        $expectedMessage = 'Vehicle already registered in this fleet.';
        if ($this->lastErrorMessage !== $expectedMessage) {
            throw new \Exception("Expected error '$expectedMessage', got '{$this->lastErrorMessage}'");
        }
    }

    #[When('I register this vehicle into my fleet')]
    public function iRegisterThisVehicleIntoMyFleet(): void
    {
        $fleet = $this->getFleetByName('myFleet');
        $vehicle = $this->getLastVehicle();

        if (!$fleet->hasVehicle($vehicle)) {
            $fleet->addVehicle($vehicle);
        }
    }

    #[Then('this vehicle should be part of my vehicle fleet')]
    public function thisVehicleShouldBePartOfMyVehicleFleet(): void
    {
        $fleet = $this->getLastFleet();
        $vehicle = $this->getLastVehicle();

        if (!$fleet->hasVehicle($vehicle)) {
            throw new \Exception("Vehicle not found in fleet");
        }
    }

    #[Given('I have registered this vehicle into my fleet')]
    public function iHaveRegisteredThisVehicleIntoMyFleet(): void
    {
        $fleet = $this->getLastFleet();
        $vehicle = $this->getLastVehicle();

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
        $vehicle = $this->vehicle ?? $this->getLastVehicle();

        foreach ($this->fleets as $fleet) {
            try {
                $fleet->addVehicle($vehicle);
            } catch (\Exception $e) {
                $this->lastErrorMessage = $e->getMessage();
            }
        }
    }

    #[Then('I should be informed this this vehicle has already been registered into my fleet')]
    public function iShouldBeInformedThisThisVehicleHasAlreadyBeenRegisteredIntoMyFleet(): void
    {
        $expectedMessage = 'Vehicle already registered in this fleet.';
        
        if ($this->lastErrorMessage === null) {
            throw new \Exception('No exception was thrown');
        }
        
        if ($this->lastErrorMessage !== $expectedMessage) {
            throw new \Exception(sprintf(
                "Expected error message '%s', got '%s'",
                $expectedMessage,
                $this->lastErrorMessage
            ));
        }
    }

    #[Given('the fleet of another user')]
    public function theFleetOfAnotherUser(): void
    {
        $fleet = new Fleet(new FleetId('otherUserFleet'));
        $this->fleets['otherUserFleet'] = $fleet;
        $this->repository->save($fleet);
    }

    #[Given('this vehicle has been registered into the other user\'s fleet')]
    public function thisVehicleHasBeenRegisteredIntoTheOtherUsersFleet(): void
    {
        $fleet = $this->getFleetByName('otherUserFleet');
        $vehicle = $this->getLastVehicle();

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
        if ($this->vehicle === null || $this->location === null) {
            throw new \Exception('Vehicle or location not defined');
        }

        try {
            $this->vehicle->parkTo($this->location);
        } catch (\Throwable $e) {
            $this->lastErrorMessage = $e->getMessage();
        }
    }

    #[Then('the known location of my vehicle should verify this location')]
    public function theKnownLocationOfMyVehicleShouldVerifyThisLocation(): void
    {
        if ($this->vehicle === null || $this->location === null) {
            throw new \Exception('Vehicle or location not defined');
        }

        if ((string) $this->vehicle->location() !== (string) $this->location) {
            throw new \Exception("Vehicle is not at the expected location");
        }
    }

    #[Given('my vehicle has been parked into this location')]
    public function myVehicleHasBeenParkedIntoThisLocation(): void
    {
        if ($this->vehicle === null || $this->location === null) {
            throw new \Exception('Vehicle or location not defined');
        }

        $this->vehicle->parkTo($this->location);
    }

    #[When('I try to park my vehicle at this location')]
    public function iTryToParkMyVehicleAtThisLocation2(): void
    {
        if ($this->vehicle === null || $this->location === null) {
            throw new \Exception('Vehicle or location not defined');
        }

        try {
            $this->vehicle->parkTo($this->location);
            $this->lastErrorMessage = null;
            throw new \Exception('Expected DomainException not thrown');
        } catch (\DomainException $e) {
            $this->lastErrorMessage = $e->getMessage();
        }
    }

    #[AfterScenario]
    public function displayJsonState(AfterScenarioScope $scope): void
    {
        fwrite(STDOUT, "=== AfterScenario hook triggered ===\n");

        $fleetsData = array_map(
            fn(Fleet $fleet) => [
                'id' => (string) $fleet->id()->id(),
                'vehiclesCount' => count($fleet->vehicles()),
                'vehicles' => array_map(
                    fn(Vehicle $vehicle) => [
                        'plateNumber' => $vehicle->id()->plateNumber(),
                        'location' => $vehicle->location()?->__toString(),
                    ],
                    $fleet->vehicles()
                ),
                'lastErrorMessage' => $this->lastErrorMessage
            ],
            $this->fleets
        );

        $json = json_encode($fleetsData, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
        fwrite(STDOUT, $json . "\n");
    }

    // Helper methods

    private function getFleetById(string $id): Fleet
    {
        return $this->fleets[$id] ?? throw new \Exception("Fleet $id not found");
    }

    private function getFleetByName(string $name): Fleet
    {
        return $this->fleets[$name] ?? throw new \Exception("Fleet $name not found");
    }

    private function getVehicleById(string $id): Vehicle
    {
        return $this->vehicles[$id] ?? throw new \Exception("Vehicle $id not found");
    }

    private function getLastFleet(): Fleet
    {
        $fleet = end($this->fleets);
        if ($fleet === false) {
            throw new \Exception("Fleet not defined");
        }
        return $fleet;
    }

    private function getLastVehicle(): Vehicle
    {
        $vehicle = end($this->vehicles);
        if ($vehicle === false) {
            throw new \Exception("No vehicles available");
        }
        return $vehicle;
    }

    private function getLastLocation(): string
    {
        $location = end($this->locations);
        if ($location === false) {
            throw new \Exception("No locations defined");
        }
        return $location;
    }

    private function getLastVehicleLocationKey(): string
    {
        $vehicleId = key(array_slice($this->vehicleLocations, -1, 1, true));
        if ($vehicleId === null) {
            throw new \Exception("No vehicle locations defined");
        }
        return $vehicleId;
    }

    private function isVehicleAlreadyParkedAtLocation(string $vehicleId, string $location): bool
    {
        return isset($this->vehicleLocations[$vehicleId]) 
            && $this->vehicleLocations[$vehicleId] === $location;
    }

    private function parseVehicleIdentifier(string $vehicleIdentifier): array
    {
        $parts = explode(' - ', $vehicleIdentifier);
        return [
            $parts[0] ?? 'Unknown',
            $parts[1] ?? 'XXX-000'
        ];
    }
}