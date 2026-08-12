<?php

declare(strict_types=1);

namespace BlackPrint\Commerce\Sync\Registry;

use BlackPrint\Commerce\Sync\Contracts\IngestionStage;
use RuntimeException;

final class StageRegistry
{
    /**
     * @var array<string, IngestionStage>
     */
    private array $stages = [];

    public function register(
        IngestionStage $stage
    ): void {

        $this->stages[
            $stage->resource()
        ] = $stage;
    }

    public function get(
        string $resource
    ): IngestionStage {

        if (! isset($this->stages[$resource])) {

            throw new RuntimeException(

                sprintf(
                    'Unknown ingestion resource: %s',
                    $resource
                )

            );
        }

        return $this->stages[$resource];
    }

    public function has(
        string $resource
    ): bool {

        return isset(
            $this->stages[$resource]
        );
    }

    /**
     * @return array<string, IngestionStage>
     */
    public function all(): array
    {
        return $this->stages;
    }
}
