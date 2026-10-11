<?php

declare(strict_types=1);

namespace Resoul\Imdb\Contracts;

interface HttpClientInterface
{
    public function get(string $uri): string;
}