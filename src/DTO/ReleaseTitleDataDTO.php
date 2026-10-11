<?php
namespace Resoul\Imdb\DTO;

readonly class ReleaseTitleDataDTO
{
    public function __construct(
        public string $imdbProUri,
        public string $releaseDate,
        public ?int $budget = null,
        public ?int $opening = null,
        public ?int $openingTheaters = null,
        public ?int $wideRelease = null,
        public ?GrossDataDTO $gross = null,
        public ?string $distributor = null,
    ) {}
}