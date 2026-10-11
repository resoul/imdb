<?php

declare(strict_types=1);

namespace Resoul\Imdb;

use Resoul\Imdb\Contracts\DomFactoryInterface;
use Resoul\Imdb\Contracts\HtmlFetcherInterface;
use DOMDocument;
use Resoul\Imdb\DTO\PersonDTO;
use Resoul\Imdb\DTO\ReleaseDataDTO;
use Resoul\Imdb\Entity\Actor;
use Resoul\Imdb\Entity\Enum\DistributorEnum;
use Resoul\Imdb\Entity\Enum\FilmTypeEnum;
use Resoul\Imdb\Entity\Enum\GenreEnum;
use Resoul\Imdb\Entity\Enum\RoleEnum;
use Resoul\Imdb\Entity\Gross;
use Resoul\Imdb\Entity\IMDB;
use Resoul\Imdb\Entity\Release;
use Resoul\Imdb\Entity\Film;
use Resoul\Imdb\Parser\BoxOfficeReleaseParser;
use Resoul\Imdb\Parser\TitleParser;
use Resoul\Imdb\Parser\TitleReleaseParser;

final class Mojo
{
    private string $uri;
    private BoxOfficeReleaseParser $releaseParser;
    private TitleReleaseParser $titleReleaseParser;
    private TitleParser $titleParser;

    public function __construct(
        private HtmlFetcherInterface $fetcher,
        private DomFactoryInterface $domFactory,
    ) {
        $this->releaseParser = new BoxOfficeReleaseParser();
        $this->titleReleaseParser = new TitleReleaseParser();
        $this->titleParser = new TitleParser();
    }

    public function run(): IMDB
    {
        $dom = $this->parse($this->uri);
        $this->releaseParser->load($dom);
        $this->releaseParser->setNumberOfMovies(10);
        $releaseTableData = $this->releaseParser->parseReleaseTable();

        $title = trim($dom->getElementsByTagName('h1')
            ->item(0)->nextSibling->childNodes->item(0)->textContent);

        $genres = array_flip(GenreEnum::getLabels());
        $distributors = array_flip(DistributorEnum::getLabels());

        $releases = array_map(
            static fn (ReleaseDataDTO $releaseDataDTO): Release => new Release(
                release: $releaseDataDTO->title,
                uri: $releaseDataDTO->uri,
                theaters: $releaseDataDTO->theaters,
                rank: $releaseDataDTO->rank,
                lastWeek: $releaseDataDTO->lastWeek,
                weeks: $releaseDataDTO->weeks,
                gross: $releaseDataDTO->gross,
                total: $releaseDataDTO->total,
            ),
            $releaseTableData->releases
        );

        /**@var $release Release */
        foreach ($releases as $release) {
            $this->titleReleaseParser->load(
                $this->parse($release->getUri())
            );
            $releaseTitle = $this->titleReleaseParser->parseTitle();

            $this->titleParser->load(
                $this->parse($releaseTitle->imdbProUri, true)
            );
            $title = $this->titleParser->parse();

            $release->setFilm(new Film(
                uid: $releaseTitle->imdbProUri,
                releaseUid: $release->getUri(),
                original: $title->title,
                poster: $title->poster,
                description: $title->description,
                releaseDate: $releaseTitle->releaseDate,
                certificate: $title->certificate,
                duration: $title->runningTime,
                genres: array_map(
                    static fn (string $genre): GenreEnum => GenreEnum::from($genres[$genre]),
                    $title->genres
                ),
                type: FilmTypeEnum::from($title->type),
                opening: $releaseTitle->opening,
                openingTheaters: $releaseTitle->openingTheaters,
                wideRelease: $releaseTitle->wideRelease,
                releaseSummary: $title->releaseSummary,
                budget: $releaseTitle->budget,
                gross: new Gross(
                    domestic: $releaseTitle->gross?->domestic,
                    international: $releaseTitle->gross?->international,
                    worldwide: $releaseTitle->gross?->worldwide,
                ),
                cast: array_map(
                    static fn (PersonDTO $person): Actor => new Actor(
                        original: $person->name,
                        uri: $person->uri,
                        role: RoleEnum::from($person->role),
                        roleName: $person->characterName,
                        poster: $person->posterUrl
                    ),
                    $title->persons
                ),
                distributor: DistributorEnum::from($distributors[$releaseTitle->distributor])
            ));
        }

        return new IMDB(
            title: $title,
            release: $releases
        );
    }

    public function byWeekend(string $weekend): self
    {
        $this->uri = sprintf('https://www.boxofficemojo.com/weekend/%s/', $weekend);
        return $this;
    }

    public function parse(string $url, bool $pro = false): DOMDocument
    {
        $html = $this->fetcher->fetch($url, $pro);
        return $this->domFactory->createFromHtml($html);
    }
}