<?php

declare(strict_types=1);

namespace Domain\Location;

class Location
{
    public function __construct(
        private string $description,
        private string $lat,
        private string $lng,
    ) {}

    public function equals(self $other): bool
    {
        return $this->description === $other->description;
    }

    public function __toString(): string
    {
        return $this->description . ' (' . $this->lat . ', ' . $this->lng . ')';
    }
}
