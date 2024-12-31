<?php
namespace resoul\imdb\model;

use resoul\imdb\model\enum\DistributorEnum;
use resoul\imdb\model\enum\FilmTypeEnum;
use resoul\imdb\model\enum\GenreEnum;

class Film
{
    /**
     * @var array<Actor>
     */
    private ?array $cast;
    private ?Gross $gross;
    private ?DistributorEnum $distributor;

    /**
     * @var array<GenreEnum>
     */
    private array $genres;
    private string $uid;
    private FilmTypeEnum $type;
    private string $releaseUid;
    private string $original;
    private string $description;
    private string $poster;
    private string $duration;
    private string $certificate;
    private ?int $budget;
    private ?int $opening;
    private ?int $openingTheaters;
    private ?int $wideRelease;
    private string $releaseDate;

    public function __construct(
        string $uid,
        string $releaseUid,
        string $original,
        string $poster,
        string $description,
        string $releaseDate,
        string $certificate,
        string $duration,
        array $genres,
        FilmTypeEnum $type,
        ?int $opening = null,
        ?int $openingTheaters = null,
        ?int $wideRelease = null,
        ?int $budget = null,
        ?Gross $gross = null,
        ?array $cast = null,
        ?DistributorEnum $distributor = null,
    ) {
        $this->releaseUid = $releaseUid;
        $this->cast = $cast;
        $this->genres = $genres;
        $this->gross = $gross;
        $this->distributor = $distributor;
        $this->uid = $uid;
        $this->poster = $poster;
        $this->original = $original;
        $this->description = $description;
        $this->certificate = $certificate;
        $this->duration = $duration;
        $this->releaseDate = $releaseDate;
        $this->budget = $budget;
        $this->opening = $opening;
        $this->openingTheaters = $openingTheaters;
        $this->wideRelease = $wideRelease;
        $this->type = $type;
    }

    public function getUID(): string
    {
        return $this->uid;
    }

    public function getRelease(): string
    {
        return $this->releaseUid;
    }

    public function getOriginal(): string
    {
        return $this->original;
    }

    public function getPoster(): string
    {
        return $this->poster;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getReleaseDate(): string
    {
        return $this->releaseDate;
    }

    public function getDuration(): string
    {
        return $this->duration;
    }

    public function getCertificate(): string
    {
        return $this->certificate;
    }

    public function getBudget(): ?int
    {
        return $this->budget;
    }

    public function getOpening(): ?int
    {
        return $this->opening;
    }

    public function getWideRelease(): ?int
    {
        return $this->wideRelease;
    }

    public function getOpeningTheaters(): ?int
    {
        return $this->openingTheaters;
    }

    /**
     * @return ?array<Actor>
     */
    public function getCast(): ?array
    {
        return $this->cast;
    }

    public function getGross(): Gross
    {
        return $this->gross;
    }

    public function getType(): FilmTypeEnum
    {
        return $this->type;
    }

    public function getDistributor(): ?DistributorEnum
    {
        return $this->distributor;
    }

    /**
     * @return array<GenreEnum>
    */
    public function getGenres(): array
    {
        return $this->genres;
    }
}