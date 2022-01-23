<?php

namespace app\plugins\green_manager;

use app\models\settings\Site;

class SiteSettings extends Site
{
    /** @var bool */
    public $isConfirmed = false;
}
