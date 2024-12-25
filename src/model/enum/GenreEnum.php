<?php
namespace resoul\imdb\model\enum;

enum GenreEnum: int
{
    case ACTION = 1;
    case DRAMA = 2;
    case FAMILY = 3;
    case ADVENTURE = 4;
    case SCI_FI = 5;
    case COMEDY = 6;
    case BIOGRAPHY = 7;
    case WESTERN = 8;
    case CRIME = 9;
    case THRILLER = 10;
    case ROMANCE = 11;
    case MYSTERY = 12;
    case FANTASY = 13;
    case ANIMATION = 14;
    case SPORT = 15;
    case HORROR = 16;

    public static function getLabels(): array
    {
        return [
            GenreEnum::ACTION->value => 'Action',
            GenreEnum::DRAMA->value => 'Drama',
            GenreEnum::FAMILY->value => 'Family',
            GenreEnum::ADVENTURE->value => 'Adventure',
            GenreEnum::SCI_FI->value => 'Sci-Fi',
            GenreEnum::COMEDY->value => 'Comedy',
            GenreEnum::BIOGRAPHY->value => 'Biography',
            GenreEnum::WESTERN->value => 'Western',
            GenreEnum::THRILLER->value => 'Thriller',
            GenreEnum::CRIME->value => 'Crime',
            GenreEnum::ROMANCE->value => 'Romance',
            GenreEnum::MYSTERY->value => 'Mystery',
            GenreEnum::FANTASY->value => 'Fantasy',
            GenreEnum::ANIMATION->value => 'Animation',
            GenreEnum::SPORT->value => 'Sport',
            GenreEnum::HORROR->value => 'Horror',
        ];
    }
}