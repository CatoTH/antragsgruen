<?php

namespace app\plugins\gruen_ci;

use app\components\view\AssetBundle;

class Assets3 extends AssetBundle
{
    public array $css = [
        'layout-gruen_ci3.css',
    ];

    public function getSourcePath(): string
    {
        return __DIR__ . '/assets';
    }
}
