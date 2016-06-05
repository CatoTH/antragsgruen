<?php
namespace Helper;

use Codeception\TestCase;

class Bootbox extends \Codeception\Module
{
    /**
     * @return \Codeception\Module\WebDriver
     * @throws \Codeception\Exception\ModuleException
     */
    public function getWebDriver()
    {
        return $this->getModule('\Helper\AntragsgruenWebDriver');
    }

    /**
     * @param string $text
     */
    public function seeBootboxDialog($text)
    {
        $this->getWebDriver()->wait(1);
        $this->getWebDriver()->see($text, '.bootbox');
    }

    /**
     * @param string $text
     */
    public function dontSeeBootboxDialog($text)
    {
        $this->getWebDriver()->wait(1);
        $this->getWebDriver()->dontSee($text, '.bootbox');
    }

    /**
     */
    public function acceptBootboxAlert()
    {
        $this->getWebDriver()->click('.bootbox .btn-primary');
        $this->getWebDriver()->wait(1);
    }

    /**
     */
    public function acceptBootboxConfirm()
    {
        $this->getWebDriver()->click('.bootbox .btn-primary');
        $this->getWebDriver()->wait(1);
    }
}
