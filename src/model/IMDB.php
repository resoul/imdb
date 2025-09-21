<?php
namespace resoul\imdb\model;

/**
 * IMDB Container Model Class
 *
 * Main container for parsed box office data, holding a collection
 * of Release objects along with descriptive metadata.
 *
 * @package resoul\imdb\model
 * @author resoul
 * @version 0.1.3
 * @since 0.1.0
 */
class IMDB
{
    /**
     * Descriptive title for this dataset
     */
    private string $title;

    /**
     * Array of release data
     * @var array<Release>
     */
    private array $release;

    /**
     * Initialize a new IMDB container with box office data.
     *
     * Creates a container holding multiple Release objects along with
     * a descriptive title indicating the time period or source.
     *
     * @param string $title Descriptive title (e.g., "Weekend Box Office Nov 29-Dec 1, 2024")
     * @param array $release Array of Release objects
     *
     * @example
     * ```php
     * $imdb = new IMDB(
     *     title: "Weekend Box Office Nov 29-Dec 1, 2024",
     *     release: [$release1, $release2, $release3]
     * );
     * ```
     *
     * @since 0.1.0
     */
    public function __construct(string $title, array $release)
    {
        $this->title = $title;
        $this->release = $release;
    }

    /**
     * Get all release data.
     *
     * @return array<Release> Array of Release objects ordered by ranking
     *
     * @example
     * ```php
     * foreach ($imdb->getReleases() as $release) {
     *     echo sprintf(
     *         "#%d: %s - $%s\n",
     *         $release->getRank(),
     *         $release->getRelease(),
     *         number_format($release->getGross())
     *     );
     * }
     * ```
     *
     * @since 0.1.0
     */
    public function getReleases(): array
    {
        return $this->release;
    }

    /**
     * Get the descriptive title for this dataset.
     *
     * @return string Dataset title indicating source or time period
     *
     * @example
     * ```php
     * echo "=== " . $imdb->getTitle() . " ===\n";
     * ```
     *
     * @since 0.1.0
     */
    public function getTitle(): string
    {
        return $this->title;
    }
}