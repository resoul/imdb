<?php
namespace Resoul\Imdb\Mapper;

use Resoul\Imdb\Domain\Release;
use Resoul\Imdb\DTO\ReleaseDataDTO;

class ReleaseDataMapper
{
    /**
     * Convert ReleaseDataDto to Release model
     *
     * @param ReleaseDataDto $dto
     * @return Release
     */
    public function toModel(ReleaseDataDto $dto): Release
    {
        return new Release(
            release: $dto->title,
            uri: $dto->uri,
            theaters: $dto->theaters,
            rank: $dto->rank,
            lastWeek: $dto->lastWeek,
            weeks: $dto->weeks,
            gross: $dto->gross,
            total: $dto->total,
        );
    }

    /**
     * Convert array of DTOs to array of models
     *
     * @param array<ReleaseDataDto> $dtos
     * @return array<Release>
     */
    public function toModels(array $dtos): array
    {
        return array_map(
            fn(ReleaseDataDTO $dto) => $this->toModel($dto),
            $dtos
        );
    }
}