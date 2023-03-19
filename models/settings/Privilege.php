<?php

declare(strict_types=1);

namespace app\models\settings;

class Privilege
{
    public int $id;
    public string $name;
    public bool $motionRestrictable;
    public ?int $dependentOnId;

    public function __construct(int $id, string $name, bool $motionRestrictable, ?int $dependentOnId)
    {
        $this->id = $id;
        $this->name = $name;
        $this->motionRestrictable = $motionRestrictable;
        $this->dependentOnId = $dependentOnId;
    }
}
