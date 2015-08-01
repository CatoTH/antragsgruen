<?php
namespace Helper;

use Codeception\TestCase;

class AccessibilityValidator extends \Codeception\Module
{
    public static $SUPPORTED_STANDARDS = [
        'WCAG2AAA',
        'WCAG2AA',
        'WCAG2A',
        'Section508',
    ];
    const STANDARD_WCAG2AAA   = 'WCAG2AAA';
    const STANDARD_WCAG2AA    = 'WCAG2AA';
    const STANDARD_WCAG2A     = 'WCAG2A';
    const STANDARD_SECTION508 = 'Section508';

    /**
     * @return string
     */
    private function getPageUrl()
    {
        /** @var \Codeception\Module\WebDriver $webdriver */
        $webdriver = $this->getModule('WebDriver');
        return $webdriver->webDriver->getCurrentURL();
    }

    /**
     * @param string $url
     * @return array
     * @throws \Exception
     */
    private function validateByPa11y($url, $standard)
    {
        if (!in_array($standard, static::$SUPPORTED_STANDARDS)) {
            throw new \Exception('Unknown standard: ' . $standard);
        }
        exec('pa11y -s ' . $standard . ' -r json "' . $url . '"', $return);
        $data = json_decode($return[0], true);
        if (!$data) {
            $msg = 'Invalid data returned from validation service: ';
            throw new \Exception($msg . $return);
        }
        return $data;
    }

    /**
     * @param string $standard
     * @param string[] $ignoreMessages
     */
    public function validatePa11y($standard = 'WCAG2AA', $ignoreMessages = [])
    {
        try {
            $url      = $this->getPageUrl();
            $messages = $this->validateByPa11y($url, $standard);
        } catch (\Exception $e) {
            $this->fail($e->getMessage());
            return;
        }
        $failMessages = [];
        foreach ($messages as $message) {
            if ($message['type'] == 'error') {
                $string = $message['code'] . "\n" . $message['selector'] . ': ';
                $string .= $message['context'] . "\n";
                $string .= $message['message'];
                $ignoring = false;
                foreach ($ignoreMessages as $ignoreMessage) {
                    if (mb_stripos($string, $ignoreMessage) !== false) {
                        $ignoring = true;
                    }
                }
                if (!$ignoring) {
                    $failMessages[] = $string;
                }
            }
        }
        if (count($failMessages) > 0) {
            $failStr = 'Failed ' . $standard . ' check: ' . "\n";
            $failStr .= implode("\n\n", $failMessages);
            \PHPUnit_Framework_Assert::fail($failStr);
        }
    }
}
