<?php
declare(strict_types=1);

namespace Domain\Fleet;

final class FleetId
{
    public function __construct(
        private readonly string $id
    ) {
        if (empty($id)) {
            throw new \InvalidArgumentException("Fleet ID cannot be empty.");
        }
    }

    public function id(): string
    {
        return $this->id;
    }

    public function equals(self $other): bool
    {
        return $this->id === $other->id;
    }

    public static function generate(): self
    {
        return new self(uniqid('', true));
    }
}
