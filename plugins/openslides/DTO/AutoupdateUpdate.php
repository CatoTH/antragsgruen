<?php

declare(strict_types=1);

namespace app\plugins\openslides\DTO;

use Symfony\Component\Serializer\Annotation\SerializedName;

class AutoupdateUpdate
{
    /**
     * @var AutoupdateChangedData
     * @SerializedName("changed")
     */
    private $changed;

    /**
     * @var array
     * @SerializedName("deleted")
     */
    private $deleted;

    /**
     * @var int
     * @SerializedName("from_change_id")
     */
    private $fromChangeId;

    /**
     * @var int
     * @SerializedName("to_change_id")
     *
     */
    private $toChangeId;

    /**
     * @var bool
     * @SerializedName("all_data")
     */
    private $allData;

    public function getChanged(): AutoupdateChangedData
    {
        return $this->changed;
    }

    public function setChanged(AutoupdateChangedData $changed): void
    {
        $this->changed = $changed;
    }

    public function getDeleted(): array
    {
        return $this->deleted;
    }

    public function setDeleted(array $deleted): void
    {
        $this->deleted = $deleted;
    }

    public function getFromChangeId(): int
    {
        return $this->fromChangeId;
    }

    public function setFromChangeId(int $fromChangeId): void
    {
        $this->fromChangeId = $fromChangeId;
    }

    public function getToChangeId(): int
    {
        return $this->toChangeId;
    }

    public function setToChangeId(int $toChangeId): void
    {
        $this->toChangeId = $toChangeId;
    }

    public function isAllData(): bool
    {
        return $this->allData;
    }

    public function setAllData(bool $allData): void
    {
        $this->allData = $allData;
    }
}
