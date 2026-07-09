<?php

declare(strict_types=1);

namespace app\models\api\imotion;

use Symfony\Component\Serializer\Attribute\Ignore;

class AmendmentUpdateSection
{
    /**
     * Section data that cannot be expressed as the plain string in $data: structured POST input
     * (e.g. TextSimple in single-paragraph mode) or an UploadedFileRef for file-based sections.
     * Only set by the fromWebRequest() builders, never by API deserialization (enforced by #[Ignore]).
     */
    #[Ignore]
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
