<?php
namespace resoul\imdb\model;

/**
 * Release Model Class
 *
 * Represents box office performance data for a single film release,
 * including ranking, revenue, theater counts, and performance metrics.
 *
 * @package resoul\imdb\model
 * @author resoul
 * @version 0.1.3
 * @since 0.1.0
 */
class Release
{
    /**
     * Box office ranking position
     */
    private int $rank;

    /**
     * Previous week's ranking (0 if new entry or not applicable)
     */
    private int $lastWeek;

    /**
     * Film title
     */
    private string $release;

    /**
     * Box Office Mojo URL for this release
     */
    private string $uri;

    /**
     * Gross revenue for the period in USD
     */
    private int $gross;

    /**
     * Number of theaters showing the film
     */
    private int $theaters;

    /**
     * Total cumulative gross revenue in USD
     */
    private int $total;

    /**
     * Number of weeks in theatrical release
     */
    private int $weeks;

    /**
     * Detailed film information (populated after parsing)
     */
    private Film $film;

    /**
     * Initialize a new Release instance with box office performance data.
     *
     * Creates a release record containing ranking information and financial
     * performance metrics for a specific time period (weekend or year).
     *
     * @param string $release Film title
     * @param string $uri Box Office Mojo URL for detailed data
     * @param int $theaters Number of theaters (0 for yearly data)
     * @param int $rank Box office ranking position
     * @param int $lastWeek Previous week's rank (0 if not applicable)
     * @param int $weeks Number of weeks in release (0 for yearly data)
     * @param int $gross Period gross revenue in USD
     * @param int $total Total cumulative gross in USD
     *
     * @example Create a weekend release:
     * ```php
     * $release = new Release(
     *     release: 'Top Gun: Maverick',
     *     uri: 'https://www.boxofficemojo.com/title/tt1745960/',
     *     theaters: 4735,
     *     rank: 1,
     *     lastWeek: 1,
     *     weeks: 4,
     *     gross: 44000000,
     *     total: 521700000
     * );
     * ```
     *
     * @since 0.1.0
     */
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

    /**
     * Get the Box Office Mojo URL for detailed information.
     *
     * @return string URL to Box Office Mojo page
     *
     * @example
     * ```php
     * echo '<a href="' . $release->getUri() . '">View Details</a>';
     * ```
     *
     * @since 0.1.0
     */
    public function getUri(): string
    {
        return $this->uri;
    }

    /**
     * Get the film title.
     *
     * @return string Film title as it appears in box office rankings
     *
     * @example
     * ```php
     * echo "#" . $release->getRank() . ": " . $release->getRelease();
     * ```
     *
     * @since 0.1.0
     */
    public function getRelease(): string
    {
        return $this->release;
    }

    /**
     * Get the number of theaters showing this film.
     *
     * @return int Theater count (0 for yearly data where not available)
     *
     * @example
     * ```php
     * if ($release->getTheaters() > 0) {
     *     $perTheater = $release->getGross() / $release->getTheaters();
     *     echo "Per-theater average: $" . number_format($perTheater);
     * }
     * ```
     *
     * @since 0.1.0
     */
    public function getTheaters(): int
    {
        return $this->theaters;
    }

    /**
     * Get the number of weeks in theatrical release.
     *
     * @return int Weeks in release (0 for yearly data where not applicable)
     *
     * @example
     * ```php
     * if ($release->getWeeks() == 1) {
     *     echo "Opening weekend";
     * } else {
     *     echo "Week " . $release->getWeeks();
     * }
     * ```
     *
     * @since 0.1.0
     */
    public function getWeeks(): int
    {
        return $this->weeks;
    }

    /**
     * Get the previous week's ranking position.
     *
     * @return int Last week's rank (0 if new entry or not applicable)
     *
     * @example
     * ```php
     * $current = $release->getRank();
     * $last = $release->getLastWeek();
     *
     * if ($last == 0) {
     *     echo "NEW";
     * } elseif ($current < $last) {
     *     echo "↑" . ($last - $current);
     * } elseif ($current > $last) {
     *     echo "↓" . ($current - $last);
     * } else {
     *     echo "=";
     * }
     * ```
     *
     * @since 0.1.0
     */
    public function getLastWeek(): int
    {
        return $this->lastWeek;
    }

    /**
     * Get the total cumulative gross revenue.
     *
     * @return int Total gross in USD
     *
     * @example
     * ```php
     * echo "Total Gross: $" . number_format($release->getTotal());
     * ```
     *
     * @since 0.1.0
     */
    public function getTotal(): int
    {
        return $this->total;
    }

    /**
     * Get the gross revenue for this period.
     *
     * @return int Period gross in USD (weekend gross or yearly total)
     *
     * @example
     * ```php
     * echo "Weekend Gross: $" . number_format($release->getGross());
     * ```
     *
     * @since 0.1.0
     */
    public function getGross(): int
    {
        return $this->gross;
    }

    /**
     * Get the box office ranking position.
     *
     * @return int Ranking position (1 = #1 at box office)
     *
     * @example
     * ```php
     * echo "#" . $release->getRank() . " at the box office";
     * ```
     *
     * @since 0.1.0
     */
    public function getRank(): int
    {
        return $this->rank;
    }

    /**
     * Set the detailed film information for this release.
     *
     * This method is called automatically by the Parser after extracting
     * detailed film data from IMDB Pro to enrich the basic release data.
     *
     * @param Film $film Complete film information object
     *
     * @internal This method is called by Parser::run()
     * @since 0.1.0
     */
    public function setFilm(Film $film): void
    {
        $this->film = $film;
    }

    /**
     * Get the detailed film information.
     *
     * @return Film Complete film data including cast, crew, and technical details
     *
     * @example
     * ```php
     * $film = $release->getFilm();
     * echo "Director: " . $film->getDirector();
     * echo "Runtime: " . $film->getDuration() . " minutes";
     * ```
     *
     * @since 0.1.0
     */
    public function getFilm(): Film
    {
        return $this->film;
    }
}