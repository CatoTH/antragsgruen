<?php

declare(strict_types=1);

namespace app\models\api\imotion;

class AmendmentUpdateSection
{
    /** Raw POST array data for section types that require structured input (e.g. TextSimple in single-paragraph mode) */
    private mixed $rawData = null;

    public function __construct(
        public int $sectionId,
        public ?string $data = null,
    ) {
    }

    public function getRawData(): mixed
    {
        return $this->rawData;
    }

    public function setRawData(mixed $rawData): self
    {
        $this->rawData = $rawData;

        return $this;
    }
}
