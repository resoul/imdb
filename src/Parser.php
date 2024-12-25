<?php
namespace resoul\imdb;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use resoul\imdb\model\enum\DistributorEnum;
use resoul\imdb\model\enum\GenreEnum;
use resoul\imdb\model\enum\RoleEnum;
use resoul\imdb\model\Weekend;
use resoul\imdb\model\Release;
use resoul\imdb\model\Film;
use resoul\imdb\model\Actor;
use resoul\imdb\model\Gross;

class Parser
{
    private string $cacheFolder;
    private string $uri;
    private Client $client;

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
    public function run()
    {
        $this->createCacheFolder();
        $weekend = $this->_parseTable($this->getHTML($this->uri));
        foreach ($weekend->getReleases() as $release) {
            $film = $this->_parseRelease($release);
            print_r($film);
        }
    }

    private function _parseTable($code): Weekend
    {
        $dom = new \DOMDocument;
        libxml_use_internal_errors(true);
        $dom->loadHTML($code);
        libxml_use_internal_errors(false);

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

        return new Weekend(
            title: $title,
            release: $result
        );
    }

    private function _parseRelease(Release $release): Film
    {
        $dom = new \DOMDocument;
        libxml_use_internal_errors(true);

        $dom->loadHTML($this->getHTML($release->getUri()));
        libxml_use_internal_errors(false);
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

        $dom = new \DOMDocument;
        libxml_use_internal_errors(true);
        $dom->loadHTML($this->getHTML('https://www.boxofficemojo.com' . parse_url($path)['path']));
        libxml_use_internal_errors(false);

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


        $dom = new \DOMDocument;
        libxml_use_internal_errors(true);
        $dom->loadHTML($this->getHTML($data['uid'], true));
        libxml_use_internal_errors(false);

        $poster = $dom->getElementById('primary_image')
            ->getElementsByTagName('img')
            ->item(0)
            ->getAttribute('src');

        $posterSrc = explode('.', str_replace('.jpg', '', basename($poster)))[0];
        $data['poster'] = str_replace(basename($poster), $posterSrc, $poster);

        $data['genres'] = [];
        if ($dom->getElementById('title_heading')->childElementCount == 2) {
            $data['certificate'] = trim($dom->getElementById('certificate')?->textContent ?? '');
            $data['running_time'] = (int) trim(str_replace('min', '', trim($dom->getElementById('running_time')->textContent)));
            $labels = array_flip(GenreEnum::getLabels());
            foreach (explode(',', trim($dom->getElementById('genres')->textContent)) as $genre) {
                if (isset($labels[trim($genre)])) {
                    $data['genres'][] = GenreEnum::tryFrom($labels[trim($genre)]);
                }
            }

            $title = $dom->getElementById('title_heading');
            foreach ($title->childNodes->item(0)->childNodes->item(0)->childNodes as $node) {
                if (!$node instanceof \DOMElement) {
                    $data['title'] = trim($node->textContent);
                }
            }
        }
        $description = $dom->getElementById('title_summary');
        foreach ($description->getElementsByTagName('div') as $div) {
            $div->remove();
        }
        $data['description'] = trim($description->textContent);

        $this->_readCrewLine($data, RoleEnum::DIRECTOR, $dom->getElementById('director_summary'));
        $this->_readCrewLine($data, RoleEnum::WRITER, $dom->getElementById('writer_summary'));
        $this->_readCrewLine($data, RoleEnum::PRODUCER, $dom->getElementById('producer_summary'));
        $this->_readCrewLine($data, RoleEnum::COMPOSER, $dom->getElementById('composer_summary'));
        $this->_readCrewLine($data, RoleEnum::CINEMATOGRAPHER, $dom->getElementById('cinematographer_summary'));

        $c = 0;
        foreach ($dom->getElementById('title_cast_sortable_table')->getElementsByTagName('tr') as $tr) {
            if ($tr->hasAttribute('data-cast-listing-index')) {
                $path = $name = '';
                foreach ($tr->getElementsByTagName('td')->item(0)->getElementsByTagName('a') as $a) {
                    if ($a->hasAttribute('data-tab')) {
                        $url = parse_url($a->getAttribute('href'));
                        $path = $url['path'];
                        $name = trim($a->textContent);
                    }
                }
                foreach ($tr->getElementsByTagName('td')->item(0)->getElementsByTagName('img') as $img) {
                    if (!empty(trim($img->getAttribute('data-src')))) {
                        $src = explode('.', str_replace('.jpg', '', basename(trim($img->getAttribute('data-src')))))[0];
                        $data['cast']["https://pro.imdb.com$path"]['path'] = str_replace(basename(trim($img->getAttribute('data-src'))), $src, trim($img->getAttribute('data-src')));
                    }
                }
                foreach ($tr->getElementsByTagName('td')->item(0)->getElementsByTagName('span') as $span) {
                    if ($span->getAttribute('class') == 'see_more_text_collapsed') {
                        $data['cast']["https://pro.imdb.com$path"]['name'] = $name;
                        $data['cast']["https://pro.imdb.com$path"]['role'] = trim($span->textContent);
                    }
                }
                $c++;
                if ($c == 8)
                    break;
            }
        }
        if (isset($data['crew'])) {
            foreach ($data['crew'] as $role => $item) {
                foreach ($item as $uri => $name) {
                    $person[] = new Actor(
                        original: $name,
                        uri: $uri,
                        role: RoleEnum::tryFrom($role)
                    );
                }
            }
            unset($data['crew']);
        }
        if (isset($data['cast'])) {
            foreach ($data['cast'] as $uri => $actor) {
                $person[] = new Actor(
                    original: $actor['name'],
                    uri: $uri,
                    role: RoleEnum::ACTOR,
                    roleName: $actor['role'],
                    poster: $actor['path'] ?? null
                );
            }
            unset($data['cast']);
        }

        return new Film(
            uid: $data['uid'],
            releaseUid: $data['release_uid'],
            original: $data['title'],
            poster: $data['poster'],
            description: $data['description'],
            releaseDate: $data['release'],
            certificate: $data['certificate'],
            duration: $data['running_time'],
            genres: $data['genres'],
            opening: $data['opening'] ?? null,
            openingTheaters: $data['opening_theaters'] ?? null,
            wideRelease: $data['wide_release'] ?? null,
            budget: $data['budget'] ?? null,
            gross: $gross ?? null,
            cast: $person ?? null,
            distributor: $data['distributor'] ?? null
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

    private function _readCrewLine(&$data, RoleEnum $crew, ?\DOMElement $element = null): void
    {
        if ($element === null) return;

        foreach ($element->getElementsByTagName('a') as $a) {
            foreach ($a->getElementsByTagName('span') as $span) {
                $url = parse_url($a->getAttribute('href'));
                $data['crew'][$crew->value]['https://pro.imdb.com' . $url['path']] = trim($span->textContent);
            }
        }
    }

    private function _request(string $uri): string
    {
        $response = $this->client->request('GET', $uri);
        return $response->getBody()->getContents();
    }

    public function byWeekend(string $weekend): static
    {
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
}