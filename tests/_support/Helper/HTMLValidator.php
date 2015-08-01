<?php
namespace Helper;

use Codeception\TestCase;

class HTMLValidator extends \Codeception\Module
{
    /**
     * @param string $html
     * @return array
     * @throws \Exception
     */
    private function validateByVNU($html)
    {
        $filename = '/tmp/' . uniqid('html-validate') . '.html';
        file_put_contents($filename, $html);
        exec("java -Xss1024k -jar /usr/local/bin/vnu.jar --format json " . $filename . " 2>&1", $return);
        $data = json_decode($return[0], true);
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
     * @param string[] $ignoreMessages
     */
    public function validateHTML($ignoreMessages = [])
    {
        $source = $this->getPageSource();
        try {
            $messages = $this->validateByVNU($source);
        } catch (\Exception $e) {
            $this->fail($e->getMessage());
            return;
        }
        $failMessages = [];
        $lines        = explode("\n", $source);
        foreach ($messages as $message) {
            if ($message['type'] == 'error') {
                $formattedMsg = '- Line ' . $message['lastLine'] . ', column ' . $message['lastColumn'] . ': ' .
                    $message['message'] . "\n  > " . $lines[$message['lastLine'] - 1];
                $ignoring = false;
                foreach ($ignoreMessages as $ignoreMessage) {
                    if (mb_stripos($formattedMsg, $ignoreMessage) !== false) {
                        $ignoring = true;
                    }
                }
                if (!$ignoring) {
                    $failMessages[] = $formattedMsg;
                }
            }
        }
        if (count($failMessages) > 0) {
            \PHPUnit_Framework_Assert::fail('Invalid HTML: ' . "\n" . implode("\n", $failMessages));
        }
    }
}
