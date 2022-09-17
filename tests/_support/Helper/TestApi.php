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

    private function executeCall($subdomain, $consultationUrl, $operation, $data): array
    {
        $baseUrl = str_replace(['{SUBDOMAIN}', '{PATH}'], [$subdomain, $consultationUrl], \AcceptanceTester::ABSOLUTE_URL_TEMPLATE_SITE);
        $url = $baseUrl . '/test/' . $operation;

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

    public function apiSetAmendmentStatus($subdomain, $consultationUrl, $amendmentId, $status): void
    {
        $ret = $this->executeCall($subdomain, $consultationUrl, 'set-amendment-status', [
            'id'     => $amendmentId,
            'status' => $status,
        ]);

        $this->assertTrue($ret['success']);
    }

    public function apiSetUserFixedData($subdomain, $consultationUrl, $email, $nameGiven, $nameFamily, $organisation, $fixed): void
    {
        $ret = $this->executeCall($subdomain, $consultationUrl, 'set-user-fixed-data', [
            'email'     => $email,
            'nameGiven' => $nameGiven,
            'nameFamily' => $nameFamily,
            'organisation' => $organisation,
            'fixed' => $fixed,
        ]);

        $this->assertTrue($ret['success']);
    }

    public function apiSetUserVoted(
        string $subdomain,
        string $consultationUrl,
        string $email,
        int $votingBlockId,
        int $itemId,
        string $answer
    ): void {
        $ret = $this->executeCall($subdomain, $consultationUrl, 'user-votes', [
            'email' => $email,
            'votingBlock' => $votingBlockId,
            'itemId' => $itemId,
            'answer' => $answer,
        ]);

        $this->assertTrue($ret['success']);
    }
}
