<?php
namespace resoul\imdb\model;

use resoul\imdb\model\enum\DistributorEnum;
use resoul\imdb\model\enum\FilmTypeEnum;
use resoul\imdb\model\enum\GenreEnum;

/**
 * Film Model Class
 *
 * Represents comprehensive film information including box office performance,
 * cast and crew details, technical specifications, and distribution data.
 *
 * @package resoul\imdb\model
 * @author resoul
 * @version 0.1.3
 * @since 0.1.0
 */
class Film
{
    /**
     * Array of cast and crew members
     * @var array<Actor>|null
     */
    private ?array $cast;

    /**
     * Worldwide box office gross breakdown
     */
    private ?Gross $gross;

    /**
     * Film distribution company
     */
    private ?DistributorEnum $distributor;

    /**
     * Array of film genres
     * @var array<GenreEnum>
     */
    private array $genres;

    /**
     * IMDB Pro URL identifier
     */
    private string $uid;

    /**
     * Film type (movie or TV series)
     */
    private FilmTypeEnum $type;

    /**
     * Box Office Mojo URL identifier
     */
    private string $releaseUid;

    /**
     * Original film title
     */
    private string $original;

    /**
     * Plot description/summary
     */
    private string $description;

    /**
     * Poster image URL
     */
    private string $poster;

    /**
     * Runtime duration in minutes
     */
    private string $duration;

    /**
     * MPAA rating certificate
     */
    private string $certificate;

    /**
     * Production budget in USD (null if unknown)
     */
    private ?int $budget;

    /**
     * Opening weekend gross in USD (null if unknown)
     */
    private ?int $opening;

    /**
     * Opening weekend theater count (null if unknown)
     */
    private ?int $openingTheaters;

    /**
     * Maximum theater count during release (null if unknown)
     */
    private ?int $wideRelease;

    /**
     * Number of seasons (for TV series, 0 for movies)
     */
    private int $seasons;

    /**
     * Release date in Y-m-d format
     */
    private string $releaseDate;

    /**
     * International release summary text (null if not available)
     */
    private ?string $releaseSummary;

    /**
     * Initialize a new Film instance with comprehensive movie data.
     *
     * Creates a complete film record containing both basic information
     * (title, description, poster) and detailed data (cast, financial
     * performance, distribution details).
     *
     * @param string $uid IMDB Pro URL for the film
     * @param string $releaseUid Box Office Mojo URL for box office data
     * @param string $original Original film title
     * @param string $poster URL to poster image
     * @param string $description Plot summary or description
     * @param string $releaseDate Release date in Y-m-d format
     * @param string $certificate MPAA rating (G, PG, PG-13, R, etc.)
     * @param string $duration Runtime in minutes
     * @param array $genres Array of GenreEnum values
     * @param FilmTypeEnum $type Movie or TV series designation
     * @param int|null $opening Opening weekend gross in USD
     * @param int|null $openingTheaters Opening weekend theater count
     * @param int|null $wideRelease Maximum theater count during release
     * @param string|null $releaseSummary International release information
     * @param int|null $budget Production budget in USD
     * @param int $seasons Number of seasons (TV series only)
     * @param Gross|null $gross Worldwide box office breakdown
     * @param array|null $cast Array of Actor objects for cast and crew
     * @param DistributorEnum|null $distributor Distribution company
     *
     * @example Create a film instance:
     * ```php
     * $film = new Film(
     *     uid: 'https://pro.imdb.com/title/tt123456/',
     *     releaseUid: 'https://www.boxofficemojo.com/title/tt123456/',
     *     original: 'The Example Movie',
     *     poster: 'https://example.com/poster.jpg',
     *     description: 'An exciting adventure film...',
     *     releaseDate: '2024-05-15',
     *     certificate: 'PG-13',
     *     duration: '120',
     *     genres: [GenreEnum::ACTION, GenreEnum::ADVENTURE],
     *     type: FilmTypeEnum::MOVIE
     * );
     * ```
     *
     * @since 0.1.0
     */
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
        ?string $releaseSummary = null,
        ?int $budget = null,
        int $seasons = 0,
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
        $this->releaseSummary = $releaseSummary;
        $this->seasons = $seasons;
    }

    /**
     * Get the IMDB Pro URL identifier for this film.
     *
     * @return string IMDB Pro URL (e.g., 'https://pro.imdb.com/title/tt123456/')
     *
     * @example
     * ```php
     * echo "IMDB: " . $film->getUID();
     * ```
     *
     * @since 0.1.0
     */
    public function getUID(): string
    {
        return $this->uid;
    }

    /**
     * Get the Box Office Mojo URL for this film's box office data.
     *
     * @return string Box Office Mojo URL
     *
     * @example
     * ```php
     * echo "Box Office Data: " . $film->getRelease();
     * ```
     *
     * @since 0.1.0
     */
    public function getRelease(): string
    {
        return $this->releaseUid;
    }

    /**
     * Get the original film title.
     *
     * @return string Film title as it appears in official records
     *
     * @example
     * ```php
     * echo "Now playing: " . $film->getOriginal();
     * ```
     *
     * @since 0.1.0
     */
    public function getOriginal(): string
    {
        return $this->original;
    }

    /**
     * Get the poster image URL.
     *
     * Returns a direct link to the film's poster image, typically
     * hosted on IMDB's content delivery network.
     *
     * @return string URL to poster image
     *
     * @example
     * ```php
     * echo '<img src="' . $film->getPoster() . '" alt="' . $film->getOriginal() . '">';
     * ```
     *
     * @since 0.1.0
     */
    public function getPoster(): string
    {
        return $this->poster;
    }

    /**
     * Get the film's plot description or summary.
     *
     * @return string Plot summary text
     *
     * @example
     * ```php
     * echo "Plot: " . substr($film->getDescription(), 0, 100) . "...";
     * ```
     *
     * @since 0.1.0
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * Get the film's release date.
     *
     * @return string Release date in Y-m-d format (e.g., '2024-05-15')
     *
     * @example
     * ```php
     * $date = DateTime::createFromFormat('Y-m-d', $film->getReleaseDate());
     * echo "Released: " . $date->format('F j, Y');
     * ```
     *
     * @since 0.1.0
     */
    public function getReleaseDate(): string
    {
        return $this->releaseDate;
    }

    /**
     * Get the runtime duration in minutes.
     *
     * @return string Duration as string (e.g., '120' for 2 hours)
     *
     * @example
     * ```php
     * $minutes = (int) $film->getDuration();
     * $hours = floor($minutes / 60);
     * $mins = $minutes % 60;
     * echo "Runtime: {$hours}h {$mins}m";
     * ```
     *
     * @since 0.1.0
     */
    public function getDuration(): string
    {
        return $this->duration;
    }

    /**
     * Get the MPAA rating certificate.
     *
     * @return string Rating certificate (G, PG, PG-13, R, NC-17, etc.)
     *
     * @example
     * ```php
     * echo "Rated: " . $film->getCertificate();
     * ```
     *
     * @since 0.1.0
     */
    public function getCertificate(): string
    {
        return $this->certificate;
    }

    /**
     * Get the production budget in USD.
     *
     * @return int|null Budget amount in dollars, null if not available
     *
     * @example
     * ```php
     * if ($budget = $film->getBudget()) {
     *     echo "Budget: $" . number_format($budget);
     * }
     * ```
     *
     * @since 0.1.0
     */
    public function getBudget(): ?int
    {
        return $this->budget;
    }

    /**
     * Get the opening weekend gross revenue.
     *
     * @return int|null Opening weekend gross in USD, null if not available
     *
     * @example
     * ```php
     * if ($opening = $film->getOpening()) {
     *     echo "Opening Weekend: $" . number_format($opening);
     * }
     * ```
     *
     * @since 0.1.0
     */
    public function getOpening(): ?int
    {
        return $this->opening;
    }

    /**
     * Get the international release summary information.
     *
     * @return string|null Release summary text or null if not available
     *
     * @example
     * ```php
     * if ($summary = $film->getReleaseSummary()) {
     *     echo "International Release: " . $summary;
     * }
     * ```
     *
     * @since 0.1.0
     */
    public function getReleaseSummary(): ?string
    {
        return $this->releaseSummary;
    }

    /**
     * Get the widest theater release count.
     *
     * @return int|null Maximum number of theaters, null if not available
     *
     * @example
     * ```php
     * if ($wide = $film->getWideRelease()) {
     *     echo "Widest Release: " . number_format($wide) . " theaters";
     * }
     * ```
     *
     * @since 0.1.0
     */
    public function getWideRelease(): ?int
    {
        return $this->wideRelease;
    }

    /**
     * Get the number of seasons (for TV series).
     *
     * @return int Number of seasons, 0 for movies
     *
     * @example
     * ```php
     * if ($film->getSeasons() > 0) {
     *     echo "TV Series with " . $film->getSeasons() . " seasons";
     * } else {
     *     echo "Feature film";
     * }
     * ```
     *
     * @since 0.1.0
     */
    public function getSeasons(): int
    {
        return $this->seasons;
    }

    /**
     * Get the opening weekend theater count.
     *
     * @return int|null Number of theaters during opening weekend, null if not available
     *
     * @example
     * ```php
     * if ($theaters = $film->getOpeningTheaters()) {
     *     $perTheater = $film->getOpening() / $theaters;
     *     echo "Per-theater average: $" . number_format($perTheater);
     * }
     * ```
     *
     * @since 0.1.0
     */
    public function getOpeningTheaters(): ?int
    {
        return $this->openingTheaters;
    }

    /**
     * Get the cast and crew information.
     *
     * @return array<Actor>|null Array of Actor objects or null if not available
     *
     * @example
     * ```php
     * if ($cast = $film->getCast()) {
     *     foreach ($cast as $person) {
     *         echo $person->getRole()->name . ": " . $person->getOriginal() . "\n";
     *     }
     * }
     * ```
     *
     * @since 0.1.0
     */
    public function getCast(): ?array
    {
        return $this->cast;
    }

    /**
     * Get the worldwide box office gross breakdown.
     *
     * @return Gross Box office performance data
     *
     * @example
     * ```php
     * $gross = $film->getGross();
     * echo "Domestic: $" . number_format($gross->getDomestic()) . "\n";
     * echo "International: $" . number_format($gross->getInternational()) . "\n";
     * echo "Worldwide: $" . number_format($gross->getWorldwide()) . "\n";
     * ```
     *
     * @since 0.1.0
     */
    public function getGross(): Gross
    {
        return $this->gross;
    }

    /**
     * Get the film type (movie or TV series).
     *
     * @return FilmTypeEnum Film type enumeration
     *
     * @example
     * ```php
     * if ($film->getType() === FilmTypeEnum::MOVIE) {
     *     echo "This is a feature film";
     * } else {
     *     echo "This is a TV series";
     * }
     * ```
     *
     * @since 0.1.0
     */
    public function getType(): FilmTypeEnum
    {
        return $this->type;
    }

    /**
     * Get the distribution company.
     *
     * @return DistributorEnum|null Distribution company or null if unknown
     *
     * @example
     * ```php
     * if ($distributor = $film->getDistributor()) {
     *     echo "Distributed by: " . $distributor->name;
     * }
     * ```
     *
     * @since 0.1.0
     */
    public function getDistributor(): ?DistributorEnum
    {
        return $this->distributor;
    }

    /**
     * Get the film's genres.
     *
     * @return array<GenreEnum> Array of genre enumerations
     *
     * @example
     * ```php
     * $genreNames = array_map(fn($genre) => $genre->name, $film->getGenres());
     * echo "Genres: " . implode(', ', $genreNames);
     * ```
     *
     * @since 0.1.0
     */
    public function getGenres(): array
    {
        return $this->genres;
    }
}