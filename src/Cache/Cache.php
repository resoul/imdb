<?php

declare(strict_types=1);

namespace Resoul\Imdb\Cache;

use Resoul\Imdb\Contracts\CacheInterface;
use Resoul\Imdb\Exception\MojoException;

final class Cache implements CacheInterface
{
    private string $cacheFolder;

    public function __construct(string $path)
    {
        $this->cacheFolder = rtrim($path, DIRECTORY_SEPARATOR);
    }

    public function getCacheFolder(): string
    {
        return $this->cacheFolder;
    }

    public function ensureCacheFolderExists(): void
    {
        if (is_dir($this->cacheFolder)) {
            return;
        }

        if (!@mkdir($this->cacheFolder, 0755, true) && !is_dir($this->cacheFolder)) {
            throw new MojoException(
                sprintf('Could not create cache folder: %s', $this->cacheFolder)
            );
        }

        if (!is_writable($this->cacheFolder)) {
            throw new MojoException(
                sprintf('Cache folder is not writable: %s', $this->cacheFolder)
            );
        }
    }
}