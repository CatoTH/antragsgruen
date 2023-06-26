<?php
namespace Tests\Support\Helper;

use Codeception\Module;
use RuntimeException;

class Download extends Module
{
    /**
     * @param string $selector
     * @return string
     * @throws \Codeception\Exception\ModuleException
     */
    public function getAbsoluteHref(string $selector): string
    {
        if ($this->hasModule(AntragsgruenWebDriver::class)) {
            /** @var \Tests\Support\Helper\AntragsgruenWebDriver $webdriver */
            $webdriver = $this->getModule(AntragsgruenWebDriver::class);
        } elseif ($this->hasModule('WebDriver')) {
            /** @var \Codeception\Module\WebDriver $webdriver */
            $webdriver = $this->getModule('WebDriver');
        } else {
            throw new RuntimeException("WebDriver not found");
        }

        return $webdriver->executeJS('
            var $element = $("' . $selector . '");
            //return $element.attr("href");
            var a = document.createElement("a");
            a.href = $element.attr("href");
            return a.href;
        ');
    }

    /**
     * @param string $selector
     * @return string
     * @throws \Exception
     */
    public function downloadLink(string $selector): string
    {
        $url    = $this->getAbsoluteHref($selector);
        $handle = curl_init();
        curl_setopt($handle, CURLOPT_URL, $url);
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, 1);
        $data = curl_exec($handle);
        $info = curl_getinfo($handle);
        curl_close($handle);
        if ($info['http_code'] !== 200) {
            throw new RuntimeException('File not found: '.$info['http_code'].' / '.$url);
        }
        return $data;
    }
}
