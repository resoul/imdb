<?php
namespace Resoul\Imdb\DTO;

use Resoul\Imdb\Enum\RoleEnum;

readonly class PersonDTO
{
    public function __construct(
        public string $name,
        public string $uri,
        public RoleEnum $role,
        public ?string $characterName = null,
        public ?string $posterUrl = null,
    ) {}
}