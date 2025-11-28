<?php
namespace Resoul\Imdb\Mapper;

use Resoul\Imdb\Domain\Actor;
use Resoul\Imdb\DTO\PersonDTO;
use Resoul\Imdb\DTO\TitleDataDTO;

class TitleDataMapper
{
    public function mapPersonsToActors(TitleDataDTO $dto): array
    {
        return array_map(
            fn(PersonDTO $person) => new Actor(
                original: $person->name,
                uri: $person->uri,
                role: $person->role,
                roleName: $person->characterName,
                poster: $person->posterUrl
            ),
            $dto->persons
        );
    }

    public function toFilmData(TitleDataDTO $dto): array
    {
        return [
            'original' => $dto->title,
            'poster' => $dto->poster,
            'description' => $dto->description,
            'certificate' => $dto->certificate,
            'duration' => (string) $dto->runningTime,
            'genres' => $dto->genres,
            'type' => $dto->type,
            'seasons' => $dto->seasons,
            'releaseSummary' => $dto->releaseSummary,
            'cast' => $this->mapPersonsToActors($dto),
        ];
    }
}