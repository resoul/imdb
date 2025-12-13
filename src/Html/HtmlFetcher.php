<?php

declare(strict_types=1);

namespace Resoul\Imdb\Html;

use Resoul\Imdb\Contracts\CacheInterface;
use Resoul\Imdb\Contracts\HtmlFetcherInterface;
use Resoul\Imdb\Contracts\HttpClientInterface;

final class HtmlFetcher implements HtmlFetcherInterface
{
    private HttpClientInterface $http;
    private CacheInterface $cache;

    public function __construct(HttpClientInterface $http, CacheInterface $cache)
    {
        $this->http = $http;
        $this->cache = $cache;
    }

    public function fetch(string $url, bool $pro = false): string
    {
        $this->cache->ensureCacheFolderExists();

        $path = parse_url($url, PHP_URL_PATH) ?? '';
        $file = str_replace(['/', 'weekend', 'release', 'title'], '', $path);

        if ($pro) {
            $file = 'pro.' . $file;
        }

        $filePath = sprintf('%s/%s.html', $this->cache->getCacheFolder(), $file);

        if (!file_exists($filePath)) {
            file_put_contents($filePath, $this->http->get($url));
        }

        return file_get_contents($filePath);
    }
}