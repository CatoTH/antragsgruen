<?php
namespace Helper;

use Codeception\Module\WebDriver;
use Codeception\TestCase;
use Codeception\Util\Uri;

require_once(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' .
    DIRECTORY_SEPARATOR . 'config'
    . DIRECTORY_SEPARATOR . 'AntragsgruenSetupDB.php');

class Acceptance extends \Codeception\Module
{
    use \app\tests\AntragsgruenSetupDB;

    public function _before(TestCase $test): void
    {
        $this->createDB();
    }

    public function _after(TestCase $test): void
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
        $this->populateDB(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR .
            '..' . DIRECTORY_SEPARATOR . '_data' . DIRECTORY_SEPARATOR . 'dbdata1.sql');
    }

    public function populateDBDataYfj(): void
    {
        $this->populateDB(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR .
            '..' . DIRECTORY_SEPARATOR . '_data' . DIRECTORY_SEPARATOR . 'dbdata-yfj.sql');
    }
}
