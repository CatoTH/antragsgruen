<?php
namespace Tests\Unit;

use app\components\Tools;
use Tests\Support\Helper\TestBase;

class DateFunctionsTest extends TestBase
{
    /**
     */
    public function testBootstrapDatesql(): void
    {
        $ret = Tools::dateBootstrapdate2sql('2.1.2016', 'de');
        $this->assertEquals('2016-01-02', $ret);
        $ret = Tools::dateBootstrapdate2sql('02.01.2016', 'de');
        $this->assertEquals('2016-01-02', $ret);

        $ret = Tools::dateBootstrapdate2sql('3/4/2016', 'en');
        $this->assertEquals('2016-03-04', $ret);
        $ret = Tools::dateBootstrapdate2sql('02/01/2016', 'en');
        $this->assertEquals('2016-02-01', $ret);
    }

    /**
     */
    public function testBootstrapTime2sql(): void
    {
        $ret = Tools::dateBootstraptime2sql('2.1.2016 11:05', 'de');
        $this->assertEquals('2016-01-02 11:05:00', $ret);
        $ret = Tools::dateBootstraptime2sql('02.1.2016 11:5', 'de');
        $this->assertEquals('2016-01-02 11:05:00', $ret);

        $ret = Tools::dateBootstraptime2sql('3/4/2016 11:05', 'en');
        $this->assertEquals('2016-03-04 11:05:00', $ret);
        $ret = Tools::dateBootstraptime2sql('3/4/2016 20:05', 'en');
        $this->assertEquals('2016-03-04 20:05:00', $ret);

        $ret = Tools::dateBootstraptime2sql('3/4/2016 11:05 AM', 'en');
        $this->assertEquals('2016-03-04 11:05:00', $ret);
        $ret = Tools::dateBootstraptime2sql('3/4/2016 11:05 PM', 'en');
        $this->assertEquals('2016-03-04 23:05:00', $ret);
    }
}
