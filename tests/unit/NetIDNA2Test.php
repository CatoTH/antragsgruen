<?php

namespace unit;

use Codeception\Specify;
use Net_IDNA2;

class NetIDNA2Test extends TestBase
{
    use Specify;

    /**
     * Test if a complete URL consisting also of port-number etc. will be decoded just fine, test 1
     *
     * @return void
     */
    public function testShouldDecodePortNumbersFragmentsAndUrisCorrectly1()
    {
        $idn = new Net_IDNA2();
        $result = $idn->decode('http://www.xn--ml-6kctd8d6a.org:8080/test.php?arg1=1&arg2=2#fragment');
        $this->assertSame("http://www.\xD0\xB5\xD1\x85\xD0\xB0m\xD1\x80l\xD0\xB5.org:8080/test.php?arg1=1&arg2=2#fragment", $result);
    }

    /**
     * Test if a complete URL consisting also of port-number etc. will be decoded just fine, test 2
     *
     * @return void
     */
    public function testShouldDecodePortNumbersFragmentsAndUrisCorrectly2()
    {
        $idn = new Net_IDNA2();
        $result = $idn->decode('http://xn--tst-qla.example.com:8080/test.php?arg1=1&arg2=2#fragment');
        $this->assertSame("http://täst.example.com:8080/test.php?arg1=1&arg2=2#fragment", $result);
    }

    /**
     * Test encoding of German letter Eszett according to the original standard (IDNA2003)
     *
     * @return void
     */
    public function testEncodingForGermanEszettUsingIDNA2003()
    {
        $idn = new Net_IDNA2();
        // make sure to use 2003-encoding
        $idn->setParams('version', '2003');
        $result = $idn->encode('http://www.straße.example.com/');

        $this->assertSame("http://www.strasse.example.com/", $result);
    }

    /**
     * Test encoding of German letter Eszett according to the "new" standard (IDNA2005/IDNAbis)
     *
     * @return void
     */
    public function testEncodingForGermanEszettUsingIDNA2008()
    {
        $idn = new Net_IDNA2();
        // make sure to use 2008-encoding
        $idn->setParams('version', '2008');
        $result = $idn->encode('http://www.straße.example.com/');
        // switch back for other testcases
        $idn->setParams('version', '2003');

        $this->assertSame("http://www.xn--strae-oqa.example.com/", $result);
    }
}
