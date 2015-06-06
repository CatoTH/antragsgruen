<?php

namespace Codeception\Module;

//use \Kevintweber\PhpunitMarkupValidators\Assert\AssertHtml5;

class ValidatorHelper extends \Codeception\Module
{

    /**
     * @param string $html
     * @throws \Exception
     * @return array
     */
    private function postToHTMLValidator($html)
    {
        $curl = curl_init('http://validator.w3.org/check');
        curl_setopt_array($curl, [
            CURLOPT_POST           => 1,
            CURLOPT_POSTFIELDS     => [
                'fragment' => $html,
                'output'   => 'json',
            ],
            CURLOPT_FOLLOWLOCATION => 1,
            CURLOPT_HTTPHEADER     => ['Content-Type: multipart/form-data'],
            CURLOPT_RETURNTRANSFER => 1,
        ]);
        $return = curl_exec($curl);

        sleep(1); // As requested on https://validator.w3.org/docs/api.html

        if (curl_errno($curl) > 0) {
            throw new \Exception(curl_error($curl));
        }
        $info = curl_getinfo($curl);
        if ($info['http_code'] != 200) {
            throw new \Exception('Error retrieving validator data: HTTP: ' . $info['http_code']);
        }
        $data = json_decode($return, true);
        if (!$data || !isset($data['messages']) || !is_array($data['messages'])) {
            throw new \Exception('Invalid data returned from validation service: ' . $return);
        }
        return $data['messages'];
    }




    /**
     * @return string
     */
    private function getPageSource()
    {
        /** @var \Codeception\Module\WebDriver $webdriver */
        $webdriver = $this->getModule('WebDriver');
        return $webdriver->webDriver->getPageSource();
    }

    /**
     */
    public function validateHTML()
    {
        $source = $this->getPageSource();
        try {
            $messages = $this->postToHTMLValidator($source);
        } catch (\Exception $e) {
            $this->fail($e->getMessage());
            return;
        }
        $failMessages = [];
        $lines        = explode("\n", $source);
        foreach ($messages as $message) {
            if ($message['type'] == 'error') {
                $failMessages[] = '- Line ' . $message['lastLine'] . ', column ' . $message['lastColumn'] . ': ' .
                    $message['message'] . "\n  > " . $lines[$message['lastLine'] - 1];
            }
        }
        if (count($failMessages) > 0) {
            \PHPUnit_Framework_Assert::fail('Invalid HTML: ' . "\n" . implode("\n", $failMessages));
        }
    }

    /*

    private function postToFeedValidator($feed)
    {
        // @TODO Call does not work yet
        $ch = curl_init('http://validator.w3.org/feed/check.cgi');
        curl_setopt_array($ch, [
            CURLOPT_POST           => 1,
            CURLOPT_POSTFIELDS     => [
                'rawdata' => $feed,
                'manual' => '1',
                'output' => 'soap12',
            ],
            CURLOPT_FOLLOWLOCATION => 1,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
            CURLOPT_RETURNTRANSFER => 1,
        ]);
        $return = curl_exec($ch);

        sleep(1); // As requested on https://validator.w3.org/docs/api.html

        if (curl_errno($ch) > 0) {
            throw new \Exception(curl_error($ch));
        }
        $info = curl_getinfo($ch);
        if ($info['http_code'] != 200) {
            throw new \Exception('Error retrieving validator data: HTTP: ' . $info['http_code']);
        }
        $data = json_decode($return, true);
        if (!$data || !isset($data['messages']) || !is_array($data['messages'])) {
            throw new \Exception('Invalid data returned from validation service: ' . $return);
        }
        return $data['messages'];
    }

    public function validateRSS()
    {
        $source = $this->getPageSource();
        try {
            $messages = $this->postToFeedValidator($source);
        } catch (\Exception $e) {
            $this->fail($e->getMessage());
            return;
        }
        $failMessages = [];
        $lines        = explode("\n", $source);
        foreach ($messages as $message) {
            if ($message['type'] == 'error') {
                $failMessages[] = '- Line ' . $message['lastLine'] . ', column ' . $message['lastColumn'] . ': ' .
                    $message['message'] . "\n  > " . $lines[$message['lastLine'] - 1];
            }
        }
        if (count($failMessages) > 0) {
            \PHPUnit_Framework_Assert::fail('Invalid Feed: ' . "\n" . implode("\n", $failMessages));
        }
    }
     */
}
