<?php

declare(strict_types=1);

namespace Resoul\Imdb\Parser;

use Resoul\Imdb\DTO\GrossDataDTO;
use DOMElement;
use Resoul\Imdb\DTO\ReleaseTitleDataDTO;

final class TitleReleaseParser
{
    use ParserTrait;

    private function parseSummaryDetails(): array
    {
        $data = [];
        $summaryElement = $this->dom->getElementById('mojo-summary-details-discloser');

        if (!$summaryElement || !$summaryElement->nextElementSibling) {
            return $data;
        }

        foreach ($summaryElement->nextElementSibling->childNodes as $element) {
            if ($element->childElementCount !== 2) {
                continue;
            }

            $label = $element->getElementsByTagName('span')->item(0)?->textContent;
            $value = $element->getElementsByTagName('span')->item(1);

            if (!$label || !$value) {
                continue;
            }

            match (trim($label)) {
                'Distributor' => $data['distributor'] = $this->parseDistributor($value),
                'Opening' => $data = array_merge($data, $this->parseOpening($value)),
                'Budget' => $data['budget'] = $this->parseBudget($value),
                'Widest Release' => $data['wide_release'] = $this->parseWideRelease($value),
                default => $this->parseReleaseDate($label, $value, $data),
            };
        }

        return $data;
    }

    private function parseDistributor(DOMElement $element): string
    {
        // Remove links
        foreach ($element->getElementsByTagName('a') as $a) {
            $a->remove();
        }

        return trim($element->textContent);
    }

    private function parseOpening(DOMElement $element): array
    {
        $data = [];

        foreach ($element->getElementsByTagName('span') as $span) {
            if ($span->getAttribute('class') === 'money') {
                $data['opening'] = (int) str_replace([',', '$'], '', trim($span->textContent));
                $span->remove();
            }
        }

        // Parse theater count
        $remainingText = trim($element->textContent);
        foreach (explode(' ', $remainingText) as $item) {
            $item = str_replace(',', '', $item);
            if (!empty($item) && (int) $item > 0) {
                $data['opening_theaters'] = (int) $item;
                break;
            }
        }

        return $data;
    }

    private function parseBudget(DOMElement $element): ?int
    {
        foreach ($element->getElementsByTagName('span') as $span) {
            if ($span->getAttribute('class') === 'money') {
                return (int) str_replace([',', '$'], '', trim($span->textContent));
            }
        }

        return null;
    }

    private function parseWideRelease(DOMElement $element): int
    {
        return (int) str_replace([',', ' theaters'], '', trim($element->textContent));
    }

    private function parseReleaseDate(string $label, DOMElement $element, array &$data): void
    {
        if (trim(explode('(', $label)[0]) !== 'Release Date') {
            return;
        }

        $dateText = $element->textContent;
        $parts = explode('(', $dateText);

        if (count($parts) === 2) {
            $data['release_date'] = date('Y-m-d', strtotime(trim($parts[0])));
        } else {
            $dateParts = explode('-', $dateText);
            $data['release_date'] = date('Y-m-d', strtotime(trim($dateParts[0])));
        }
    }

    private function parseGrossData(): ?GrossDataDTO
    {
        $grossValues = [];

        foreach ($this->dom->getElementsByTagName('div') as $div) {
            if ($div->getAttribute('class') !== 'a-section a-spacing-none mojo-performance-summary-table') {
                continue;
            }

            foreach ($div->getElementsByTagName('div') as $k => $element) {
                foreach ($element->getElementsByTagName('span') as $item) {
                    if ($item->getAttribute('class') === 'money') {
                        $grossValues[$k] = (int) str_replace([',', '$'], '', trim($item->textContent));
                    }
                }
            }
        }

        if (empty($grossValues)) {
            return null;
        }

        return new GrossDataDTO(
            domestic: $grossValues[0] ?? null,
            international: $grossValues[1] ?? null,
            worldwide: $grossValues[2] ?? null
        );
    }

    private function parseImdbUri(): string
    {
        $refiner = $this->dom->getElementById('title-summary-refiner');
        if (!$refiner) {
            return '';
        }

        $link = $refiner->getElementsByTagName('a')->item(0);
        if (!$link) {
            return '';
        }

        $path = $link->getAttribute('href');
        return 'https://pro.imdb.com' . parse_url($path)['path'];
    }

    public function parseTitle(): ReleaseTitleDataDTO
    {
        foreach ($this->dom->getElementsByTagName('br') as $br) {
            $br->remove();
        }
        $summaryData = $this->parseSummaryDetails();
        $gross = $this->parseGrossData();
        $imdbUri = $this->parseImdbUri();

        return new ReleaseTitleDataDTO(
            imdbProUri: $imdbUri,
            releaseDate: $summaryData['release_date'] ?? '',
            budget: $summaryData['budget'] ?? null,
            opening: $summaryData['opening'] ?? null,
            openingTheaters: $summaryData['opening_theaters'] ?? null,
            wideRelease: $summaryData['wide_release'] ?? null,
            gross: $gross,
            distributor: $summaryData['distributor'] ?? null,
        );
    }
}