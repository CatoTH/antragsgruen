<?php
namespace Helper;

use Codeception\TestCase;

class Download extends \Codeception\Module
{
    /**
     * @param string $selector
     * @return string
     */
    public function getAbsoluteHref($selector)
    {
        if ($this->hasModule('\Helper\AntragsgruenWebDriver')) {
            /** @var \Helper\AntragsgruenWebDriver $webdriver */
            $webdriver = $this->getModule('\Helper\AntragsgruenWebDriver');
        } elseif ($this->hasModule('WebDriver')) {
            /** @var \Codeception\Module\WebDriver $webdriver */
            $webdriver = $this->getModule('WebDriver');
        } else {
            throw new \Exception("WebDriver not found");
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
    public function downloadLink($selector)
    {
        $url    = $this->getAbsoluteHref($selector);
        $handle = curl_init();
        curl_setopt($handle, CURLOPT_URL, $url);
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, 1);
        $data = curl_exec($handle);
        $info = curl_getinfo($handle);
        curl_close($handle);
        if ($info['http_code'] != 200) {
            throw new \Exception('File not found: ' . $info['http_code'] . ' / ' . $url);
        }
        return $data;
    }
}
