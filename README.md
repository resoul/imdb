<p align="center">
    <a href="https://www.boxofficemojo.com" target="_blank">
        <img src="https://m.media-amazon.com/images/S/sash/l6pNvrD703JE4jf.png" height="100px">
    </a>
    <h1 align="center">IMDB parser</h1>
    <br>
</p>

Box Office Mojo parser for demo purpose only.

## Table of Contents
1. [Overview](#overview)
2. [Installation](#installation)
3. [Quick Start](#quick-start)
4. [API Reference](#api-reference)
5. [Models](#models)
6. [Examples](#examples)
7. [Error Handling](#error-handling)
8. [Performance Considerations](#performance-considerations)
9. [Contributing](#contributing)

## Overview

The IMDB Parser is a PHP library that scrapes Box Office Mojo and IMDB Pro to extract movie and box office data. It provides a clean, object-oriented interface for retrieving weekend box office rankings, yearly box office data, and detailed film information.

### Features

- Parse weekend box office rankings
- Extract yearly box office data
- Retrieve detailed film information from IMDB Pro
- Built-in caching system
- Comprehensive data models
- Support for cast, crew, and financial data

### Requirements

- PHP 8.0 or higher
- ext-dom extension
- ext-libxml extension
- Guzzle HTTP client

## Installation

Install via Composer:

```bash
composer require resoul/imdb
```

## Quick Start

```php
<?php
use resoul\imdb\Parser;
use resoul\imdb\model\IMDB;

// Initialize parser with cache directory
$parser = new Parser();
$parser->setCacheFolder('/path/to/cache/');

try {
    // Parse weekend box office data
    $weekendData = $parser
        ->byWeekend('2024W48')
        ->run();
    
    echo "Weekend: " . $weekendData->getTitle() . "\n";
    
    foreach ($weekendData->getReleases() as $release) {
        echo sprintf(
            "#%d: %s - $%s\n",
            $release->getRank(),
            $release->getRelease(),
            number_format($release->getGross())
        );
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
```

## API Reference

### Parser Class

The main `Parser` class is the entry point for all parsing operations.

#### Constructor

```php
public function __construct()
```

Creates a new Parser instance with default HTTP client configuration.

**Example:**
```php
$parser = new Parser();
```

#### Methods

##### setCacheFolder(string $path): static

Sets the cache directory for storing downloaded HTML content.

**Parameters:**
- `$path` (string): Absolute path to cache directory

**Returns:** `static` - For method chaining

**Throws:** `Exception` - If directory cannot be created

**Example:**
```php
$parser->setCacheFolder('/var/cache/imdb/');
```

##### byWeekend(string $weekend): static

Configures parser to extract weekend box office data.

**Parameters:**
- `$weekend` (string): Weekend identifier in format "YYYYWNN" (e.g., "2024W48")

**Returns:** `static` - For method chaining

**Example:**
```php
$parser->byWeekend('2024W48');
```

##### byYear(string $year): static

Configures parser to extract yearly box office data.

**Parameters:**
- `$year` (string): Year in YYYY format (e.g., "2024")

**Returns:** `static` - For method chaining

**Example:**
```php
$parser->byYear('2024');
```

##### bySource(string $source): static

Configures parser to extract data from a custom URL.

**Parameters:**
- `$source` (string): Full URL to parse

**Returns:** `static` - For method chaining

**Example:**
```php
$parser->bySource('https://www.boxofficemojo.com/custom-url/');
```

##### run(): IMDB

Executes the parsing operation and returns structured data.

**Returns:** `IMDB` - Parsed box office data with releases

**Throws:** `Exception` - If parsing fails or network error occurs

**Example:**
```php
$data = $parser->byWeekend('2024W48')->run();
```

##### parseTitle(): Film

Parses a single film from IMDB Pro (requires setting source first).

**Returns:** `Film` - Detailed film information

**Throws:** `Exception` - If parsing fails

**Example:**
```php
$film = $parser
    ->bySource('https://pro.imdb.com/title/tt123456/')
    ->parseTitle();
```

## Models

### IMDB

Main container for parsed box office data.

#### Properties

- `title` (string): Descriptive title (e.g., "Weekend Box Office")
- `release` (Release[]): Array of release data

#### Methods

```php
public function getTitle(): string
public function getReleases(): array // Returns Release[]
```

### Release

Represents a single film's box office performance.

#### Properties

- `rank` (int): Box office ranking position
- `lastWeek` (int): Previous week's ranking (0 if new)
- `release` (string): Film title
- `uri` (string): Box Office Mojo URL
- `gross` (int): Weekend/period gross revenue in dollars
- `theaters` (int): Number of theaters (weekend data only)
- `total` (int): Total gross revenue in dollars
- `weeks` (int): Number of weeks in release
- `film` (Film): Detailed film information (populated after run())

#### Methods

```php
public function getRank(): int
public function getLastWeek(): int
public function getRelease(): string
public function getUri(): string
public function getGross(): int
public function getTheaters(): int
public function getTotal(): int
public function getWeeks(): int
public function getFilm(): Film
public function setFilm(Film $film): void
```

### Film

Comprehensive film information from IMDB Pro.

#### Properties

- `uid` (string): IMDB Pro URL
- `releaseUid` (string): Box Office Mojo URL
- `original` (string): Original film title
- `poster` (string): Poster image URL
- `description` (string): Plot summary
- `releaseDate` (string): Release date (Y-m-d format)
- `certificate` (string): MPAA rating
- `duration` (string): Runtime in minutes
- `genres` (GenreEnum[]): Array of genre enums
- `type` (FilmTypeEnum): Movie or TV series
- `opening` (int|null): Opening weekend gross
- `openingTheaters` (int|null): Opening weekend theater count
- `wideRelease` (int|null): Widest theater release
- `budget` (int|null): Production budget
- `gross` (Gross|null): Worldwide gross breakdown
- `cast` (Actor[]|null): Cast and crew information
- `distributor` (DistributorEnum|null): Distribution company

#### Methods

```php
public function getUID(): string
public function getRelease(): string
public function getOriginal(): string
public function getPoster(): string
public function getDescription(): string
public function getReleaseDate(): string
public function getDuration(): string
public function getCertificate(): string
public function getBudget(): ?int
public function getOpening(): ?int
public function getOpeningTheaters(): ?int
public function getWideRelease(): ?int
public function getSeasons(): int
public function getReleaseSummary(): ?string
public function getCast(): ?array // Returns Actor[]
public function getGross(): Gross
public function getType(): FilmTypeEnum
public function getDistributor(): ?DistributorEnum
public function getGenres(): array // Returns GenreEnum[]
```

### Gross

Box office revenue breakdown by market.

#### Properties

- `domestic` (int|null): US/Canada gross revenue
- `international` (int|null): International gross revenue
- `worldwide` (int|null): Worldwide total gross revenue

#### Methods

```php
public function getDomestic(): ?int
public function getInternational(): ?int
public function getWorldwide(): ?int
```

### Actor

Cast and crew member information.

#### Properties

- `original` (string): Person's name
- `uri` (string): IMDB Pro URL
- `role` (RoleEnum): Role type (Actor, Director, etc.)
- `roleName` (string|null): Character name (for actors)
- `poster` (string|null): Profile image URL

#### Methods

```php
public function getOriginal(): string
public function getUri(): string
public function getRole(): RoleEnum
public function getRoleName(): ?string
public function getPoster(): ?string
```

### Enums

#### FilmTypeEnum
- `MOVIE` (1): Feature film
- `SERIAL` (2): TV series or miniseries

#### GenreEnum
Available genres: Action, Drama, Comedy, Thriller, etc.

#### RoleEnum
Available roles: Actor, Director, Writer, Producer, etc.

#### DistributorEnum
Major film distributors: Warner Bros., Universal Pictures, etc.

## Examples

### Weekend Box Office Analysis

```php
<?php
use resoul\imdb\Parser;

$parser = new Parser();
$parser->setCacheFolder('./cache/');

try {
    $data = $parser->byWeekend('2024W48')->run();
    
    echo "=== " . $data->getTitle() . " ===\n\n";
    
    foreach ($data->getReleases() as $release) {
        $film = $release->getFilm();
        
        printf(
            "Rank #%d: %s\n" .
            "  Weekend Gross: $%s\n" .
            "  Theaters: %s\n" .
            "  Weeks in Release: %d\n" .
            "  Genre: %s\n" .
            "  Rating: %s\n" .
            "  Runtime: %s min\n\n",
            $release->getRank(),
            $film->getOriginal(),
            number_format($release->getGross()),
            number_format($release->getTheaters()),
            $release->getWeeks(),
            implode(', ', array_map(fn($g) => $g->name, $film->getGenres())),
            $film->getCertificate(),
            $film->getDuration()
        );
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
```

### Yearly Box Office Top 10

```php
<?php
use resoul\imdb\Parser;

$parser = new Parser();
$parser->setCacheFolder('./cache/');

try {
    $data = $parser->byYear('2024')->run();
    
    echo "=== Top 10 Films of 2024 ===\n\n";
    
    $top10 = array_slice($data->getReleases(), 0, 10);
    
    foreach ($top10 as $release) {
        $film = $release->getFilm();
        $gross = $film->getGross();
        
        printf(
            "#%d: %s\n" .
            "  Total Gross: $%s\n" .
            "  Budget: $%s\n" .
            "  Distributor: %s\n" .
            "  Release Date: %s\n\n",
            $release->getRank(),
            $film->getOriginal(),
            number_format($release->getGross()),
            $film->getBudget() ? '$' . number_format($film->getBudget()) : 'Unknown',
            $film->getDistributor()?->name ?? 'Unknown',
            date('F j, Y', strtotime($film->getReleaseDate()))
        );
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
```

### Detailed Film Information

```php
<?php
use resoul\imdb\Parser;

$parser = new Parser();
$parser->setCacheFolder('./cache/');

try {
    $film = $parser
        ->bySource('https://pro.imdb.com/title/tt15398776/')
        ->parseTitle();
    
    echo "=== " . $film->getOriginal() . " ===\n\n";
    echo "Description: " . $film->getDescription() . "\n\n";
    echo "Release Date: " . date('F j, Y', strtotime($film->getReleaseDate())) . "\n";
    echo "Runtime: " . $film->getDuration() . " minutes\n";
    echo "Rating: " . $film->getCertificate() . "\n";
    echo "Genres: " . implode(', ', array_map(fn($g) => $g->name, $film->getGenres())) . "\n\n";
    
    // Cast information
    $cast = $film->getCast();
    if ($cast) {
        echo "=== Cast & Crew ===\n";
        foreach ($cast as $person) {
            echo sprintf(
                "%s: %s%s\n",
                $person->getRole()->name,
                $person->getOriginal(),
                $person->getRoleName() ? ' as ' . $person->getRoleName() : ''
            );
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
```

### Advanced Usage with Error Handling

```php
<?php
use resoul\imdb\Parser;
use resoul\imdb\model\enum\FilmTypeEnum;

class BoxOfficeAnalyzer {
    private Parser $parser;
    
    public function __construct(string $cacheDir) {
        $this->parser = new Parser();
        $this->parser->setCacheFolder($cacheDir);
    }
    
    public function getTopMoviesOnly(string $weekend): array {
        try {
            $data = $this->parser->byWeekend($weekend)->run();
            $movies = [];
            
            foreach ($data->getReleases() as $release) {
                $film = $release->getFilm();
                
                // Filter only movies (exclude TV series)
                if ($film->getType() === FilmTypeEnum::MOVIE) {
                    $movies[] = [
                        'rank' => $release->getRank(),
                        'title' => $film->getOriginal(),
                        'gross' => $release->getGross(),
                        'theaters' => $release->getTheaters(),
                        'poster' => $film->getPoster(),
                        'description' => $film->getDescription(),
                    ];
                }
            }
            
            return $movies;
            
        } catch (Exception $e) {
            error_log("Failed to parse weekend data: " . $e->getMessage());
            return [];
        }
    }
    
    public function calculateMetrics(array $movies): array {
        if (empty($movies)) {
            return ['error' => 'No data available'];
        }
        
        $totalGross = array_sum(array_column($movies, 'gross'));
        $totalTheaters = array_sum(array_column($movies, 'theaters'));
        $avgPerTheater = $totalTheaters > 0 ? $totalGross / $totalTheaters : 0;
        
        return [
            'total_gross' => $totalGross,
            'total_theaters' => $totalTheaters,
            'average_per_theater' => $avgPerTheater,
            'movie_count' => count($movies),
        ];
    }
}

// Usage
$analyzer = new BoxOfficeAnalyzer('./cache/');
$movies = $analyzer->getTopMoviesOnly('2024W48');
$metrics = $analyzer->calculateMetrics($movies);

echo "Weekend Box Office Metrics:\n";
echo "Total Gross: $" . number_format($metrics['total_gross']) . "\n";
echo "Average per Theater: $" . number_format($metrics['average_per_theater']) . "\n";
```

## Error Handling

The parser throws `Exception` objects for various error conditions:

### Common Exceptions

1. **Network Errors**: Connection timeout, DNS resolution failures
2. **Parsing Errors**: Invalid HTML structure, missing elements
3. **Cache Errors**: Unable to create cache directory, permission issues
4. **Data Validation**: Invalid weekend format, missing required data

### Best Practices

```php
try {
    $data = $parser->byWeekend('2024W48')->run();
    
    // Always check if releases exist
    if (empty($data->getReleases())) {
        echo "No box office data found for this period.\n";
        return;
    }
    
    foreach ($data->getReleases() as $release) {
        // Check if film data was successfully parsed
        try {
            $film = $release->getFilm();
            // Process film data...
        } catch (Exception $e) {
            echo "Failed to get film details for: " . $release->getRelease() . "\n";
            continue;
        }
    }
    
} catch (Exception $e) {
    // Log error for debugging
    error_log("Parser error: " . $e->getMessage());
    
    // Provide user-friendly message
    echo "Unable to retrieve box office data. Please try again later.\n";
}
```

## Performance Considerations

### Caching

The parser includes a built-in caching system that stores downloaded HTML files to avoid repeated requests:

```php
// Cache files are stored as: {cache_folder}/{parsed_url}.html
// Pro IMDB pages are prefixed: pro.{parsed_url}.html
```

### Memory Usage

For large datasets (yearly data), consider processing releases in batches:

```php
$data = $parser->byYear('2024')->run();
$releases = $data->getReleases();

// Process in chunks of 10
foreach (array_chunk($releases, 10) as $batch) {
    foreach ($batch as $release) {
        // Process release...
    }
    
    // Optional: Clear memory between batches
    if (function_exists('gc_collect_cycles')) {
        gc_collect_cycles();
    }
}
```

### Rate Limiting

Be respectful of the target websites:

- The parser automatically caches requests to avoid redundant calls
- Consider adding delays between runs if processing multiple time periods
- Monitor your request frequency to avoid being blocked

This comprehensive documentation should help users understand and effectively use the IMDB parser library.