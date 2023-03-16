<?php

declare(strict_types=1);

namespace app\plugins\dbwv;

use app\plugins\ModuleBase;

class Module extends ModuleBase
{
    public static function getProvidedTranslations(): array
    {
        return ['de'];
    }
}
