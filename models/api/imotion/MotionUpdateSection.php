<?php

declare(strict_types=1);

namespace app\models\api\imotion;

class MotionUpdateSection
{
    private ?array $fileData = null;

    public function __construct(
        public int $sectionId,
        public mixed $data = null,
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
