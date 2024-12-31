<?php
namespace resoul\imdb\model;

class Release
{
    private int $rank;
    private int $lastWeek;
    private string $release;
    private string $uri;
    private int $gross;
    private int $theaters;
    private int $total;
    private int $weeks;
    private Film $film;

    public function __construct(
        string $release,
        string $uri,
        int $theaters,
        int $rank,
        int $lastWeek,
        int $weeks,
        int $gross,
        int $total,
    ) {
        $this->release = $release;
        $this->uri = $uri;
        $this->theaters = $theaters;
        $this->rank = $rank;
        $this->lastWeek = $lastWeek;
        $this->weeks = $weeks;
        $this->total = $total;
        $this->gross = $gross;
    }

    public function getUri(): string
    {
        return $this->uri;
    }

    public function getRelease(): string
    {
        return $this->release;
    }

    public function getTheaters(): int
    {
        return $this->theaters;
    }

    public function getWeeks(): int
    {
        return $this->weeks;
    }

    public function getLastWeek(): int
    {
        return $this->lastWeek;
    }

    public function getTotal(): int
    {
        return $this->total;
    }

    public function getGross(): int
    {
        return $this->gross;
    }

    public function getRank(): int
    {
        return $this->rank;
    }

    public function setFilm(Film $film): void
    {
        $this->film = $film;
    }

    public function getFilm(): Film
    {
        return $this->film;
    }
}