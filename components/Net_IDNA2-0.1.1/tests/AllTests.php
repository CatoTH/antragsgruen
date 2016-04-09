<?php

if (!defined('PHPUnit2_MAIN_METHOD')) {
    define('PHPUnit2_MAIN_METHOD', 'Net_IDNA2_AllTests::main');
}

require_once 'PHPUnit2/TextUI/TestRunner.php';

require_once 'Net_IDNA2Test.php';
require_once 'draft-josefsson-idn-test-vectors.php';

class Net_IDNA2_AllTests
{
    public static function main()
    {
        PHPUnit2_TextUI_TestRunner::run(self::suite());
    }

    public static function suite()
    {
        $suite = new PHPUnit2_Framework_TestSuite('PEAR - Net_IDNA2');

        $suite->addTestSuite('Net_IDNA2Test');
        $suite->addTestSuite('draft-josefsson-idn-test-vectors');

        return $suite;
    }
}

if (PHPUnit2_MAIN_METHOD == 'Net_IDNA2_AllTests::main') {
    Net_IDNA2_AllTests::main();
}
