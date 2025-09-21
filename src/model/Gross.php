<?php
namespace resoul\imdb\model;
/**
 * Gross Revenue Model Class
 *
 * Represents worldwide box office performance broken down by market regions.
 *
 * @package resoul\imdb\model
 * @author resoul
 * @version 0.1.3
 * @since 0.1.0
 */
class Gross
{
    /**
     * International box office gross (excluding US/Canada)
     */
    private ?int $international;

    /**
     * Worldwide total box office gross
     */
    private ?int $worldwide;

    /**
     * Domestic box office gross (US/Canada)
     */
    private ?int $domestic;

    /**
     * Initialize box office gross data with regional breakdown.
     *
     * @param int|null $domestic US/Canada gross revenue in USD
     * @param int|null $international International gross revenue in USD
     * @param int|null $worldwide Worldwide total gross revenue in USD
     *
     * @example
     * ```php
     * $gross = new Gross(
     *     domestic: 400000000,
     *     international: 600000000,
     *     worldwide: 1000000000
     * );
     * ```
     *
     * @since 0.1.0
     */
    public function __construct(int $domestic = null, int $international = null, int $worldwide = null)
    {
        $this->international = $international;
        $this->worldwide = $worldwide;
        $this->domestic = $domestic;
    }

    /**
     * Get the worldwide total gross revenue.
     *
     * @return int|null Worldwide gross in USD or null if not available
     *
     * @example
     * ```php
     * if ($worldwide = $gross->getWorldwide()) {
     *     echo "Worldwide: $" . number_format($worldwide);
     * }
     * ```
     *
     * @since 0.1.0
     */
    public function getWorldwide(): ?int
    {
        return $this->worldwide;
    }

    /**
     * Get the domestic box office gross (US/Canada).
     *
     * @return int|null Domestic gross in USD or null if not available
     *
     * @example
     * ```php
     * if ($domestic = $gross->getDomestic()) {
     *     echo "Domestic: $" . number_format($domestic);
     * }
     * ```
     *
     * @since 0.1.0
     */
    public function getDomestic(): ?int
    {
        return $this->domestic;
    }

    /**
     * Get the international box office gross (excluding US/Canada).
     *
     * @return int|null International gross in USD or null if not available
     *
     * @example
     * ```php
     * if ($international = $gross->getInternational()) {
     *     echo "International: $" . number_format($international);
     * }
     * ```
     *
     * @since 0.1.0
     */
    public function getInternational(): ?int
    {
        return $this->international;
    }
}