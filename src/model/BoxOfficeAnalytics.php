<?php
namespace resoul\imdb\model;

use resoul\imdb\Parser;
use Exception;

/**
 * Box Office Analytics Class
 *
 * Comprehensive example showing how to build analytics functionality
 * on top of the IMDB parser with proper error handling and caching.
 */
class BoxOfficeAnalytics
{
    private Parser $parser;
    private string $cacheDir;

    public function __construct(string $cacheDir = './cache/')
    {
        $this->cacheDir = $cacheDir;
        $this->parser = new Parser();

        try {
            $this->parser->setCacheFolder($cacheDir);
        } catch (Exception $e) {
            throw new Exception("Failed to initialize cache: " . $e->getMessage());
        }
    }

    /**
     * Generate comprehensive weekend box office report
     */
    public function generateWeekendReport(string $weekend): WeekendReport
    {
        try {
            $data = $this->parser->byWeekend($weekend)->run();
            $releases = $data->getReleases();

            if (empty($releases)) {
                throw new Exception("No box office data found for weekend $weekend");
            }

            $metrics = $this->calculateWeekendMetrics($releases);
            $insights = $this->generateInsights($releases, $metrics);

            return new WeekendReport($weekend, $releases, $metrics, $insights);

        } catch (Exception $e) {
            error_log("Weekend report generation failed: " . $e->getMessage());
            throw new Exception("Unable to generate weekend report for $weekend: " . $e->getMessage());
        }
    }

    /**
     * Compare box office performance across multiple weekends
     */
    public function compareWeekends(array $weekends): ComparisonReport
    {
        $weekendData = [];
        $errors = [];

        foreach ($weekends as $weekend) {
            try {
                $report = $this->generateWeekendReport($weekend);
                $weekendData[$weekend] = [
                    'total_gross' => $report->getTotalGross(),
                    'total_theaters' => $report->getTotalTheaters(),
                    'average_per_theater' => $report->getAveragePerTheater(),
                    'top_film' => $report->getTopFilm(),
                    'new_releases' => count($report->getNewReleases())
                ];
            } catch (Exception $e) {
                $errors[$weekend] = $e->getMessage();
                error_log("Failed to process weekend $weekend: " . $e->getMessage());
            }
        }

        return new ComparisonReport($weekendData, $errors);
    }

    /**
     * Analyze yearly box office trends and performance
     */
    public function analyzeYear(string $year): YearlyReport
    {
        try {
            $data = $this->parser->byYear($year)->run();
            $releases = $data->getReleases();

            if (empty($releases)) {
                throw new Exception("No box office data found for year $year");
            }

            $genreAnalysis = $this->analyzeGenres($releases);
            $budgetAnalysis = $this->analyzeBudgets($releases);
            $distributorAnalysis = $this->analyzeDistributors($releases);

            return new YearlyReport($year, $releases, $genreAnalysis, $budgetAnalysis, $distributorAnalysis);

        } catch (Exception $e) {
            throw new Exception("Year analysis failed for $year: " . $e->getMessage());
        }
    }

    /**
     * Calculate weekend performance metrics
     */
    private function calculateWeekendMetrics(array $releases): array
    {
        $totalGross = 0;
        $totalTheaters = 0;
        $filmCount = 0;

        foreach ($releases as $release) {
            $totalGross += $release->getGross();
            $totalTheaters += $release->getTheaters();
            $filmCount++;
        }

        $averagePerTheater = $totalTheaters > 0 ? $totalGross / $totalTheaters : 0;
        $averagePerFilm = $filmCount > 0 ? $totalGross / $filmCount : 0;

        return [
            'total_gross' => $totalGross,
            'total_theaters' => $totalTheaters,
            'average_per_theater' => $averagePerTheater,
            'average_per_film' => $averagePerFilm,
            'film_count' => $filmCount
        ];
    }

    /**
     * Generate insights from weekend data
     */
    private function generateInsights(array $releases, array $metrics): array
    {
        $insights = [];
        $newReleases = [];
        $returningFilms = [];

        foreach ($releases as $release) {
            if ($release->getWeeks() <= 1) {
                $newReleases[] = $release;
            } else {
                $returningFilms[] = $release;
            }
        }

        $insights['new_releases_count'] = count($newReleases);
        $insights['returning_films_count'] = count($returningFilms);

        if (!empty($newReleases)) {
            $newReleasesGross = array_sum(array_map(fn($r) => $r->getGross(), $newReleases));
            $insights['new_releases_percentage'] = ($newReleasesGross / $metrics['total_gross']) * 100;
        }

        // Market concentration (top 3 films' share)
        $top3Gross = 0;
        for ($i = 0; $i < min(3, count($releases)); $i++) {
            $top3Gross += $releases[$i]->getGross();
        }
        $insights['top3_market_share'] = ($top3Gross / $metrics['total_gross']) * 100;

        return $insights;
    }

    /**
     * Analyze genre distribution
     */
    private function analyzeGenres(array $releases): array
    {
        $genreStats = [];

        foreach ($releases as $release) {
            $film = $release->getFilm();
            $genres = $film->getGenres();

            foreach ($genres as $genre) {
                $genreName = $genre->name;
                if (!isset($genreStats[$genreName])) {
                    $genreStats[$genreName] = [
                        'count' => 0,
                        'total_gross' => 0,
                        'films' => []
                    ];
                }

                $genreStats[$genreName]['count']++;
                $genreStats[$genreName]['total_gross'] += $release->getGross();
                $genreStats[$genreName]['films'][] = $film->getOriginal();
            }
        }

        // Sort by total gross
        uasort($genreStats, fn($a, $b) => $b['total_gross'] <=> $a['total_gross']);

        return $genreStats;
    }

    /**
     * Analyze budget vs performance
     */
    private function analyzeBudgets(array $releases): array
    {
        $budgetRanges = [
            'low' => ['min' => 0, 'max' => 50000000, 'films' => [], 'total_gross' => 0],
            'medium' => ['min' => 50000001, 'max' => 150000000, 'films' => [], 'total_gross' => 0],
            'high' => ['min' => 150000001, 'max' => PHP_INT_MAX, 'films' => [], 'total_gross' => 0]
        ];

        foreach ($releases as $release) {
            $film = $release->getFilm();
            $budget = $film->getBudget();

            if ($budget === null) continue;

            foreach ($budgetRanges as $range => &$data) {
                if ($budget >= $data['min'] && $budget <= $data['max']) {
                    $data['films'][] = [
                        'title' => $film->getOriginal(),
                        'budget' => $budget,
                        'gross' => $release->getGross(),
                        'roi' => $budget > 0 ? ($release->getGross() / $budget) : 0
                    ];
                    $data['total_gross'] += $release->getGross();
                    break;
                }
            }
        }

        return $budgetRanges;
    }

    /**
     * Analyze distributor performance
     */
    private function analyzeDistributors(array $releases): array
    {
        $distributorStats = [];

        foreach ($releases as $release) {
            $film = $release->getFilm();
            $distributor = $film->getDistributor();

            if ($distributor === null) continue;

            $distributorName = $distributor->name;
            if (!isset($distributorStats[$distributorName])) {
                $distributorStats[$distributorName] = [
                    'film_count' => 0,
                    'total_gross' => 0,
                    'films' => []
                ];
            }

            $distributorStats[$distributorName]['film_count']++;
            $distributorStats[$distributorName]['total_gross'] += $release->getGross();
            $distributorStats[$distributorName]['films'][] = $film->getOriginal();
        }

        // Sort by total gross
        uasort($distributorStats, fn($a, $b) => $b['total_gross'] <=> $a['total_gross']);

        return $distributorStats;
    }
}