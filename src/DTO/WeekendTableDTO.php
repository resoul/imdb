<?php

declare(strict_types=1);

namespace Resoul\Imdb\DTO;

readonly class WeekendTableDTO
{
    /**
     * @param array<ReleaseDataDTO> $releases
     */
    public function __construct(
        public string $title,
        public array $releases,
    ) {}
}