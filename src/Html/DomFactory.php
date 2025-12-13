<?php

declare(strict_types=1);

namespace Resoul\Imdb\Html;

use Resoul\Imdb\Contracts\DomFactoryInterface;
use DOMDocument;

final class DomFactory implements DomFactoryInterface
{
    public function createFromHtml(string $html): DOMDocument
    {
        $dom = new DOMDocument();

        libxml_use_internal_errors(true);
        $dom->loadHTML($html);
        libxml_clear_errors();
        libxml_use_internal_errors(false);

        return $dom;
    }
}