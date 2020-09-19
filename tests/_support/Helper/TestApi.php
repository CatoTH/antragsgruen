<?php

namespace Helper;

class TestApi extends \Codeception\Module
{
    public function getUrlBase()
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

        return $webdriver->_getConfig('url');
    }

    private function executeCall($subdomain, $consultationUrl, $operation, $data)
    {
        $url = 'http://localhost/' . $subdomain . '/' . $consultationUrl . '/test/' . $operation;

        $handle = curl_init();
        curl_setopt($handle, CURLOPT_URL, $url);
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($handle, CURLOPT_HTTPHEADER, ['User-Agent: Antragsgruen/Tester']);
        curl_setopt($handle, CURLOPT_POST, 1);
        curl_setopt($handle, CURLOPT_POSTFIELDS, http_build_query($data));

        $data = curl_exec($handle);
        $info = curl_getinfo($handle);
        curl_close($handle);
        if ($info['http_code'] !== 200) {
            throw new \Exception('File not found: ' . $info['http_code'] . ' / ' . $url);
        }

        return json_decode($data, true);
    }

    public function apiSetAmendmentStatus($subdomain, $consultationUrl, $amendmentId, $status)
    {
        $ret = $this->executeCall($subdomain, $consultationUrl, 'set-amendment-status', [
            'id'     => $amendmentId,
            'status' => $status,
        ]);

        $this->assertTrue($ret['success']);
    }
}
