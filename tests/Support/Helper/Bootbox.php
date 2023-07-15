<?php

namespace Tests\Support\Helper;

use Codeception\Module;
use Codeception\Module\WebDriver;

class Bootbox extends Module
{
    public function getWebDriver(): WebDriver
    {
        return $this->getModule(AntragsgruenWebDriver::class);
    }

    public function seeBootboxDialog(string $text): void
    {
        $this->getWebDriver()->wait(1);
        $this->getWebDriver()->see($text, '.bootbox');
    }

    public function dontSeeBootboxDialog(string $text): void
    {
        $this->getWebDriver()->wait(1);
        $this->getWebDriver()->dontSee($text, '.bootbox');
    }

    public function acceptBootboxAlert(): void
    {
        $this->getWebDriver()->executeJS('$(".bootbox .btn-primary").trigger("click")');
        $this->getWebDriver()->wait(1);
    }

    public function acceptBootboxConfirm(): void
    {
        $this->getWebDriver()->executeJS('$(".bootbox .btn-primary").trigger("click")');
        $this->getWebDriver()->wait(1);
    }
}
