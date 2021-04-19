<?php

declare(strict_types=1);

namespace app\models\settings;

class IMotionStatus
{
    /**
     * For plugin-defined IDs, use IDs of 100+
     * @var int
     */
    public $id;

    /**
     * e.g. "published"
     * @var string
     */
    public $name;

    /**
     * e.g. "publish"
     * @var string|null
     */
    public $nameVerb;

    /**
     * @var bool
     */
    public $adminInvisible;

    public function __construct(int $id, string $name, ?string $nameVerb = null, ?bool $adminInvisible = false)
    {
        $this->id = $id;
        $this->name = $name;
        $this->nameVerb = $nameVerb;
        $this->adminInvisible = $adminInvisible;
    }
}
