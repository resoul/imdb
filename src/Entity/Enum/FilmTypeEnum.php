<?php

declare(strict_types=1);

namespace Resoul\Imdb\Entity\Enum;

enum FilmTypeEnum: int
{
    case MOVIE = 1;
    case SERIAL = 2;

    public static function getLabels(): array
    {
        return [
            FilmTypeEnum::MOVIE->value => 'Movie',
            FilmTypeEnum::SERIAL->value => 'Serial',
        ];
    }
}