<?php
namespace Resoul\Imdb\DTO;

readonly class GrossDataDTO
{
    public function __construct(
        public ?int $domestic = null,
        public ?int $international = null,
        public ?int $worldwide = null,
    ) {}
}