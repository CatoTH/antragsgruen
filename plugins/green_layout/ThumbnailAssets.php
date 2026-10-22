<?php

namespace app\plugins\green_layout;

use app\components\view\AssetBundle;

class ThumbnailAssets extends AssetBundle
{
    public function getSourcePath(): string
    {
        return __DIR__ . '/assets';
    }
}
