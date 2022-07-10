<?php

declare(strict_types=1);

namespace app\models\settings;

class IMotionStatus
{
    /**
     * For plugin-defined IDs, use IDs of 100+
     */
    public int $id;

    /**
     * e.g. "published"
     */
    public string $name;

    /**
     * e.g. "publish"
     */
    public ?string $nameVerb;

    public ?bool $adminInvisible;
    public ?bool $userInvisible;

    public function __construct(int $id, string $name, ?string $nameVerb = null, ?bool $adminInvisible = false, ?bool $userInvisible = false)
    {
        $this->id = $id;
        $this->name = $name;
        $this->nameVerb = $nameVerb;
        $this->adminInvisible = $adminInvisible;
        $this->userInvisible = $userInvisible;
    }
}
