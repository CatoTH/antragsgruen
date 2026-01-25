<?php

namespace Tests\Unit;

use app\models\settings\EnvironmentConfigLoader;
use Tests\Support\Helper\EnvironmentTestTrait;
use Tests\Support\Helper\TestBase;

class EnvironmentConfigLoaderTest extends TestBase
{
    use EnvironmentTestTrait;

    protected function setUp(): void
    {
        parent::setUp();
        // Clear environment to avoid test pollution
        $this->clearTestEnvVars();
    }

    protected function tearDown(): void
    {
        $this->clearTestEnvVars();
        parent::tearDown();
    }

    public function testGetDatabaseConfigWithAllVars(): void
    {
        $_ENV['DB_HOST'] = 'testhost';
        $_ENV['DB_NAME'] = 'testdb';
        $_ENV['DB_USER'] = 'testuser';
        $_ENV['DB_PASSWORD'] = 'testpass';

        $config = EnvironmentConfigLoader::getDatabaseConfig();

        $this->assertNotNull($config);
        $this->assertIsArray($config);
        $this->assertStringContainsString('testhost', $config['dsn']);
        $this->assertStringContainsString('testdb', $config['dsn']);
        $this->assertEquals('testuser', $config['username']);
        $this->assertEquals('testpass', $config['password']);
    }

    public function testGetDatabaseConfigMissingRequired(): void
    {
        $_ENV['DB_HOST'] = 'testhost';
        // Missing DB_NAME and DB_USER

        $config = EnvironmentConfigLoader::getDatabaseConfig();

        $this->assertNull($config);
    }

    public function testGetRedisConfigEnabled(): void
    {
        $_ENV['REDIS_HOST'] = 'redis.example.com';
        $_ENV['REDIS_PORT'] = '6380';

        $config = EnvironmentConfigLoader::getRedisConfig();

        $this->assertNotNull($config);
        $this->assertEquals('redis.example.com', $config['hostname']);
        $this->assertEquals(6380, $config['port']);
    }

    public function testGetRedisConfigDisabled(): void
    {
        // No REDIS_HOST set
        $config = EnvironmentConfigLoader::getRedisConfig();

        $this->assertNull($config);
    }

    public function testGetMailServiceConfigDsn(): void
    {
        $_ENV['MAILER_DSN'] = 'smtp://user:pass@smtp.example.com:587';

        $config = EnvironmentConfigLoader::getMailServiceConfig();

        $this->assertNotNull($config);
        $this->assertEquals('smtp.example.com', $config['host']);
        $this->assertEquals(587, $config['port']);
        $this->assertEquals('user', $config['username']);
        $this->assertEquals('pass', $config['password']);
    }

    public function testGetMailServiceConfigIndividual(): void
    {
        $_ENV['SMTP_HOST'] = 'mail.example.com';
        $_ENV['SMTP_PORT'] = '465';
        $_ENV['SMTP_USERNAME'] = 'sender';

        $config = EnvironmentConfigLoader::getMailServiceConfig();

        $this->assertNotNull($config);
        $this->assertEquals('mail.example.com', $config['host']);
        $this->assertEquals(465, $config['port']);
        $this->assertEquals('sender', $config['username']);
    }

    public function testGetApplicationConfig(): void
    {
        $_ENV['APP_DOMAIN'] = 'motion.tools';
        $_ENV['APP_PROTOCOL'] = 'https';
        $_ENV['BASE_LANGUAGE'] = 'de';
        $_ENV['RANDOM_SEED'] = 'test-seed-12345';

        $config = EnvironmentConfigLoader::getApplicationConfig();

        $this->assertIsArray($config);
        $this->assertEquals('https://motion.tools/', $config['domainPlain']);
        $this->assertEquals('de', $config['baseLanguage']);
        $this->assertEquals('test-seed-12345', $config['randomSeed']);
    }

    public function testGetDatabaseConfigWithTablePrefix(): void
    {
        $_ENV['DB_HOST'] = 'localhost';
        $_ENV['DB_NAME'] = 'testdb';
        $_ENV['DB_USER'] = 'testuser';
        $_ENV['TABLE_PREFIX'] = 'ag_';

        $dbConfig = EnvironmentConfigLoader::getDatabaseConfig();
        $appConfig = EnvironmentConfigLoader::getApplicationConfig();

        $this->assertNotNull($dbConfig);
        $this->assertArrayHasKey('tablePrefix', $appConfig);
        $this->assertEquals('ag_', $appConfig['tablePrefix']);
    }

    public function testGetDatabaseConfigWithDbTablePrefix(): void
    {
        $_ENV['DB_HOST'] = 'localhost';
        $_ENV['DB_NAME'] = 'testdb';
        $_ENV['DB_USER'] = 'testuser';
        $_ENV['DB_TABLE_PREFIX'] = 'prefix_';

        $appConfig = EnvironmentConfigLoader::getApplicationConfig();

        $this->assertArrayHasKey('tablePrefix', $appConfig);
        $this->assertEquals('prefix_', $appConfig['tablePrefix']);
    }

    public function testTablePrefixPrecedence(): void
    {
        $_ENV['TABLE_PREFIX'] = 'first_';
        $_ENV['DB_TABLE_PREFIX'] = 'second_';

        $config = EnvironmentConfigLoader::getApplicationConfig();

        // TABLE_PREFIX should take precedence
        $this->assertEquals('first_', $config['tablePrefix']);
    }

    public function testGetDatabaseConfigWithCustomPort(): void
    {
        $_ENV['DB_HOST'] = 'dbhost';
        $_ENV['DB_NAME'] = 'dbname';
        $_ENV['DB_USER'] = 'dbuser';
        $_ENV['DB_PORT'] = '3307';

        $config = EnvironmentConfigLoader::getDatabaseConfig();

        $this->assertStringContainsString('port=3307', $config['dsn']);
    }

    public function testGetDatabaseConfigWithCustomCharset(): void
    {
        $_ENV['DB_HOST'] = 'dbhost';
        $_ENV['DB_NAME'] = 'dbname';
        $_ENV['DB_USER'] = 'dbuser';
        $_ENV['DB_CHARSET'] = 'utf8';

        $config = EnvironmentConfigLoader::getDatabaseConfig();

        $this->assertEquals('utf8', $config['charset']);
    }

    public function testGetRedisConfigWithPassword(): void
    {
        $_ENV['REDIS_HOST'] = 'redis.example.com';
        $_ENV['REDIS_PASSWORD'] = 'secret123';
        $_ENV['REDIS_DB'] = '2';

        $config = EnvironmentConfigLoader::getRedisConfig();

        $this->assertNotNull($config);
        $this->assertEquals('secret123', $config['password']);
        $this->assertEquals(2, $config['database']);
    }

    public function testGetMailServiceConfigDsnWithSmtps(): void
    {
        $_ENV['MAILER_DSN'] = 'smtps://user:pass@smtp.example.com:465';

        $config = EnvironmentConfigLoader::getMailServiceConfig();

        $this->assertNotNull($config);
        $this->assertEquals('smtp.example.com', $config['host']);
        $this->assertEquals(465, $config['port']);
        $this->assertEquals('ssl', $config['encryption']);
    }

    public function testGetMailServiceConfigDsnNoAuth(): void
    {
        $_ENV['MAILER_DSN'] = 'smtp://localhost:25';

        $config = EnvironmentConfigLoader::getMailServiceConfig();

        $this->assertNotNull($config);
        $this->assertEquals('localhost', $config['host']);
        $this->assertEquals(25, $config['port']);
        $this->assertNull($config['username']);
        $this->assertNull($config['password']);
    }

    public function testGetMailServiceConfigDsnUrlEncoded(): void
    {
        $_ENV['MAILER_DSN'] = 'smtp://user%40example.com:p%40ssw0rd@smtp.example.com:587';

        $config = EnvironmentConfigLoader::getMailServiceConfig();

        $this->assertEquals('user@example.com', $config['username']);
        $this->assertEquals('p@ssw0rd', $config['password']);
    }

    public function testGetMailServiceConfigIndividualWithSsl(): void
    {
        $_ENV['SMTP_HOST'] = 'mail.example.com';
        $_ENV['SMTP_PORT'] = '465';
        $_ENV['SMTP_ENCRYPTION'] = 'ssl';

        $config = EnvironmentConfigLoader::getMailServiceConfig();

        $this->assertEquals('ssl', $config['encryption']);
        $this->assertEquals(465, $config['port']);
    }

    public function testGetApplicationConfigWithAllSettings(): void
    {
        $_ENV['APP_DOMAIN'] = 'example.org';
        $_ENV['APP_PROTOCOL'] = 'http';
        $_ENV['MULTISITE_MODE'] = 'true';
        $_ENV['BASE_LANGUAGE'] = 'fr';
        $_ENV['RANDOM_SEED'] = 'random123';
        $_ENV['RESOURCE_BASE'] = '/app/';
        $_ENV['MAIL_FROM_EMAIL'] = 'noreply@example.org';
        $_ENV['MAIL_FROM_NAME'] = 'Example System';
        $_ENV['PREPEND_WWW_TO_SUBDOMAIN'] = 'false';
        $_ENV['ALLOW_REGISTRATION'] = 'yes';
        $_ENV['CONFIRM_EMAIL_ADDRESSES'] = 'no';

        $config = EnvironmentConfigLoader::getApplicationConfig();

        $this->assertEquals('http://example.org/', $config['domainPlain']);
        $this->assertTrue($config['multisiteMode']);
        $this->assertEquals('fr', $config['baseLanguage']);
        $this->assertEquals('random123', $config['randomSeed']);
        $this->assertEquals('/app/', $config['resourceBase']);
        $this->assertEquals('noreply@example.org', $config['mailFromEmail']);
        $this->assertEquals('Example System', $config['mailFromName']);
        $this->assertFalse($config['prependWWWToSubdomain']);
        $this->assertTrue($config['allowRegistration']);
        $this->assertFalse($config['confirmEmailAddresses']);
    }

    public function testGetApplicationConfigWithToolPaths(): void
    {
        $_ENV['IMAGE_MAGICK_PATH'] = '/usr/local/bin/convert';
        $_ENV['WEASYPRINT_PATH'] = '/opt/weasyprint/bin/weasyprint';
        $_ENV['LUALATEX_PATH'] = '/usr/bin/lualatex';
        $_ENV['PDFUNITE_PATH'] = '/usr/bin/pdfunite';

        $config = EnvironmentConfigLoader::getApplicationConfig();

        $this->assertEquals('/usr/local/bin/convert', $config['imageMagickPath']);
        $this->assertEquals('/opt/weasyprint/bin/weasyprint', $config['weasyprintPath']);
        $this->assertEquals('/usr/bin/lualatex', $config['lualatexPath']);
        $this->assertEquals('/usr/bin/pdfunite', $config['pdfunitePath']);
    }

    public function testBooleanValueParsing(): void
    {
        // Test various boolean representations
        $testCases = [
            ['true', true],
            ['TRUE', true],
            ['True', true],
            ['1', true],
            ['yes', true],
            ['YES', true],
            ['on', true],
            ['ON', true],
            ['false', false],
            ['FALSE', false],
            ['False', false],
            ['0', false],
            ['no', false],
            ['NO', false],
            ['off', false],
            ['OFF', false],
        ];

        foreach ($testCases as [$value, $expected]) {
            $_ENV['MULTISITE_MODE'] = $value;
            $config = EnvironmentConfigLoader::getApplicationConfig();
            $this->assertEquals($expected, $config['multisiteMode'],
                "Failed asserting that '{$value}' parses to " . ($expected ? 'true' : 'false'));
            unset($_ENV['MULTISITE_MODE']);
        }
    }

    public function testGetApplicationConfigDomainWithTrailingSlashNormalization(): void
    {
        $_ENV['APP_DOMAIN'] = 'example.com/';

        $config = EnvironmentConfigLoader::getApplicationConfig();

        // Should normalize to single trailing slash
        $this->assertEquals('https://example.com/', $config['domainPlain']);
    }

    public function testGetApplicationConfigEmptyReturnsEmptyArray(): void
    {
        // No environment variables set
        $config = EnvironmentConfigLoader::getApplicationConfig();

        $this->assertIsArray($config);
        $this->assertEmpty($config);
    }

    public function testMailServiceConfigDsnTakesPrecedenceOverIndividual(): void
    {
        $_ENV['MAILER_DSN'] = 'smtp://dsn-user@dsn.example.com:587';
        $_ENV['SMTP_HOST'] = 'individual.example.com';
        $_ENV['SMTP_USERNAME'] = 'individual-user';

        $config = EnvironmentConfigLoader::getMailServiceConfig();

        // DSN should take precedence
        $this->assertEquals('dsn.example.com', $config['host']);
        $this->assertEquals('dsn-user', $config['username']);
    }
}
