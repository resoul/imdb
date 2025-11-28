<?php
namespace Resoul\Imdb\Mapper;


use Resoul\Imdb\Domain\Gross;
use Resoul\Imdb\DTO\BoxOfficeDataDTO;
use Resoul\Imdb\DTO\GrossDataDTO;
use Resoul\Imdb\Enum\DistributorEnum;

/**
 * Maps between BoxOfficeDataDto and related models
 */
class BoxOfficeDataMapper
{
    /**
     * Convert GrossDataDTO to Gross model
     *
     * @param GrossDataDTO|null $dto
     * @return Gross|null
     */
    public function mapGross(?GrossDataDTO $dto): ?Gross
    {
        if ($dto === null) {
            return null;
        }

        return new Gross(
            domestic: $dto->domestic,
            international: $dto->international,
            worldwide: $dto->worldwide
        );
    }

    /**
     * Map distributor name to enum
     *
     * @param string|null $distributorName
     * @return DistributorEnum|null
     */
    public function mapDistributor(?string $distributorName): ?DistributorEnum
    {
        if ($distributorName === null) {
            return null;
        }

        $distributors = array_flip(DistributorEnum::getLabels());

        if (isset($distributors[$distributorName])) {
            return DistributorEnum::tryFrom($distributors[$distributorName]);
        }

        return null;
    }

    /**
     * Extract data for Film model creation
     *
     * @param BoxOfficeDataDTO $dto
     * @return array
     */
    public function toFilmData(BoxOfficeDataDTO $dto): array
    {
        return [
            'uid' => $dto->imdbProUri,
            'releaseUid' => $dto->boxOfficeMojoUri,
            'releaseDate' => $dto->releaseDate,
            'budget' => $dto->budget,
            'opening' => $dto->opening,
            'openingTheaters' => $dto->openingTheaters,
            'wideRelease' => $dto->wideRelease,
            'gross' => $this->mapGross($dto->gross),
            'distributor' => $this->mapDistributor($dto->distributor),
        ];
    }
}