<?php
declare(strict_types=1);

namespace Domain\Fleet;

use Domain\Vehicle\Vehicle;
use Domain\Location\Location;
use Domain\Vehicle\VehicleId;

final class Fleet
{
    /**
     * @var Vehicle[]
     */
    private array $vehicles = [];
    private array $vehicleLocations = [];
    private array $parkedVehicles = [];

    private string $userId;
    public function __construct(
        private readonly FleetId $id
    ) {
    }

    public function id(): FleetId
    {
        return $this->id;
    }

    /**
     * Ajoute un véhicule à la flotte si il n'y est pas déjà.
     *
     * @throws \DomainException si le véhicule est déjà présent
     */
    public function addVehicle(Vehicle $vehicle): void
    {
        if ($this->hasVehicle($vehicle)) {
            throw new \Exception("Vehicle already registered in this fleet.");
        }
        $this->vehicles[] = $vehicle;
    }
    public function userId(): string
    {
        return $this->userId;
    }
    /**
     * Vérifie si un véhicule est présent dans la flotte
     */
    public function hasVehicle(Vehicle $vehicle): bool
    {
        foreach ($this->vehicles as $v) {
            if ($v->id()->equals($vehicle->id())) {
                return true;
            }
        }
        return false;
    }

    /**
     * Retourne la liste des véhicules
     *
     * @return Vehicle[]
     */
    public function vehicles(): array
    {
        return $this->vehicles;
    }


    public function parkVehicleAtLocation(VehicleId $vehicleId, Location $location): void
    {
        $vehicleIdStr = $vehicleId->toString();

        if (isset($this->parkedVehicles[$vehicleIdStr]) && $this->parkedVehicles[$vehicleIdStr]->equals($location)) {
            throw new \DomainException("Vehicle is already parked at this location.");
        }

        $this->parkedVehicles[$vehicleIdStr] = $location;

    }

    public function getVehicleLocation(VehicleId $vehicleId): ?Location
    {
        $vehicleIdStr = $vehicleId->toString();
        return $this->parkedVehicles[$vehicleIdStr] ?? null;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id(),
        ];
    }

    public static function fromArray(array $data): self
    {
        $id = !empty($data['id']) ? $data['id'] : FleetId::generate()->id();
        $fleetId = new FleetId($id);
    
        $fleet = new self($fleetId);
    
        return $fleet; 
    }
    
}
