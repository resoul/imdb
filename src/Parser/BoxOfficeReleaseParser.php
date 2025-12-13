<?php

declare(strict_types=1);

namespace Resoul\Imdb\Parser;

use Resoul\Imdb\DTO\ReleaseDataDTO;
use Resoul\Imdb\DTO\WeekendTableDTO;

final class BoxOfficeReleaseParser
{
    use ParserTrait;

    private int $numberOfMovies;

    public function setNumberOfMovies(int $numberOfMovies): void
    {
        $this->numberOfMovies = $numberOfMovies;
    }

    public function parseReleaseTable(): WeekendTableDTO
    {
        $tables = $this->dom->getElementById('table')
            ->getElementsByTagName('table');

        $title = trim($this->dom->getElementsByTagName('h1')
            ->item(0)->nextSibling->childNodes->item(0)->textContent);

        $data = $result = [];

        foreach ($tables as $item) {
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

        foreach ($data as $position => $value) {
            if ($position > $this->numberOfMovies) break;
            $result[] = new ReleaseDataDTO(
                rank: $position,
                title: (string) $value['movie'],
                uri: (string) $value['uri'],
                gross: (int) str_replace(['$', ','], '', $value['gross']) > 0 ? (int) str_replace(['$', ','], '', $value['gross']) : 0,
                total: (int) str_replace(['$', ','], '', $value['total']) > 0 ? (int) str_replace(['$', ','], '', $value['total']) : 0,
                theaters: (int) str_replace(['$', ','], '', $value['theaters']) > 0 ? (int) str_replace(['$', ','], '', $value['theaters']) : 0,
                weeks: max((int)$value['weeks'], 0),
                lastWeek: max((int)$value['last_week'], 0),
            );
        }

        return new WeekendTableDTO(
            title: $title,
            releases: $result
        );
    }
}