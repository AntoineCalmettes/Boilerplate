<?php
declare(strict_types=1);

namespace Domain\Vehicle;

final class VehicleId
{
    public readonly string $plateNumber;

    public function __construct(
        string $plateNumber,
        private string $id,
    ) {
        if (empty($plateNumber)) {
            throw new \InvalidArgumentException("Vehicle plate number cannot be empty.");
        }

        $this->plateNumber = $plateNumber;
        $this->id = strtolower($id);
    }

    public function plateNumber(): string
    {
        return $this->plateNumber;
    }

    public function equals(self $other): bool
    {
        return $this->id === $other->id;
    }

    public function toString(): string
    {
        return $this->id;
    }
}
