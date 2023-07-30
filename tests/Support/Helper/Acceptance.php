<?php
namespace Tests\Support\Helper;

use Codeception\Module;
use Codeception\Module\WebDriver;
use Codeception\TestInterface;
use Codeception\Util\Uri;
use Tests\config\AntragsgruenSetupDB;

class Acceptance extends Module
{
    use AntragsgruenSetupDB;

    public function _before(TestInterface $test): void
    {
        $this->createDB();
    }

    public function _after(TestInterface $test): void
    {
        $this->deleteDB();
    }

    public function amOnPage2(string $page): void
    {
        $urlParts = parse_url($page);
        /** @var WebDriver $webdriver */
        $webdriver = $this->getModule('WebDriver');
        if (!isset($urlParts['host']) && !isset($urlParts['scheme'])) {
            $page = Uri::appendPath($webdriver->_getConfig('url'), $page);
        }
        $this->debugSection('GET', $page);
        $webdriver->webDriver->get($page);
    }

    public function populateDBData1(): void
    {
        $this->populateDB(__DIR__.'/../Data/dbdata1.sql');
    }

    public function populateDBDataYfj(): void
    {
        $this->populateDB(__DIR__.'/../Data/dbdata-yfj.sql');
    }

    public function populateDBDataDbwv(): void
    {
        $this->populateDB(__DIR__.'/../Data/dbdata-dbwv.sql');
    }
}
