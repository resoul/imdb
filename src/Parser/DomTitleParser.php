<?php
namespace Resoul\Imdb\Parser;

use DOMDocument;
use DOMElement;
use Resoul\Imdb\DTO\PersonDTO;
use Resoul\Imdb\DTO\TitleDataDTO;
use Resoul\Imdb\Enum\FilmTypeEnum;
use Resoul\Imdb\Enum\GenreEnum;
use Resoul\Imdb\Enum\RoleEnum;

/**
 * Parses IMDB Pro title pages into DTOs
 */
class DomTitleParser
{
    /**
     * Parse DOM document into TitleDataDto
     *
     * @param DOMDocument $dom
     * @return TitleDataDto
     */
    public function parse(DOMDocument $dom): TitleDataDTO
    {
        return new TitleDataDTO(
            title: $this->parseTitle($dom),
            poster: $this->parsePoster($dom),
            description: $this->parseDescription($dom),
            certificate: $this->parseCertificate($dom),
            runningTime: $this->parseRunningTime($dom),
            genres: $this->parseGenres($dom),
            type: $this->parseType($dom),
            persons: $this->parsePersons($dom),
            seasons: $this->parseSeasons($dom),
            releaseSummary: $this->parseReleaseSummary($dom),
        );
    }

    private function parseTitle(DOMDocument $dom): string
    {
        $titleHeading = $dom->getElementById('title_heading');
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

    private function parsePoster(DOMDocument $dom): string
    {
        $primaryImage = $dom->getElementById('primary_image');
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

    private function parseDescription(DOMDocument $dom): string
    {
        $summary = $dom->getElementById('title_summary');
        if (!$summary) {
            return '';
        }

        // Remove nested divs
        foreach ($summary->getElementsByTagName('div') as $div) {
            $div->remove();
        }

        return trim($summary->textContent);
    }

    private function parseCertificate(DOMDocument $dom): string
    {
        return trim($dom->getElementById('certificate')?->textContent ?? '');
    }

    private function parseRunningTime(DOMDocument $dom): int
    {
        $runningTimeText = $dom->getElementById('running_time')?->textContent ?? '0';
        return (int) str_replace('min', '', trim($runningTimeText));
    }

    private function parseGenres(DOMDocument $dom): array
    {
        $genresElement = $dom->getElementById('genres');
        if (!$genresElement) {
            return [];
        }

        $labels = array_flip(GenreEnum::getLabels());
        $genres = [];

        foreach (explode(',', trim($genresElement->textContent)) as $genre) {
            $genreTrimmed = trim($genre);
            if (isset($labels[$genreTrimmed])) {
                $genres[] = GenreEnum::tryFrom($labels[$genreTrimmed]);
            }
        }

        return $genres;
    }

    private function parseType(DOMDocument $dom): FilmTypeEnum
    {
        $titleHeading = $dom->getElementById('title_heading');
        if (!$titleHeading || $titleHeading->childElementCount !== 2) {
            return FilmTypeEnum::MOVIE;
        }

        $type = trim($dom->getElementById('title_type')?->textContent ?? '');

        return match ($type) {
            'TV Series', 'TV Mini-series' => FilmTypeEnum::SERIAL,
            default => FilmTypeEnum::MOVIE,
        };
    }

    private function parseSeasons(DOMDocument $dom): int
    {
        $season = $dom->getElementById('season');
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

    private function parseReleaseSummary(DOMDocument $dom): ?string
    {
        $statusSummary = $dom->getElementById('status_summary');
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

    private function parsePersons(DOMDocument $dom): array
    {
        $persons = [];

        // Parse crew
        $persons = array_merge($persons, $this->parseCrewLine(RoleEnum::DIRECTOR, $dom->getElementById('director_summary')));
        $persons = array_merge($persons, $this->parseCrewLine(RoleEnum::WRITER, $dom->getElementById('writer_summary')));
        $persons = array_merge($persons, $this->parseCrewLine(RoleEnum::PRODUCER, $dom->getElementById('producer_summary')));
        $persons = array_merge($persons, $this->parseCrewLine(RoleEnum::COMPOSER, $dom->getElementById('composer_summary')));
        $persons = array_merge($persons, $this->parseCrewLine(RoleEnum::CINEMATOGRAPHER, $dom->getElementById('cinematographer_summary')));

        // Parse cast
        $persons = array_merge($persons, $this->parseCast($dom));

        return $persons;
    }

    private function parseCrewLine(RoleEnum $role, ?DOMElement $element): array
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

    private function parseCast(DOMDocument $dom): array
    {
        $castTable = $dom->getElementById('title_cast_sortable_table');
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
            role: RoleEnum::ACTOR,
            characterName: $character ?: null,
            posterUrl: $posterUrl
        );
    }
}