<?php
namespace resoul\imdb\model;

use JsonSerializable;

/**
 * Comparison Report Class
 */
class ComparisonReport implements JsonSerializable
{
    private array $weekendData;
    private array $errors;

    public function __construct(array $weekendData, array $errors = [])
    {
        $this->weekendData = $weekendData;
        $this->errors = $errors;
    }

    public function getWeekends(): array
    {
        return $this->weekendData;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }

    public function getGrowthRate(string $from, string $to): ?float
    {
        if (!isset($this->weekendData[$from]) || !isset($this->weekendData[$to])) {
            return null;
        }

        $fromGross = $this->weekendData[$from]['total_gross'];
        $toGross = $this->weekendData[$to]['total_gross'];

        if ($fromGross == 0) return null;

        return (($toGross - $fromGross) / $fromGross) * 100;
    }

    public function toJson(): string
    {
        return json_encode($this, JSON_PRETTY_PRINT);
    }

    public function jsonSerialize(): array
    {
        return [
            'comparison' => $this->weekendData,
            'errors' => $this->errors,
            'summary' => [
                'weekends_processed' => count($this->weekendData),
                'errors_count' => count($this->errors)
            ]
        ];
    }
}