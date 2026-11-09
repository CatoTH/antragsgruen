<?php

declare(strict_types=1);

namespace app\plugins\joulefish_simplify;

use app\models\db\Motion;
use app\models\layoutHooks\Hooks;

class LayoutHooks extends Hooks
{
    public function beforeMotionView(string $before, Motion $motion): string
    {
        return $before . Module::renderMotionWidget($motion);
    }
}

