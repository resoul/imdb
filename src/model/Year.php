<?php
namespace resoul\imdb\model;

class Year implements IMDBInterface
{
    private string $title;

    private array $release;

    public function __construct(string $title, array $release)
    {
        $this->title = $title;
        $this->release = $release;
    }

    /**
     *@return  array<Release>
     */
    public function getReleases(): array
    {
        return $this->release;
    }

    public function getTitle(): string
    {
        return $this->title;
    }
}