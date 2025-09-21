<?php
namespace resoul\imdb\model;

use JsonSerializable;

/**
 * Yearly Report Class
 */
class YearlyReport implements JsonSerializable
{
    private string $year;
    private array $releases;
    private array $genreAnalysis;
    private array $budgetAnalysis;
    private array $distributorAnalysis;

    public function __construct(string $year, array $releases, array $genreAnalysis, array $budgetAnalysis, array $distributorAnalysis)
    {
        $this->year = $year;
        $this->releases = $releases;
        $this->genreAnalysis = $genreAnalysis;
        $this->budgetAnalysis = $budgetAnalysis;
        $this->distributorAnalysis = $distributorAnalysis;
    }

    public function getYear(): string
    {
        return $this->year;
    }

    public function getTopFilm(): Release
    {
        return $this->releases[0];
    }

    public function getTotalYearGross(): int
    {
        return array_sum(array_map(fn($r) => $r->getGross(), $this->releases));
    }

    public function getAverageBudget(): float
    {
        $budgets = [];
        foreach ($this->releases as $release) {
            $budget = $release->getFilm()->getBudget();
            if ($budget !== null) {
                $budgets[] = $budget;
            }
        }

        return !empty($budgets) ? array_sum($budgets) / count($budgets) : 0;
    }

    public function getGenreAnalysis(): array
    {
        return $this->genreAnalysis;
    }

    public function getBudgetAnalysis(): array
    {
        return $this->budgetAnalysis;
    }

    public function getDistributorAnalysis(): array
    {
        return $this->distributorAnalysis;
    }

    public function getReleases(): array
    {
        return $this->releases;
    }

    public function toJson(): string
    {
        return json_encode($this, JSON_PRETTY_PRINT);
    }

    public function jsonSerialize(): array
    {
        return [
            'year' => $this->year,
            'summary' => [
                'total_gross' => $this->getTotalYearGross(),
                'film_count' => count($this->releases),
                'top_film' => $this->getTopFilm()->getRelease(),
                'average_budget' => round($this->getAverageBudget(), 2)
            ],
            'analysis' => [
                'genres' => $this->genreAnalysis,
                'budget_ranges' => $this->budgetAnalysis,
                'distributors' => $this->distributorAnalysis
            ]
        ];
    }
}