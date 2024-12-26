<?php
namespace resoul\imdb\model;

interface IMDBInterface
{
    public function getReleases(): array;
    public function getTitle(): string;
}