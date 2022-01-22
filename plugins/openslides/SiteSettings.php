<?php

namespace app\plugins\openslides;

use app\models\exceptions\Internal;
use app\models\settings\Site;

class SiteSettings extends Site
{
    /** @var string */
    public $osBaseUri; // https://demo.openslides.org/

    public function getAuthPrefix(): string
    {
        $url = parse_url($this->osBaseUri);
        if (is_array($url) && isset($url['host'])) {
            return 'openslides-' . $url['host'];
        } else {
            throw new Internal('Could not parse osBaseUri');
        }
    }
}
