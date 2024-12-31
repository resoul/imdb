<?php
namespace resoul\imdb\model\enum;

enum RoleEnum: int
{
    case ACTOR = 1;
    case DIRECTOR = 2;
    case WRITER = 3;
    case PRODUCER = 4;
    case COMPOSER = 5;
    case CINEMATOGRAPHER = 6;
    case SHOWRUNNER = 7;

    public static function getLabels(): array
    {
        return [
            RoleEnum::ACTOR->value => 'Actor',
            RoleEnum::DIRECTOR->value => 'Director',
            RoleEnum::CINEMATOGRAPHER->value => 'Cinematographer',
            RoleEnum::COMPOSER->value => 'Composer',
            RoleEnum::WRITER->value => 'Writer',
            RoleEnum::PRODUCER->value => 'Producer',
            RoleEnum::SHOWRUNNER->value => 'Showrunner',
        ];
    }
}