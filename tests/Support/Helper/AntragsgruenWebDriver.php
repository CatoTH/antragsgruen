<?php

namespace Tests\Support\Helper;

use Codeception\Module\WebDriver;
use Codeception\Util\Uri;

class AntragsgruenWebDriver extends WebDriver
{
    /**
     * @param string $page
     * @throws \JsonException
     */
    public function amOnPage($page): void
    {
        $urlParts = parse_url($page);
        if (!isset($urlParts['host']) && !isset($urlParts['scheme'])) {
            $page = Uri::appendPath($this->_getConfig('url'), $page);
        }
        $this->debugSection('GET', $page);
        $this->webDriver->get($page);
    }
}
