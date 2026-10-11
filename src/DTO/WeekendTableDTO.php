<?php
namespace Resoul\Imdb\DTO;

readonly class WeekendTableDTO
{
    /**
     * @param array<ReleaseDataDto> $releases
     */
    public function __construct(
        public string $title,
        public array $releases,
    ) {}
}