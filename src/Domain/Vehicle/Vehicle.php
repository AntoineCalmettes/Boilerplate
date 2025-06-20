<?php

declare(strict_types=1);

namespace Domain\Vehicle;

use Domain\Location\Location;

class Vehicle
{
    private ?Location $currentLocation = null;

    public function __construct(
        private readonly VehicleId $id,
        private string $description,
    ) {}

    public function parkTo(Location $location): void
    {
        if ($this->currentLocation !== null && $this->currentLocation->equals($location)) {
            throw new \DomainException('Vehicle already parked at this location');
        }

        $this->currentLocation = $location;
    }

    public function id(): VehicleId
    {
        return $this->id;
    }

    public function location(): ?Location
    {
        return $this->currentLocation;
    }

    public function equals(self $other): bool
    {
        return $this->id->equals($other->id);
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id->toString(),
            'description' => $this->description,
            'currentLocation' => $this->currentLocation?->__toString(),
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            new VehicleId($data['plateNumber'], $data['id']),
            $data['description'] ?? ''
        );
    }
}
