<?php

declare(strict_types=1);

namespace Resoul\Imdb\DTO;

readonly class ReleaseDataDTO
{
    public function __construct(
        public int $rank,
        public string $title,
        public string $uri,
        public int $gross,
        public int $total,
        public int $theaters = 0,
        public int $weeks = 0,
        public int $lastWeek = 0,
    ) {}
}