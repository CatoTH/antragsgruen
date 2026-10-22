<?php

namespace app\plugins\green_layout;

use app\components\view\AssetBundle;

class Assets extends AssetBundle
{
    public array $css = [
        'layout-green_layout.css',
    ];

    public function getSourcePath(): string
    {
        return __DIR__ . '/assets';
    }
}
