<?php

declare(strict_types=1);

namespace Resoul\Imdb\Contracts;

interface HtmlFetcherInterface
{
    public function fetch(string $url, bool $pro = false): string;
}