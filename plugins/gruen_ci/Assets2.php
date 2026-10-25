<?php

namespace app\plugins\gruen_ci;

use app\components\view\AssetBundle;

class Assets2 extends AssetBundle
{
    public array $css = [
        'layout-gruen_ci2.css',
    ];

    public function getSourcePath(): string
    {
        return __DIR__ . '/assets';
    }
}
