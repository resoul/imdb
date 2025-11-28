<?php
namespace Resoul\Imdb\DTO;

use Resoul\Imdb\Enum\FilmTypeEnum;
use Resoul\Imdb\Enum\GenreEnum;

readonly class TitleDataDTO {
    /**
     * @param array<GenreEnum> $genres Film genres
     * @param array<PersonDto> $persons Cast and crew
     */
    public function __construct(
        public string $title,
        public string $poster,
        public string $description,
        public string $certificate,
        public int $runningTime,
        public array $genres,
        public FilmTypeEnum $type,
        public array $persons,
        public int $seasons = 0,
        public ?string $releaseSummary = null,
    ) {}
}