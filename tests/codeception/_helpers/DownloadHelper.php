<?php

namespace Codeception\Module;

class DownloadHelper extends \Codeception\Module
{
    /**
     * @param string $selector
     * @return string
     */
    public function getAbsoluteHref($selector)
    {
        /** @var \Codeception\Module\WebDriver $webdriver */
        $webdriver = $this->getModule('WebDriver');
        return $webdriver->executeJS('
            var $element = $("' . $selector . '");
            return $element.attr("href");
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
        $url = $this->getAbsoluteHref($selector);
        $ch  = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $data = curl_exec($ch);
        $info = curl_getinfo($ch);
        curl_close($ch);
        if ($info['http_code'] != 200) {
            throw new \Exception('File not found: ' . $info['http_code']);
        }
        return $data;
    }
}
