<?php

namespace app\plugins\openslides;

use app\models\exceptions\Internal;
use app\models\settings\Site;

class SiteSettings extends Site
{
    public ?string $osBaseUri = null; // https://demo.openslides.org/
    public string $osApiKey;

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
