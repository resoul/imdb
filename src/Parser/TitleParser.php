<?php

declare(strict_types=1);

namespace Resoul\Imdb\Parser;

use DOMElement;
use Resoul\Imdb\DTO\PersonDTO;
use Resoul\Imdb\DTO\TitleDataDTO;

final class TitleParser
{
    use ParserTrait;

    public function parse(): TitleDataDTO
    {
        return new TitleDataDTO(
            title: $this->parseTitle(),
            poster: $this->parsePoster(),
            description: $this->parseDescription(),
            certificate: $this->parseCertificate(),
            runningTime: $this->parseRunningTime(),
            genres: $this->parseGenres(),
            type: $this->parseType(),
            persons: $this->parsePersons(),
            seasons: $this->parseSeasons(),
            releaseSummary: $this->parseReleaseSummary(),
        );
    }

    private function parsePersons(): array
    {
        $persons = [];

        // Parse crew
        $persons = array_merge($persons, $this->parseCrewLine(2, $this->dom->getElementById('director_summary')));
        $persons = array_merge($persons, $this->parseCrewLine(3, $this->dom->getElementById('writer_summary')));
        $persons = array_merge($persons, $this->parseCrewLine(4, $this->dom->getElementById('producer_summary')));
        $persons = array_merge($persons, $this->parseCrewLine(5, $this->dom->getElementById('composer_summary')));
        $persons = array_merge($persons, $this->parseCrewLine(6, $this->dom->getElementById('cinematographer_summary')));

        return array_merge($persons, $this->parseCast());
    }

    private function parseCrewLine(int $role, ?DOMElement $element): array
    {
        if ($element === null) {
            return [];
        }

        $persons = [];

        foreach ($element->getElementsByTagName('a') as $a) {
            foreach ($a->getElementsByTagName('span') as $span) {
                $url = sprintf(
                    "https://pro.imdb.com%s",
                    parse_url($a->getAttribute('href'), PHP_URL_PATH)
                );

                $persons[] = new PersonDTO(
                    name: trim($span->textContent),
                    uri: $url,
                    role: $role
                );
            }
        }

        return $persons;
    }

    private function parseCastRow(DOMElement $tr): ?PersonDTO
    {
        $td = $tr->getElementsByTagName('td')->item(0);
        if (!$td) {
            return null;
        }

        $path = '';
        $name = '';
        $character = '';
        $posterUrl = null;

        // Parse name and URI
        foreach ($td->getElementsByTagName('a') as $a) {
            if ($a->hasAttribute('data-tab')) {
                $url = parse_url($a->getAttribute('href'));
                $path = $url['path'];
                $name = trim($a->textContent);
            }
        }

        // Parse poster
        foreach ($td->getElementsByTagName('img') as $img) {
            $dataSrc = trim($img->getAttribute('data-src'));
            if (!empty($dataSrc)) {
                $src = explode('.', str_replace('.jpg', '', basename($dataSrc)))[0];
                $posterUrl = str_replace(basename($dataSrc), $src, $dataSrc);
            }
        }

        // Parse character name
        foreach ($td->getElementsByTagName('span') as $span) {
            if ($span->getAttribute('class') === 'see_more_text_collapsed') {
                $character = trim($span->textContent);
            }
        }

        if (empty($name) || empty($path)) {
            return null;
        }

        return new PersonDTO(
            name: $name,
            uri: "https://pro.imdb.com$path",
            role: 1,
            characterName: $character ?: null,
            posterUrl: $posterUrl
        );
    }

    private function parseTitle(): string
    {
        $titleHeading = $this->dom->getElementById('title_heading');
        if (!$titleHeading) {
            return '';
        }

        foreach ($titleHeading->childNodes->item(0)?->childNodes->item(0)?->childNodes ?? [] as $node) {
            if (!$node instanceof DOMElement) {
                return trim($node->textContent);
            }
        }

        return '';
    }

    private function parsePoster(): string
    {
        $primaryImage = $this->dom->getElementById('primary_image');
        if (!$primaryImage) {
            return '';
        }

        $img = $primaryImage->getElementsByTagName('img')->item(0);
        if (!$img) {
            return '';
        }

        $poster = $img->getAttribute('src');
        $posterSrc = explode('.', str_replace('.jpg', '', basename($poster)))[0];

        return str_replace(basename($poster), $posterSrc, $poster);
    }

    private function parseDescription(): string
    {
        $summary = $this->dom->getElementById('title_summary');
        if (!$summary) {
            return '';
        }

        // Remove nested divs
        foreach ($summary->getElementsByTagName('div') as $div) {
            $div->remove();
        }

        return trim($summary->textContent);
    }

    private function parseCertificate(): string
    {
        return trim($this->dom->getElementById('certificate')?->textContent ?? '');
    }

    private function parseRunningTime(): int
    {
        $runningTimeText = $this->dom->getElementById('running_time')?->textContent ?? '0';
        return (int) str_replace('min', '', trim($runningTimeText));
    }

    private function parseGenres(): array
    {
        $genresElement = $this->dom->getElementById('genres');
        if (!$genresElement) {
            return [];
        }

        $genres = [];

        foreach (explode(',', trim($genresElement->textContent)) as $genre) {
            $genres[] = trim($genre);
        }

        return $genres;
    }

    private function parseType(): int
    {
        $titleHeading = $this->dom->getElementById('title_heading');
        if (!$titleHeading || $titleHeading->childElementCount !== 2) {
            return 1;
        }

        $type = trim($this->dom->getElementById('title_type')?->textContent ?? '');

        return match ($type) {
            'TV Series', 'TV Mini-series' => 2,
            default => 1,
        };
    }

    private function parseSeasons(): int
    {
        $season = $this->dom->getElementById('season');
        if (!$season) {
            return 0;
        }

        $maxSeasons = 0;

        foreach ($season->getElementsByTagName('span') as $span) {
            if ($span->getAttribute('class') === 'a-declarative') {
                foreach ($span->getElementsByTagName('a') as $a) {
                    $seasonNumber = (int) $a->textContent;
                    if ($seasonNumber > $maxSeasons) {
                        $maxSeasons = $seasonNumber;
                    }
                }
            }
        }

        return $maxSeasons;
    }

    private function parseReleaseSummary(): ?string
    {
        $statusSummary = $this->dom->getElementById('status_summary');
        if (!$statusSummary) {
            return null;
        }

        foreach ($statusSummary->getElementsByTagName('a') as $item) {
            if (parse_url($item->getAttribute('href'), PHP_URL_QUERY) === 'ref_=tt_pub_intl_release_summary') {
                return implode(' ', explode("\n", trim($item->textContent)));
            }
        }

        return null;
    }

    private function parseCast(): array
    {
        $castTable = $this->dom->getElementById('title_cast_sortable_table');
        if (!$castTable) {
            return [];
        }

        $cast = [];
        $count = 0;

        foreach ($castTable->getElementsByTagName('tr') as $tr) {
            if (!$tr->hasAttribute('data-cast-listing-index')) {
                continue;
            }

            $castData = $this->parseCastRow($tr);
            if ($castData) {
                $cast[] = $castData;
                $count++;

                if ($count >= 10) {
                    break;
                }
            }
        }

        return $cast;
    }
}