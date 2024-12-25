<?php
namespace resoul\imdb\model;

class Gross
{
    private ?int $international;
    private ?int $worldwide;
    private ?int $domestic;

    public function __construct(int $domestic = null, int $international = null, int $worldwide = null)
    {
        $this->international = $international;
        $this->worldwide = $worldwide;
        $this->domestic = $domestic;
    }

    public function getWorldwide(): ?int
    {
        return $this->worldwide;
    }

    public function getDomestic(): ?int
    {
        return $this->domestic;
    }

    public function getInternational(): ?int
    {
        return $this->international;
    }
}