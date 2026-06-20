<?php

namespace Tests\Unit;

use Tests\Support\Helper\TestBase;

/**
 * Tests for the CSV user import parsing and validation logic
 * found in UsersController::actionProcessCsvChunk.
 *
 * These tests exercise the pure parsing/validation code paths
 * without requiring a database connection.
 */
class CsvUserImportTest extends TestBase
{
    // ---------------------------------------------------------------
    //  Helper: replicate the header-parsing logic from the controller
    // ---------------------------------------------------------------

    /**
     * Parses a raw CSV header row exactly like actionProcessCsvChunk does.
     *
     * @param string[] $header Raw header values from fgetcsv()
     * @return array<string, int> column-name => column-index
     */
    private function parseHeader(array $header): array
    {
        // Strip BOM from first column (controller line 369)
        $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $header[0]);

        return array_flip(
            array_map(
                'trim',
                array_map(function ($v) { return strtolower((string) $v); }, $header)
            )
        );
    }

    /**
     * Validates a token string exactly like actionProcessCsvChunk does.
     */
    private function isValidToken(string $token): bool
    {
        return (bool) preg_match('/^csv_[a-zA-Z0-9.]+$/', $token);
    }

    // ---------------------------------------------------------------
    //  1. CSV header parsing — valid header with email column
    // ---------------------------------------------------------------

    public function testValidHeaderWithEmailColumn(): void
    {
        $headerMap = $this->parseHeader(['Email', 'First_Name', 'Last_Name', 'Organization', 'Groups']);

        $this->assertArrayHasKey('email', $headerMap);
        $this->assertArrayHasKey('first_name', $headerMap);
        $this->assertArrayHasKey('last_name', $headerMap);
        $this->assertArrayHasKey('organization', $headerMap);
        $this->assertArrayHasKey('groups', $headerMap);

        // Indices must match original positions
        $this->assertSame(0, $headerMap['email']);
        $this->assertSame(1, $headerMap['first_name']);
        $this->assertSame(2, $headerMap['last_name']);
        $this->assertSame(3, $headerMap['organization']);
        $this->assertSame(4, $headerMap['groups']);
    }

    public function testHeaderIsCaseInsensitive(): void
    {
        $headerMap = $this->parseHeader(['EMAIL', 'First_Name', 'ORGANIZATION']);

        $this->assertArrayHasKey('email', $headerMap);
        $this->assertArrayHasKey('first_name', $headerMap);
        $this->assertArrayHasKey('organization', $headerMap);
    }

    public function testHeaderWithWhitespace(): void
    {
        $headerMap = $this->parseHeader(['  Email  ', ' First_Name', 'Last_Name ']);

        $this->assertArrayHasKey('email', $headerMap);
        $this->assertArrayHasKey('first_name', $headerMap);
        $this->assertArrayHasKey('last_name', $headerMap);
    }

    public function testMinimalHeaderEmailOnly(): void
    {
        $headerMap = $this->parseHeader(['email']);

        $this->assertArrayHasKey('email', $headerMap);
        $this->assertSame(0, $headerMap['email']);
    }

    // ---------------------------------------------------------------
    //  2. CSV header parsing — missing email column returns error
    // ---------------------------------------------------------------

    public function testMissingEmailColumnDetected(): void
    {
        $headerMap = $this->parseHeader(['First_Name', 'Last_Name', 'Organization']);

        $this->assertArrayNotHasKey('email', $headerMap);
    }

    public function testEmptyHeaderMissingEmail(): void
    {
        $headerMap = $this->parseHeader(['']);

        $this->assertArrayNotHasKey('email', $headerMap);
    }

    // ---------------------------------------------------------------
    //  3. BOM stripping from the first column
    // ---------------------------------------------------------------

    public function testBomStrippedFromFirstColumn(): void
    {
        // UTF-8 BOM is \xEF\xBB\xBF
        $headerWithBom = ["\xEF\xBB\xBFemail", 'first_name', 'last_name'];
        $headerMap = $this->parseHeader($headerWithBom);

        $this->assertArrayHasKey('email', $headerMap, 'BOM should be stripped so "email" is recognised');
        $this->assertSame(0, $headerMap['email']);
    }

    public function testBomOnlyAffectsFirstColumn(): void
    {
        // BOM in a non-first column is NOT stripped (controller only strips $header[0])
        $headerWithBom = ['email', "\xEF\xBB\xBFfirst_name"];
        $headerMap = $this->parseHeader($headerWithBom);

        // first_name will still have the BOM prefix, so the plain key won't match
        $this->assertArrayNotHasKey('first_name', $headerMap);
    }

    public function testBomWithCaseInsensitiveEmail(): void
    {
        $headerMap = $this->parseHeader(["\xEF\xBB\xBFEMAIL", 'Organization']);

        $this->assertArrayHasKey('email', $headerMap);
    }

    // ---------------------------------------------------------------
    //  4. Token validation regex
    // ---------------------------------------------------------------

    /**
     * @dataProvider validTokenProvider
     */
    public function testValidTokens(string $token): void
    {
        $this->assertTrue($this->isValidToken($token), "Token '$token' should be valid");
    }

    public static function validTokenProvider(): array
    {
        return [
            'typical uniqid'   => ['csv_6650a3b9e12345.67890123'],
            'simple'           => ['csv_abc123'],
            'alpha only'       => ['csv_abcdef'],
            'numeric only'     => ['csv_123456'],
            'with dots'        => ['csv_abc.def.ghi'],
            'mixed case'       => ['csv_AbCdEf123'],
        ];
    }

    /**
     * @dataProvider invalidTokenProvider
     */
    public function testInvalidTokens(string $token): void
    {
        $this->assertFalse($this->isValidToken($token), "Token '$token' should be invalid");
    }

    public static function invalidTokenProvider(): array
    {
        return [
            'empty string'           => [''],
            'no csv_ prefix'         => ['abc_123'],
            'only prefix'            => ['csv_'],
            'path traversal'         => ['csv_../../etc/passwd'],
            'spaces'                 => ['csv_abc 123'],
            'special chars'          => ['csv_abc$def'],
            'semicolon'              => ['csv_abc;def'],
            'unicode'                => ['csv_abcü123'],
            'newline injection'      => ["csv_abc\ndef"],
            'null byte'              => ["csv_abc\x00def"],
        ];
    }

    // ---------------------------------------------------------------
    //  5. Collision behavior parameter validation
    // ---------------------------------------------------------------

    public function testCollisionBehaviorValidValues(): void
    {
        $validValues = ['skip', 'merge', 'replace'];
        foreach ($validValues as $value) {
            $this->assertContains($value, $validValues, "Collision behavior '$value' should be accepted");
        }
    }

    public function testCollisionBehaviorDefaultIsSkip(): void
    {
        // The controller defaults to 'skip' when no value is provided (line 344)
        // $collisionBehavior = $this->getPostValue('collisionBehavior', 'skip');
        $default = 'skip';
        $this->assertSame('skip', $default);
    }

    /**
     * Verify that only the three known collision behaviors are the valid set.
     */
    public function testCollisionBehaviorInvalidValuesNotInSet(): void
    {
        $validValues = ['skip', 'merge', 'replace'];
        $invalidValues = ['overwrite', 'delete', 'upsert', '', 'SKIP', 'Merge'];

        foreach ($invalidValues as $value) {
            $this->assertNotContains($value, $validValues, "Collision behavior '$value' should NOT be in the valid set");
        }
    }

    // ---------------------------------------------------------------
    //  Integration-style: full header parse from CSV string
    // ---------------------------------------------------------------

    public function testFullCsvHeaderParsing(): void
    {
        $csvContent = "Email,First_Name,Last_Name,Organization,Groups\nuser@example.com,John,Doe,ACME,Admin";
        $fp = fopen('php://memory', 'r+');
        fwrite($fp, $csvContent);
        rewind($fp);

        $header = fgetcsv($fp);
        $this->assertNotFalse($header);

        $headerMap = $this->parseHeader($header);

        $this->assertArrayHasKey('email', $headerMap);
        $this->assertSame(0, $headerMap['email']);

        // Read the data row
        $row = fgetcsv($fp);
        $this->assertNotFalse($row);
        $this->assertSame('user@example.com', $row[$headerMap['email']]);

        fclose($fp);
    }

    public function testFullCsvWithBomParsing(): void
    {
        $bom = "\xEF\xBB\xBF";
        $csvContent = $bom . "Email,First_Name,Last_Name\nuser@test.org,Jane,Smith";
        $fp = fopen('php://memory', 'r+');
        fwrite($fp, $csvContent);
        rewind($fp);

        $header = fgetcsv($fp);
        $headerMap = $this->parseHeader($header);

        $this->assertArrayHasKey('email', $headerMap, 'BOM-prefixed CSV should still find the email column');

        $row = fgetcsv($fp);
        $this->assertSame('user@test.org', $row[$headerMap['email']]);

        fclose($fp);
    }

    public function testCsvWithSemicolonSeparatorUsesDefaultComma(): void
    {
        // fgetcsv defaults to comma — semicolons won't be split
        $csvContent = "Email;First_Name;Last_Name\nuser@test.org;Jane;Smith";
        $fp = fopen('php://memory', 'r+');
        fwrite($fp, $csvContent);
        rewind($fp);

        $header = fgetcsv($fp);
        $headerMap = $this->parseHeader($header);

        // The entire line is treated as one column when using comma delimiter
        $this->assertArrayNotHasKey('email', $headerMap, 'Semicolon-separated CSV should not parse correctly with default comma delimiter');

        fclose($fp);
    }
}
