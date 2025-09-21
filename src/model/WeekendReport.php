<?php
namespace resoul\imdb\model;

use JsonSerializable;

/**
 * Weekend Report Class
 */
class WeekendReport implements JsonSerializable
{
    private string $weekend;
    private array $releases;
    private array $metrics;
    private array $insights;

    public function __construct(string $weekend, array $releases, array $metrics, array $insights)
    {
        $this->weekend = $weekend;
        $this->releases = $releases;
        $this->metrics = $metrics;
        $this->insights = $insights;
    }

    public function getTotalGross(): int
    {
        return $this->metrics['total_gross'];
    }

    public function getTotalTheaters(): int
    {
        return $this->metrics['total_theaters'];
    }

    public function getAveragePerTheater(): float
    {
        return $this->metrics['average_per_theater'];
    }

    public function getTopFilm(): string
    {
        return !empty($this->releases) ? $this->releases[0]->getRelease() : '';
    }

    public function getNewReleases(): array
    {
        return array_filter($this->releases, fn($r) => $r->getWeeks() <= 1);
    }

    public function getWeekend(): string
    {
        return $this->weekend;
    }

    public function getReleases(): array
    {
        return $this->releases;
    }

    public function getMetrics(): array
    {
        return $this->metrics;
    }

    public function getInsights(): array
    {
        return $this->insights;
    }

    public function toJson(): string
    {
        return json_encode($this, JSON_PRETTY_PRINT);
    }

    public function jsonSerialize(): array
    {
        return [
            'weekend' => $this->weekend,
            'summary' => [
                'total_gross' => $this->metrics['total_gross'],
                'total_theaters' => $this->metrics['total_theaters'],
                'average_per_theater' => round($this->metrics['average_per_theater'], 2),
                'film_count' => $this->metrics['film_count'],
                'top_film' => $this->getTopFilm()
            ],
            'insights' => $this->insights,
            'releases' => array_map(fn($release) => [
                'rank' => $release->getRank(),
                'title' => $release->getRelease(),
                'gross' => $release->getGross(),
                'theaters' => $release->getTheaters(),
                'weeks' => $release->getWeeks(),
                'per_theater' => $release->getTheaters() > 0 ? round($release->getGross() / $release->getTheaters(), 2) : 0
            ], $this->releases)
        ];
    }
}