<?php

declare(strict_types=1);

namespace App\Command\Fleet;

final readonly class CreateFleetCommand
{
    public function __construct(
        public string $userId
    ) {}
}