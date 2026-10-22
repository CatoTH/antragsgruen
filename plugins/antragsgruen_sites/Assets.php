<?php

namespace app\plugins\antragsgruen_sites;

use app\components\view\AssetBundle;

class Assets extends AssetBundle
{
    public array $css = [
        'sites.css',
    ];

    public function getSourcePath(): string
    {
        return __DIR__ . '/assets';
    }
}
