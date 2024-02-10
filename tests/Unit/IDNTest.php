<?php

namespace Tests\Unit;

use Tests\Support\Helper\TestBase;

class IDNTest extends TestBase
{
    /**
     * Test decoding domain without a special character.
     */
    public function testShouldDecodeWithoutSpecialCharacter(): void
    {
        $result = idn_to_utf8('www.xn--ml-6kctd8d6a.org');
        $this->assertSame("www.\xD0\xB5\xD1\x85\xD0\xB0m\xD1\x80l\xD0\xB5.org", $result);
    }

    /**
     * Test decoding domain with a special character.
     */
    public function testShouldDecodeWithSpecialCharacter(): void
    {
        $result = idn_to_utf8('xn--tst-qla.example.com');
        $this->assertSame("täst.example.com", $result);
    }
}
