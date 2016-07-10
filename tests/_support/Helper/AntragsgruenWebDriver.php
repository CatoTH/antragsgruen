<?php
namespace Helper;

use Codeception\TestCase;
use Codeception\Util\Uri;

class AntragsgruenWebDriver extends \Codeception\Module\WebDriver
{
    /**
     * @param string $page
     */
    public function amOnPage($page)
    {
        $urlParts = parse_url($page);
        if (!isset($urlParts['host']) && !isset($urlParts['scheme'])) {
            $page = Uri::appendPath($this->_getConfig('url'), $page);
        }
        $this->debugSection('GET', $page);
        $this->webDriver->get($page);
    }
}