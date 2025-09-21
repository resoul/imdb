<?php
/**
 * Box Office Mojo and IMDB Pro Parser
 *
 * This class provides functionality to scrape box office data from Box Office Mojo
 * and detailed film information from IMDB Pro. It supports parsing weekend rankings,
 * yearly box office data, and individual film details.
 *
 * @package resoul\imdb
 * @author resoul
 * @version 0.1.3
 * @since 0.1.0
 *
 * @example Basic usage:
 * ```php
 * $parser = new Parser();
 * $data = $parser
 *     ->setCacheFolder('/tmp/cache/')
 *     ->byWeekend('2024W48')
 *     ->run();
 *
 * foreach ($data->getReleases() as $release) {
 *     echo $release->getRelease() . ': $' . number_format($release->getGross()) . "\n";
 * }
 * ```
 */

namespace resoul\imdb;

use Exception;
use DOMDocument;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use resoul\imdb\model\enum\DistributorEnum;
use resoul\imdb\model\IMDB;
use resoul\imdb\model\imdb\Title;
use resoul\imdb\model\Release;
use resoul\imdb\model\Film;
use resoul\imdb\model\Gross;

class Parser
{
    /**
     * Cache directory path for storing downloaded HTML files
     */
    private string $cacheFolder;

    /**
     * Target URL to parse
     */
    private string $uri;

    /**
     * HTTP client instance for making requests
     */
    private Client $client;

    /**
     * Parser state: 1=weekend, 2=yearly, 3=custom source
     */
    private int $state;

    /**
     * Title or identifier for the parsing operation
     */
    private string $title;

    /**
     * Initialize the parser with default HTTP client configuration.
     *
     * Sets up Guzzle HTTP client with SSL verification disabled and 30-second timeout.
     *
     * @since 0.1.0
     */
    public function __construct()
    {
        $this->client = new Client([
            RequestOptions::VERIFY => false,
            RequestOptions::TIMEOUT => 30,
        ]);
    }

    /**
     * Execute the parsing operation and return structured box office data.
     *
     * This method processes the configured source (weekend, yearly, or custom URL),
     * extracts box office rankings, and enriches each release with detailed film
     * information from IMDB Pro.
     *
     * @return IMDB Complete box office data with film details
     *
     * @throws Exception If cache directory cannot be created
     * @throws Exception If network request fails
     * @throws Exception If HTML parsing encounters errors
     * @throws Exception If required DOM elements are not found
     *
     * @example Weekend box office parsing:
     * ```php
     * $data = $parser->byWeekend('2024W48')->run();
     * echo "Found " . count($data->getReleases()) . " releases";
     * ```
     *
     * @example Yearly box office parsing:
     * ```php
     * $data = $parser->byYear('2024')->run();
     * $topFilm = $data->getReleases()[0];
     * echo "Top film: " . $topFilm->getRelease();
     * ```
     *
     * @since 0.1.0
     */
    public function run(): IMDB
    {
        $this->createCacheFolder();
        $instance = match ($this->state) {
            1 => $this->_parseTable(),
            default => $this->_parseYear()
        };

        foreach ($instance->getReleases() as $release) {
            $film = $this->_parseRelease($release);
            $release->setFilm($film);
        }

        return $instance;
    }

    /**
     * Parse detailed film information from IMDB Pro.
     *
     * This method extracts comprehensive film data including cast, crew,
     * plot summary, technical details, and poster images from IMDB Pro pages.
     *
     * @return Film Complete film information object
     *
     * @throws Exception If cache directory cannot be created
     * @throws Exception If network request fails
     * @throws Exception If IMDB Pro page parsing fails
     *
     * @example Parse specific film:
     * ```php
     * $film = $parser
     *     ->bySource('https://pro.imdb.com/title/tt123456/')
     *     ->parseTitle();
     *
     * echo "Title: " . $film->getOriginal() . "\n";
     * echo "Runtime: " . $film->getDuration() . " minutes\n";
     * echo "Cast: " . count($film->getCast()) . " people\n";
     * ```
     *
     * @since 0.1.0
     */
    public function parseTitle(): Film
    {
        $this->createCacheFolder();
        $title = (new Title)
            ->loadContent(content: $this->createDomDocument(uri: $this->uri, loadPro: true))
            ->getContent();

        return new Film(
            uid: $this->uri,
            releaseUid: $this->uri,
            original: $title['title'],
            poster: $title['poster'],
            description: $title['description'],
            releaseDate: '',
            certificate: $title['certificate'],
            duration: $title['running_time'],
            genres: $title['genres'],
            type: $title['type'],
            releaseSummary: $title['release_summary'] ?? null,
            seasons: $title['seasons'],
            cast: $title['person'] ?? null,
        );
    }

    /**
     * Parse weekend box office table data.
     *
     * Extracts box office rankings from Box Office Mojo weekend pages,
     * including rank, gross revenue, theater count, and weeks in release.
     *
     * @return IMDB Weekend box office data with up to 10 releases
     *
     * @throws Exception If HTML table structure is invalid
     * @throws Exception If required data fields are missing
     *
     * @internal This method is called by run() for weekend data
     * @since 0.1.0
     */
    private function _parseTable(): IMDB
    {
        $dom = $this->createDomDocument(uri: $this->uri);
        $content = $dom->getElementById('table');
        $table = $content->getElementsByTagName('table');

        $title = trim($dom->getElementsByTagName('h1')
            ->item(0)->nextSibling->childNodes->item(0)->textContent);

        $data = [];
        foreach ($table as $item) {
            $header = $content = [];
            foreach ($item->getElementsByTagName('tr') as $tr) {
                foreach ($tr->getElementsByTagName('th') as $cell) {
                    $header[] = trim($cell->nodeValue);
                }
                $body = [];
                foreach ($tr->getElementsByTagName('td') as $cell) {
                    $text = trim($cell->nodeValue);
                    $a = $cell->getElementsByTagName('a');
                    if ($a->length) {
                        foreach ($a as $link) {
                            $scheme = parse_url($link->getAttribute('href'));
                            if (!isset($scheme['host']) && isset($scheme['path'])) {
                                $text .= "|https://www.boxofficemojo.com" . $scheme['path'];
                            }
                        }
                    }
                    $body[] = $text;
                }
                if (count($header) == count($body)) {
                    $content[] = array_combine($header, $body);
                }
            }
            foreach ($content as $key => $value) {
                foreach ($value as $i => $v) {
                    switch ($i) {
                        case 'LW':
                            $data[$value['Rank']]['last_week'] = $v;
                            break;
                        case 'Release':
                            $explode = explode('|', $v);
                            $data[$value['Rank']]['movie'] = $explode[0];
                            if (isset($explode[1])) {
                                $data[$value['Rank']]['uri'] = $explode[1];
                            }
                            break;
                        case 'Gross':
                            $data[$value['Rank']]['gross'] = $v;
                            break;
                        case 'Theaters':
                            $data[$value['Rank']]['theaters'] = $v;
                            break;
                        case 'Total Gross':
                            $data[$value['Rank']]['total'] = $v;
                            break;
                        case 'Weeks':
                            $data[$value['Rank']]['weeks'] = $v;
                            break;
                    }
                }
            }
        }
        $result = [];
        foreach ($data as $position => $value) {
            if ($position > 10) continue;
            $result[] = new Release(
                release: (string) $value['movie'],
                uri: (string) $value['uri'],
                theaters: (int) str_replace(['$', ','], '', $value['theaters']) > 0 ? (int) str_replace(['$', ','], '', $value['theaters']) : 0,
                rank: $position,
                lastWeek: max((int)$value['last_week'], 0),
                weeks: max((int)$value['weeks'], 0),
                gross: (int) str_replace(['$', ','], '', $value['gross']) > 0 ? (int) str_replace(['$', ','], '', $value['gross']) : 0,
                total: (int) str_replace(['$', ','], '', $value['total']) > 0 ? (int) str_replace(['$', ','], '', $value['total']) : 0,
            );
        }

        return new IMDB(
            title: $title,
            release: $result
        );
    }

    /**
     * Extract detailed film information from Box Office Mojo and IMDB Pro.
     *
     * This method processes a Release object to gather comprehensive film data
     * including financial performance, distribution details, cast, crew, and
     * technical specifications from multiple sources.
     *
     * @param Release $release Release object containing basic box office data
     *
     * @return Film Complete film information with financial and creative details
     *
     * @throws Exception If Box Office Mojo page parsing fails
     * @throws Exception If IMDB Pro page cannot be accessed
     * @throws Exception If required film data elements are missing
     *
     * @internal This method is called by run() for each release
     * @since 0.1.0
     */
    private function _parseRelease(Release $release): Film
    {
        $dom = $this->createDomDocument(uri: $release->getUri());
        foreach ($dom->getElementsByTagName('br') as $br) {
            $br->remove();
        }
        $data = [];
        $distributors = array_flip(DistributorEnum::getLabels());
        foreach ($dom->getElementById('mojo-summary-details-discloser')->nextElementSibling->childNodes as $element) {
            if ($element->childElementCount == 2) {
                if ($element->getElementsByTagName('span')->item(0)->textContent == 'Distributor') {
                    foreach ($element->getElementsByTagName('span')->item(1)->getElementsByTagName('a') as $a) {
                        $a->remove();
                    }
                    if (isset($distributors[trim($element->getElementsByTagName('span')->item(1)->textContent)])) {
                        $data['distributor'] = DistributorEnum::tryFrom($distributors[trim($element->getElementsByTagName('span')->item(1)->textContent)]);
                    }
                }
                if ($element->getElementsByTagName('span')->item(0)->textContent == 'Opening') {
                    foreach ($element->getElementsByTagName('span') as $span) {
                        if ($span->getAttribute('class') == 'money') {
                            $data['opening'] = (int) str_replace([',', '$'], '', trim($span->textContent));
                            $span->remove();
                        }
                    }
                    foreach (explode(' ', trim($element->getElementsByTagName('span')->item(1)->textContent)) as $item) {
                        $item = str_replace(',', '', $item);
                        if (!empty($item) && ((int) $item) > 0) {
                            $data['opening_theaters'] = (int) $item;
                        }
                    }
                }
                if ($element->getElementsByTagName('span')->item(0)->textContent == 'Budget') {
                    foreach ($element->getElementsByTagName('span') as $span) {
                        if ($span->getAttribute('class') == 'money') {
                            $data['budget'] = (int) str_replace([',', '$'], '', trim($span->textContent));
                        }
                    }
                }
                if ($element->getElementsByTagName('span')->item(0)->textContent == 'Widest Release') {
                    $data['wide_release'] = (int) str_replace([',', ' theaters'], '', trim($element->getElementsByTagName('span')->item(1)->textContent));
                }
                if (trim(explode('(', $element->getElementsByTagName('span')->item(0)->textContent)[0]) == 'Release Date') {
                    $e = explode('(', $element->getElementsByTagName('span')->item(1)->textContent);
                    if (count($e) == 2) {
                        $data['release'] = date('Y-m-d', strtotime(trim(explode('(', $element->getElementsByTagName('span')->item(1)->textContent)[0])));
                    } else {
                        $data['release'] = date('Y-m-d', strtotime(trim(explode('-', $element->getElementsByTagName('span')->item(1)->textContent)[0])));
                    }
                }
            }
        }
        $path = $dom->getElementById('title-summary-refiner')->getElementsByTagName('a')
            ->item(0)->getAttribute('href');

        $data['release_uid'] = 'https://www.boxofficemojo.com' . parse_url($release->getUri())['path'];
        $data['uid'] = 'https://pro.imdb.com' . parse_url($path)['path'];

        $dom = $this->createDomDocument(uri: 'https://www.boxofficemojo.com' . parse_url($path)['path']);
        foreach ($dom->getElementsByTagName('br') as $br) {
            $br->remove();
        }

        $data['gross'] = [];
        foreach ($dom->getElementsByTagName('div') as $div) {
            if ($div->getAttribute('class') == 'a-section a-spacing-none mojo-performance-summary-table') {
                foreach ($div->getElementsByTagName('div') as $k => $element) {
                    foreach ($element->getElementsByTagName('span') as $item) {
                        if ($item->getAttribute('class') == 'money') {
                            $data['gross'][$k] = str_replace([',', '$'], '', trim($item->textContent));
                        }
                    }
                }
            }
        }

        if (isset($data['gross'])) {
            $gross = new Gross(
                domestic: $data['gross'][0] ?? null,
                international: $data['gross'][1] ?? null,
                worldwide: $data['gross'][2] ?? null
            );
            unset($data['gross']);
        }

        $title = (new Title)
            ->loadContent(content: $this->createDomDocument(uri: $data['uid'], loadPro: true))
            ->getContent();

        return new Film(
            uid: $data['uid'],
            releaseUid: $data['release_uid'],
            original: $title['title'],
            poster: $title['poster'],
            description: $title['description'],
            releaseDate: $data['release'],
            certificate: $title['certificate'],
            duration: $title['running_time'],
            genres: $title['genres'],
            type: $title['type'],
            opening: $data['opening'] ?? null,
            openingTheaters: $data['opening_theaters'] ?? null,
            wideRelease: $data['wide_release'] ?? null,
            releaseSummary: $title['release_summary'] ?? null,
            budget: $data['budget'] ?? null,
            gross: $gross ?? null,
            cast: $title['person'] ?? null,
            distributor: $data['distributor'] ?? null
        );
    }

    /**
     * Parse yearly box office data from Box Office Mojo.
     *
     * Extracts annual box office rankings, processing up to 30 releases
     * with their total gross revenues. Unlike weekend data, yearly data
     * doesn't include theater counts or weekly performance metrics.
     *
     * @return IMDB Yearly box office data with up to 30 releases
     *
     * @throws Exception If HTML table structure is invalid
     * @throws Exception If required data fields are missing
     *
     * @internal This method is called by run() for yearly data
     * @since 0.1.0
     */
    private function _parseYear(): IMDB
    {
        $dom = $this->createDomDocument(uri: $this->uri);

        $content = $dom->getElementById('table');
        $table = $content->getElementsByTagName('table');

        $data = [];
        foreach ($table as $item) {
            $header = $content = [];
            foreach ($item->getElementsByTagName('tr') as $tr) {
                foreach ($tr->getElementsByTagName('th') as $cell) {
                    $header[] = trim($cell->nodeValue);
                }
                $body = [];
                foreach ($tr->getElementsByTagName('td') as $cell) {
                    $text = trim($cell->nodeValue);
                    $a = $cell->getElementsByTagName('a');
                    if ($a->length) {
                        foreach ($a as $link) {
                            $scheme = parse_url($link->getAttribute('href'));
                            if (!isset($scheme['host']) && isset($scheme['path'])) {
                                $text .= "|https://www.boxofficemojo.com" . $scheme['path'];
                            }
                        }
                    }
                    $body[] = $text;
                }
                if (count($header) == count($body)) {
                    $content[] = array_combine($header, $body);
                }
            }
            foreach ($content as $key => $value) {
                foreach ($value as $i => $v) {
                    switch ($i) {
                        case 'LW':
                            $data[$value['Rank']]['last_week'] = $v;
                            break;
                        case 'Release':
                            $explode = explode('|', $v);
                            $data[$value['Rank']]['movie'] = $explode[0];
                            if (isset($explode[1])) {
                                $data[$value['Rank']]['uri'] = $explode[1];
                            }
                            break;
                        case 'Gross':
                            $data[$value['Rank']]['gross'] = $v;
                            break;
                        case 'Theaters':
                            $data[$value['Rank']]['theaters'] = $v;
                            break;
                        case 'Total Gross':
                            $data[$value['Rank']]['total'] = $v;
                            break;
                        case 'Weeks':
                            $data[$value['Rank']]['weeks'] = $v;
                            break;
                    }
                }
            }
        }

        // Convert to Release objects (limit to top 30 for yearly data)
        $result = [];
        foreach ($data as $position => $value) {
            if ($position > 30) continue;
            $result[] = new Release(
                release: (string) $value['movie'],
                uri: (string) $value['uri'],
                theaters: 0,
                rank: $position,
                lastWeek: 0,
                weeks: 0,
                gross: (int) str_replace(['$', ','], '', $value['gross']) > 0 ? (int) str_replace(['$', ','], '', $value['gross']) : 0,
                total: 0,
            );
        }

        return new IMDB(
            title: sprintf('Domestic Box Office For %s', $this->title),
            release: $result
        );
    }

    /**
     * Download and cache HTML content from the specified URL.
     *
     * This method implements a simple file-based caching system to avoid
     * redundant HTTP requests. Cached files are stored with sanitized
     * filenames based on the URL path.
     *
     * @param string $link Full URL to download
     * @param bool $pro Whether this is an IMDB Pro page (affects cache filename)
     *
     * @return string|false HTML content or false on failure
     *
     * @throws Exception If HTTP request fails
     *
     * @internal This method handles caching logic for all HTTP requests
     * @since 0.1.0
     */
    private function getHTML(string $link, bool $pro = false): false|string
    {
        // Generate cache filename from URL path
        $file = str_replace(['/', 'weekend', 'release', 'title'], '', parse_url($link)['path']);

        if ($pro) {
            $file = "pro.$file";
        }

        // Return cached content if exists
        if (!file_exists(sprintf($this->cacheFolder . "/%s.html", $file))) {
            echo ">> $file" . PHP_EOL;
            file_put_contents(sprintf($this->cacheFolder . "/%s.html", $file), $this->_request($link));
        }

        return file_get_contents(sprintf($this->cacheFolder . "/%s.html", $file));
    }

    /**
     * Execute HTTP GET request to the specified URL.
     *
     * Uses the configured Guzzle HTTP client to fetch content from the target URL.
     *
     * @param string $uri Target URL to request
     *
     * @return string Response body content
     *
     * @throws Exception If HTTP request fails or times out
     *
     * @internal This method performs the actual HTTP requests
     * @since 0.1.0
     */
    private function _request(string $uri): string
    {
        $response = $this->client->request('GET', $uri);
        return $response->getBody()->getContents();
    }

    /**
     * Configure parser to extract yearly box office data.
     *
     * Sets the parser to retrieve annual box office rankings from Box Office Mojo
     * for the specified year. Results include total gross revenues for the year
     * but exclude weekly performance metrics.
     *
     * @param string $year Year in YYYY format (e.g., "2024", "2023")
     *
     * @return static Returns self for method chaining
     *
     * @example Parse 2024 yearly data:
     * ```php
     * $data = $parser
     *     ->byYear('2024')
     *     ->run();
     *
     * echo "Top grossing film of 2024: " . $data->getReleases()[0]->getRelease();
     * ```
     *
     * @since 0.1.0
     */
    public function byYear(string $year): static
    {
        $this->state = 2;
        $this->title = $year;
        $this->uri = sprintf('https://www.boxofficemojo.com/year/%s/?grossesOption=totalGrosses', $year);
        return $this;
    }

    /**
     * Configure parser to extract data from a custom URL.
     *
     * Sets the parser to process data from any specified URL. This method
     * is useful for parsing specific Box Office Mojo pages or IMDB Pro URLs
     * that don't fit the standard weekend/yearly patterns.
     *
     * @param string $source Full URL to parse (must be valid Box Office Mojo or IMDB Pro URL)
     *
     * @return static Returns self for method chaining
     *
     * @example Parse custom Box Office Mojo page:
     * ```php
     * $data = $parser
     *     ->bySource('https://www.boxofficemojo.com/chart/top_lifetime_gross/')
     *     ->run();
     * ```
     *
     * @example Parse specific IMDB Pro title:
     * ```php
     * $film = $parser
     *     ->bySource('https://pro.imdb.com/title/tt123456/')
     *     ->parseTitle();
     * ```
     *
     * @since 0.1.0
     */
    public function bySource(string $source): static
    {
        $this->state = 3;
        $this->title = $source;
        $this->uri = $source;
        return $this;
    }

    /**
     * Configure parser to extract weekend box office data.
     *
     * Sets the parser to retrieve weekend box office rankings from Box Office Mojo
     * for the specified weekend. Results include detailed performance metrics
     * like theater counts, weekly changes, and per-theater averages.
     *
     * @param string $weekend Weekend identifier in format "YYYYWNN" where:
     *                       - YYYY is the year (e.g., "2024")
     *                       - W is literal "W"
     *                       - NN is the week number (01-52, e.g., "48")
     *
     * @return static Returns self for method chaining
     *
     * @example Parse specific weekend:
     * ```php
     * $data = $parser
     *     ->byWeekend('2024W48')
     *     ->run();
     *
     * foreach ($data->getReleases() as $release) {
     *     printf(
     *         "#%d: %s - $%s (%d theaters)\n",
     *         $release->getRank(),
     *         $release->getRelease(),
     *         number_format($release->getGross()),
     *         $release->getTheaters()
     *     );
     * }
     * ```
     *
     * @since 0.1.0
     */
    public function byWeekend(string $weekend): static
    {
        $this->state = 1;
        $this->uri = sprintf('https://www.boxofficemojo.com/weekend/%s/', $weekend);
        return $this;
    }

    /**
     * Create cache directory if it doesn't exist.
     *
     * Ensures the cache directory exists with proper permissions (755).
     * This method is called automatically before any caching operations.
     *
     * @throws Exception If directory cannot be created or lacks write permissions
     *
     * @internal This method is called automatically by parsing methods
     * @since 0.1.0
     */
    private function createCacheFolder(): void
    {
        if (!is_dir($this->cacheFolder) && !mkdir($this->cacheFolder, 0755, true)) {
            throw new Exception('Could not create cache folder');
        }
    }

    /**
     * Set the cache directory path for storing downloaded HTML files.
     *
     * The cache directory will be created automatically if it doesn't exist.
     * Cached files help avoid redundant HTTP requests and improve performance
     * for repeated parsing operations.
     *
     * @param string $path Absolute path to cache directory (must be writable)
     *
     * @return static Returns self for method chaining
     *
     * @throws Exception If path is not writable when cache operations are performed
     *
     * @example Set cache directory:
     * ```php
     * $parser->setCacheFolder('/var/cache/imdb/');
     * $parser->setCacheFolder('./cache/'); // Relative paths work too
     * ```
     *
     * @since 0.1.0
     */
    public function setCacheFolder(string $path): static
    {
        $this->cacheFolder = $path;
        return $this;
    }

    /**
     * Create and configure a DOMDocument from HTML content.
     *
     * Downloads HTML content, creates a DOMDocument instance, and loads
     * the HTML while suppressing libxml errors. This method handles both
     * regular Box Office Mojo pages and IMDB Pro pages.
     *
     * @param string $uri URL to download and parse
     * @param bool $loadPro Whether this is an IMDB Pro page (affects caching)
     *
     * @return DOMDocument Configured DOM document ready for parsing
     *
     * @throws Exception If HTML content cannot be downloaded
     * @throws Exception If DOM document creation fails
     *
     * @internal This method is used by all parsing operations
     * @since 0.1.0
     */
    private function createDomDocument(string $uri, bool $loadPro = false): DOMDocument
    {
        $dom = new DOMDocument;
        libxml_use_internal_errors(true);
        $dom->loadHTML($this->getHTML($uri, $loadPro));
        libxml_use_internal_errors(false);

        return $dom;
    }
}