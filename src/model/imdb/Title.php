<?php
namespace resoul\imdb\model\imdb;

use DOMDocument;
use DOMElement;
use resoul\imdb\model\Actor;
use resoul\imdb\model\enum\FilmTypeEnum;
use resoul\imdb\model\enum\GenreEnum;
use resoul\imdb\model\enum\RoleEnum;

class Title
{
    private DOMDocument $content;
    private array $data;

    public function loadContent(DOMDocument $content): static
    {
        $this->content = $content;
        $this->parseContent();

        return $this;
    }

    private function parseContent()
    {
        $poster = $this->content->getElementById('primary_image')
            ->getElementsByTagName('img')
            ->item(0)
            ->getAttribute('src');

        $posterSrc = explode('.', str_replace('.jpg', '', basename($poster)))[0];
        $this->data['poster'] = str_replace(basename($poster), $posterSrc, $poster);
        $this->data['genres'] = [];
        if ($this->content->getElementById('title_heading')->childElementCount == 2) {
            $type = trim($this->content->getElementById('title_type')?->textContent ?? '');
            switch ($type) {
                case 'TV Series':
                case 'TV Mini-series':
                    $this->data['type'] = FilmTypeEnum::SERIAL;
                    break;
                default:
                    $this->data['type'] = FilmTypeEnum::MOVIE;
                    break;
            }

            $this->data['certificate'] = trim($this->content->getElementById('certificate')?->textContent ?? '');
            $this->data['running_time'] = (int) trim(str_replace('min', '', trim($this->content->getElementById('running_time')?->textContent ?? 0)));
            $labels = array_flip(GenreEnum::getLabels());
            $genres = $this->content->getElementById('genres');
            if ($genres) {
                foreach (explode(',', trim($genres->textContent)) as $genre) {
                    if (isset($labels[trim($genre)])) {
                        $this->data['genres'][] = GenreEnum::tryFrom($labels[trim($genre)]);
                    }
                }
            }

            $title = $this->content->getElementById('title_heading');
            foreach ($title->childNodes->item(0)->childNodes->item(0)->childNodes as $node) {
                if (!$node instanceof \DOMElement) {
                    $this->data['title'] = trim($node->textContent);
                }
            }
        }
        $description = $this->content->getElementById('title_summary');
        $this->data['description'] = '';
        if ($description) {
            foreach ($description->getElementsByTagName('div') as $div) {
                $div->remove();
            }
            $this->data['description'] = trim($description->textContent);
        }

        $this->_readCrewLine(RoleEnum::DIRECTOR, $this->content->getElementById('director_summary'));
        $this->_readCrewLine(RoleEnum::WRITER, $this->content->getElementById('writer_summary'));
        $this->_readCrewLine(RoleEnum::PRODUCER, $this->content->getElementById('producer_summary'));
        $this->_readCrewLine(RoleEnum::COMPOSER, $this->content->getElementById('composer_summary'));
        $this->_readCrewLine(RoleEnum::CINEMATOGRAPHER, $this->content->getElementById('cinematographer_summary'));

        $season = $this->content->getElementById('season');
        $this->data['seasons'] = 0;
        if ($season) {
            foreach ($season->getElementsByTagName('span') as $span) {
                if ($span->getAttribute('class') === 'a-declarative') {
                    foreach ($span->getElementsByTagName('a') as $a) {
                        if ((int) $a->textContent && $this->data['seasons'] < (int) $a->textContent) {
                            $this->data['seasons'] = (int) $a->textContent;
                        }
                    }
                }
            }
        }

        $c = 0;
        $title_cast_sortable_table = $this->content->getElementById('title_cast_sortable_table');
        if ($title_cast_sortable_table) {
            foreach ($title_cast_sortable_table->getElementsByTagName('tr') as $tr) {
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
                            $this->data['cast']["https://pro.imdb.com$path"]['path'] = str_replace(basename(trim($img->getAttribute('data-src'))), $src, trim($img->getAttribute('data-src')));
                        }
                    }
                    foreach ($tr->getElementsByTagName('td')->item(0)->getElementsByTagName('span') as $span) {
                        if ($span->getAttribute('class') == 'see_more_text_collapsed') {
                            $this->data['cast']["https://pro.imdb.com$path"]['name'] = $name;
                            $this->data['cast']["https://pro.imdb.com$path"]['role'] = trim($span->textContent);
                        }
                    }
                    $c++;
                    if ($c == 10)
                        break;
                }
            }
        }
        if (isset($this->data['crew'])) {
            foreach ($this->data['crew'] as $role => $item) {
                foreach ($item as $uri => $name) {
                    $this->data['person'][] = new Actor(
                        original: $name,
                        uri: $uri,
                        role: RoleEnum::tryFrom($role)
                    );
                }
            }
            unset($this->data['crew']);
        }
        if (isset($this->data['cast'])) {
            foreach ($this->data['cast'] as $uri => $actor) {
                $this->data['person'][] = new Actor(
                    original: $actor['name'],
                    uri: $uri,
                    role: RoleEnum::ACTOR,
                    roleName: $actor['role'],
                    poster: $actor['path'] ?? null
                );
            }
            unset($this->data['cast']);
        }

        $status_summary = $this->content->getElementById('status_summary');
        if ($status_summary) {
            foreach ($status_summary->getElementsByTagName('a') as $item) {
                if (parse_url($item->getAttribute('href'), PHP_URL_QUERY) == 'ref_=tt_pub_intl_release_summary') {
                    $this->data['release_summary'] = implode(' ', explode("\n", trim($item->textContent)));
                }
            }
        }
    }

    public function getContent(): array
    {
        return $this->data;
    }

    private function _readCrewLine(RoleEnum $crew, ?DOMElement $element = null): void
    {
        if ($element === null) return;

        foreach ($element->getElementsByTagName('a') as $a) {
            foreach ($a->getElementsByTagName('span') as $span) {
                $url = sprintf(
                    "https://pro.imdb.com%s",
                    parse_url($a->getAttribute('href'), PHP_URL_PATH)
                );
                $this->data['crew'][$crew->value][$url] = trim($span->textContent);
            }
        }
    }
}