<?php

/**
 * A helper class for Codeception (http://codeception.com/) that allows automated HTML5 Validation
 * using the Nu Html Checker (http://validator.github.io/validator/) during acceptance testing.
 * It uses local binaries and can therefore be run offline.
 *
 *
 * Requirements:
 * =============
 *
 * - Codeception with WebDriver set up (PhpBrowser doesn't work)
 * - java is installed locally
 * - The vnu.jar is installed locally (download the .zip from https://github.com/validator/validator/releases,
 *   it contains the .jar file)
 *
 *
 * Installation:
 * =============
 *
 * - Copy this file to _support/Helper/ in the codeception directory
 * - Merge the following configuration to Acceptance.suite.yml:
 *
 * modules:
 *   enabled:
 *     - \Helper\HTMLValidator
 *   config:
 *     \Helper\HTMLValidator:
 *       javaPath: /usr/bin/java
 *       vnuPath: /usr/local/bin/vnu.jar
 *
 *
 *
 * Usage:
 * ======
 *
 * Validate the HTML of the current page:
 * $I->validateHTML();
 *
 * Validate the HTML of the current page, but ignore errors containing the string "Ignoreit":
 * $I->validateHTML(["Ignoreme"]);
 *
 *
 *
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 * @author Tobias Hößl <tobias@hoessl.eu>
 */


namespace Tests\Support\Helper;

use Codeception\Module;
use Exception;
use PHPUnit\Framework\Assert;
use RuntimeException;

class HTMLValidator extends Module
{
    /**
     * @param string $html
     * @return array
     * @throws \Exception
     */
    private function validateByVNU(string $html): array
    {
        $javaPath = $this->_getConfig('javaPath');
        if (!$javaPath) {
            $javaPath = 'java';
        }
        $vnuPath = $this->_getConfig('vnuPath');
        if (!$vnuPath) {
            $vnuPath = '/usr/local/bin/vnu.jar';
        }

        $filename = DIRECTORY_SEPARATOR . 'tmp' . DIRECTORY_SEPARATOR . uniqid('html-validate', true).'.html';
        file_put_contents($filename, $html);
        exec($javaPath . " -Xss1024k -jar " . $vnuPath . " --format json " . $filename . " 2>&1", $return);
        $data = json_decode($return[0], true);
        unlink($filename);
        if (!$data || !isset($data['messages']) || !is_array($data['messages'])) {
            throw new RuntimeException('Invalid data returned from validation service: '. implode("\n", $return));
        }
        return $data['messages'];
    }


    /**
     * @return string
     * @throws \Codeception\Exception\ModuleException
     * @throws \Exception
     */
    private function getPageSource(): string
    {
        if (!$this->hasModule('WebDriver') && !$this->hasModule(AntragsgruenWebDriver::class)) {
            throw new RuntimeException('This validator needs WebDriver to work');
        }

        if ($this->hasModule(AntragsgruenWebDriver::class)) {
            /** @var \Tests\Support\Helper\AntragsgruenWebDriver $webdriver */
            $webdriver = $this->getModule(AntragsgruenWebDriver::class);
            $html = $webdriver->webDriver->getPageSource();
        } elseif ($this->hasModule('WebDriver')) {
            /** @var \Codeception\Module\WebDriver $webdriver */
            $webdriver = $this->getModule('WebDriver');
            $html = $webdriver->webDriver->getPageSource();
        } else {
            throw new RuntimeException('This validator needs WebDriver to work');
        }
        if (!str_contains($html, '<!DOCTYPE html>')) {
            // Seems to be stripped by getPageSource()
            $html = '<!DOCTYPE html>' . $html;
        }
        return $html;
    }

    /**
     * @param string[] $ignoreMessages
     */
    public function validateHTML(array $ignoreMessages = []): void
    {
        $source = $this->getPageSource();
        try {
            $messages = $this->validateByVNU($source);
        } catch (Exception $e) {
            $this->fail($e->getMessage());
            return;
        }
        $failMessages = [];
        $lines        = explode("\n", $source);
        foreach ($messages as $message) {
            if ($message['type']==='error') {
                $formattedMsg = '- Line ' . $message['lastLine'] . ', column ' . $message['lastColumn'] . ': ' .
                    $message['message'] . "\n  > " . $lines[$message['lastLine'] - 1];
                $ignoring     = false;
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
            Assert::fail('Invalid HTML: ' . "\n" . implode("\n", $failMessages));
        }
    }
}
