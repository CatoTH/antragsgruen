<?php

namespace app\plugins\swagger_ui;

use app\components\view\AssetBundle;

class Assets extends AssetBundle
{
    public function getSourcePath(): string
    {
        return __DIR__ . '/assets';
    }
}
