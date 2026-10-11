<?php

declare(strict_types=1);

namespace Resoul\Imdb\DTO;

readonly class TitleDataDTO {
    public function __construct(
        public string $title,
        public string $poster,
        public string $description,
        public string $certificate,
        public int $runningTime,
        public array $genres,
        public int $type,
        public array $persons,
        public int $seasons = 0,
        public ?string $releaseSummary = null,
    ) {}
}