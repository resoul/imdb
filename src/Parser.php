<?php
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
    private string $cacheFolder;
    private string $uri;
    private Client $client;
    private int $state;
    private string $title;

    public function __construct()
    {
        $this->client = new Client([
            RequestOptions::VERIFY => false,
            RequestOptions::TIMEOUT => 30,
        ]);
    }

    /**
     * @throws Exception
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
            cast: $title['person'] ?? null,
        );
    }

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

    private function getHTML($link, $pro = false): false|string
    {
        $file = str_replace(['/', 'weekend', 'release', 'title'], '', parse_url($link)['path']);

        if ($pro) {
            $file = "pro.$file";
        }

        if (!file_exists(sprintf($this->cacheFolder . "/%s.html", $file))) {
            echo ">> $file" . PHP_EOL;
            file_put_contents(sprintf($this->cacheFolder . "/%s.html", $file), $this->_request($link));
        }

        return file_get_contents(sprintf($this->cacheFolder . "/%s.html", $file));
    }

    private function _request(string $uri): string
    {
        $response = $this->client->request('GET', $uri);
        return $response->getBody()->getContents();
    }

    public function byYear(string $year): static
    {
        $this->state = 2;
        $this->title = $year;
        $this->uri = sprintf('https://www.boxofficemojo.com/year/%s/?grossesOption=totalGrosses', $year);
        return $this;
    }

    public function bySource(string $source): static
    {
        $this->state = 3;
        $this->title = $source;
        $this->uri = $source;
        return $this;
    }

    public function byWeekend(string $weekend): static
    {
        $this->state = 1;
        $this->uri = sprintf('https://www.boxofficemojo.com/weekend/%s/', $weekend);
        return $this;
    }

    private function createCacheFolder(): void
    {
        if (!is_dir($this->cacheFolder) && !mkdir($this->cacheFolder, 0755, true)) {
            throw new Exception('Could not create cache folder');
        }
    }

    public function setCacheFolder($path): static
    {
        $this->cacheFolder = $path;
        return $this;
    }

    private function createDomDocument(string $uri, bool $loadPro = false): DOMDocument
    {
        $dom = new DOMDocument;
        libxml_use_internal_errors(true);
        $dom->loadHTML($this->getHTML($uri, $loadPro));
        libxml_use_internal_errors(false);

        return $dom;
    }
}