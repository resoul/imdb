<?php

declare(strict_types=1);

namespace Resoul\Imdb\Contracts;

interface CacheInterface
{
    public function getCacheFolder(): string;
    public function ensureCacheFolderExists(): void;
}