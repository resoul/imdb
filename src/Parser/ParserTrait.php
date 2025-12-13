<?php

declare(strict_types=1);

namespace Resoul\Imdb\Parser;

use DOMDocument;

trait ParserTrait
{
    protected DOMDocument $dom;

    public function load(DOMDocument $dom): void
    {
        $this->dom = $dom;
    }
}