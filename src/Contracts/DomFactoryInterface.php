<?php

declare(strict_types=1);

namespace Resoul\Imdb\Contracts;

use DOMDocument;

interface DomFactoryInterface
{
    public function createFromHtml(string $html): DOMDocument;
}