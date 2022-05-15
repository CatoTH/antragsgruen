<?php

namespace app\plugins\openslides;

use app\models\exceptions\Internal;
use app\models\settings\Site;

class SiteSettings extends Site
{
    /** @var string|null */
    public $osBaseUri; // https://demo.openslides.org/

    /** @var string */
    public $osApiKey;

    public function getAuthPrefix(): string
    {
        if (!isset($this->osBaseUri)) {
            throw new Internal('Could not parse osBaseUri');
        }
        $url = parse_url($this->osBaseUri);
        if (is_array($url) && isset($url['host'])) {
            return 'openslides-' . $url['host'];
        } else {
            throw new Internal('Could not parse osBaseUri');
        }
    }
}
