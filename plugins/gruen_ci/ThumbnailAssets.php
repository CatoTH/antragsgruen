<?php

namespace app\plugins\gruen_ci;

use app\components\view\AssetBundle;

class ThumbnailAssets extends AssetBundle
{
    public function getSourcePath(): string
    {
        return __DIR__ . '/assets';
    }
}
