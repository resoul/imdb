<?php

declare(strict_types=1);

namespace Resoul\Imdb\DTO;

readonly class PersonDTO
{
    public function __construct(
        public string $name,
        public string $uri,
        public int $role,
        public ?string $characterName = null,
        public ?string $posterUrl = null,
    ) {}
}