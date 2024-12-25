<?php
namespace resoul\imdb\model;

use resoul\imdb\model\enum\RoleEnum;

class Actor
{
    private ?string $roleName;
    private RoleEnum $role;
    private string $original;
    private string $uri;

    private ?string $poster;

    public function __construct(
        string $original,
        string $uri,
        RoleEnum $role,
        ?string $roleName = null,
        ?string $poster = null,
    ) {
        $this->original = $original;
        $this->uri = $uri;
        $this->role = $role;
        $this->roleName = $roleName;
        $this->poster = $poster;
    }

    public function getUri(): string
    {
        return $this->uri;
    }

    public function getOriginal(): string
    {
        return $this->original;
    }

    public function getRole(): RoleEnum
    {
        return $this->role;
    }

    public function getRoleName(): ?string
    {
        return $this->roleName;
    }

    public function getPoster(): ?string
    {
        return $this->poster;
    }
}