<?php

declare(strict_types=1);

namespace TitanZero\Interaction\Vertical\DTO;

final readonly class VerticalContextSnapshot
{
    public function __construct(
        private array $data,
        private array $layers,
        private string $hash,
    ) {}

    public function toArray(): array
    {
        return $this->data;
    }

    public function contextHash(): string
    {
        return $this->hash;
    }

    /** @return list<VerticalContextLayer> */
    public function layers(): array
    {
        return $this->layers;
    }
}
