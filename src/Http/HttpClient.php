<?php

declare(strict_types=1);

namespace Resoul\Imdb\Http;

use Resoul\Imdb\Contracts\HttpClientInterface;
use GuzzleHttp\Client;

final class HttpClient implements HttpClientInterface
{
    private Client $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function get(string $uri): string
    {
        $response = $this->client->request('GET', $uri);
        return (string) $response->getBody();
    }
}