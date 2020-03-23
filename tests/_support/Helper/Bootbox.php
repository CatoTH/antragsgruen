<?php

namespace Helper;

class Bootbox extends \Codeception\Module
{
    public function getWebDriver(): \Codeception\Module\WebDriver
    {
        return $this->getModule('\Helper\AntragsgruenWebDriver');
    }

    public function seeBootboxDialog(string $text)
    {
        $this->getWebDriver()->wait(1);
        $this->getWebDriver()->see($text, '.bootbox');
    }

    public function dontSeeBootboxDialog(string $text)
    {
        $this->getWebDriver()->wait(1);
        $this->getWebDriver()->dontSee($text, '.bootbox');
    }

    public function acceptBootboxAlert()
    {
        $this->getWebDriver()->executeJS('$(".bootbox .btn-primary").trigger("click")');
        $this->getWebDriver()->wait(1);
    }

    public function acceptBootboxConfirm()
    {
        $this->getWebDriver()->executeJS('$(".bootbox .btn-primary").trigger("click")');
        $this->getWebDriver()->wait(1);
    }
}
