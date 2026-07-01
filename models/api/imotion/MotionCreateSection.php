<?php

declare(strict_types=1);

namespace app\models\api\imotion;

class MotionCreateSection
{
    private ?array $fileData = null;

    public function __construct(
        public int $sectionId,
        public ?string $data = null,
        public ?bool $deleted = null,
    ) {
    }

    public function getFileData(): ?array
    {
        return $this->fileData;
    }

    public function setFileData(?array $fileData): self
    {
        $this->fileData = $fileData;

        return $this;
    }
}
