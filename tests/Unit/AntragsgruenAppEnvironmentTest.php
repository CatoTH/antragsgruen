<?php

namespace Tests\Unit;

use app\models\settings\AntragsgruenApp;
use Tests\Support\Helper\EnvironmentTestTrait;
use Tests\Support\Helper\TestBase;

/**
 * Integration tests for AntragsgruenApp environment variable configuration
 *
 * These tests verify that the 3-phase configuration loading works correctly:
 * 1. config.json values (highest priority)
 * 2. Environment variables (fallback)
 * 3. Installer defaults (lowest priority)
 */
class AntragsgruenAppEnvironmentTest extends TestBase
{
    use EnvironmentTestTrait;

    protected function setUp(): void
    {
        parent::setUp();
        $this->clearTestEnvVars();
    }

    protected function tearDown(): void
    {
        $this->clearTestEnvVars();
        parent::tearDown();
    }

    public function testConstructorWithEmptyJsonUsesEnvironmentVariables(): void
    {
        $_ENV['DB_HOST'] = 'envhost';
        $_ENV['DB_NAME'] = 'envdb';
        $_ENV['DB_USER'] = 'envuser';
        $_ENV['DB_PASSWORD'] = 'envpass';
        $_ENV['RANDOM_SEED'] = 'env-seed-12345';

        $app = new AntragsgruenApp('{}');

        $this->assertNotNull($app->dbConnection);
        $this->assertStringContainsString('envhost', $app->dbConnection['dsn']);
        $this->assertStringContainsString('envdb', $app->dbConnection['dsn']);
        $this->assertEquals('envuser', $app->dbConnection['username']);
        $this->assertEquals('envpass', $app->dbConnection['password']);
        $this->assertEquals('env-seed-12345', $app->randomSeed);
    }

    public function testConstructorWithJsonTakesPrecedenceOverEnvironment(): void
    {
        $_ENV['DB_HOST'] = 'envhost';
        $_ENV['DB_NAME'] = 'envdb';
        $_ENV['DB_USER'] = 'envuser';
        $_ENV['RANDOM_SEED'] = 'env-seed';

        $json = json_encode([
            'dbConnection' => [
                'dsn' => 'mysql:host=jsonhost;port=3306;dbname=jsondb',
                'username' => 'jsonuser',
                'password' => 'jsonpass',
                'charset' => 'utf8mb4',
                'emulatePrepare' => true,
            ],
            'randomSeed' => 'json-seed',
        ]);

        $app = new AntragsgruenApp($json);

        // JSON values should take precedence
        $this->assertStringContainsString('jsonhost', $app->dbConnection['dsn']);
        $this->assertStringContainsString('jsondb', $app->dbConnection['dsn']);
        $this->assertEquals('jsonuser', $app->dbConnection['username']);
        $this->assertEquals('json-seed', $app->randomSeed);
    }

    public function testConstructorWithPartialJsonMergesWithEnvironment(): void
    {
        $_ENV['REDIS_HOST'] = 'redis.example.com';
        $_ENV['REDIS_PORT'] = '6380';
        $_ENV['MAIL_FROM_EMAIL'] = 'env@example.com';

        $json = json_encode([
            'dbConnection' => [
                'dsn' => 'mysql:host=localhost;port=3306;dbname=testdb',
                'username' => 'testuser',
                'password' => 'testpass',
                'charset' => 'utf8mb4',
                'emulatePrepare' => true,
            ],
        ]);

        $app = new AntragsgruenApp($json);

        // Database from JSON
        $this->assertStringContainsString('localhost', $app->dbConnection['dsn']);

        // Redis from environment
        $this->assertNotNull($app->redis);
        $this->assertEquals('redis.example.com', $app->redis['hostname']);
        $this->assertEquals(6380, $app->redis['port']);

        // Mail from environment
        $this->assertEquals('env@example.com', $app->mailFromEmail);
    }

    public function testEnvironmentVariablesForDatabaseConfiguration(): void
    {
        $_ENV['DB_HOST'] = 'db.example.com';
        $_ENV['DB_NAME'] = 'production_db';
        $_ENV['DB_USER'] = 'prod_user';
        $_ENV['DB_PASSWORD'] = 'prod_pass';
        $_ENV['DB_PORT'] = '3307';
        $_ENV['DB_CHARSET'] = 'utf8';

        $app = new AntragsgruenApp('{}');

        $this->assertNotNull($app->dbConnection);
        $this->assertStringContainsString('db.example.com', $app->dbConnection['dsn']);
        $this->assertStringContainsString('3307', $app->dbConnection['dsn']);
        $this->assertStringContainsString('production_db', $app->dbConnection['dsn']);
        $this->assertEquals('prod_user', $app->dbConnection['username']);
        $this->assertEquals('prod_pass', $app->dbConnection['password']);
        $this->assertEquals('utf8', $app->dbConnection['charset']);
    }

    public function testEnvironmentVariablesForRedisConfiguration(): void
    {
        $_ENV['DB_HOST'] = 'localhost'; // Required for minimal config
        $_ENV['DB_NAME'] = 'testdb';
        $_ENV['DB_USER'] = 'testuser';
        $_ENV['REDIS_HOST'] = 'redis.production.com';
        $_ENV['REDIS_PORT'] = '6380';
        $_ENV['REDIS_DB'] = '5';
        $_ENV['REDIS_PASSWORD'] = 'redis_secret';

        $app = new AntragsgruenApp('{}');

        $this->assertNotNull($app->redis);
        $this->assertEquals('redis.production.com', $app->redis['hostname']);
        $this->assertEquals(6380, $app->redis['port']);
        $this->assertEquals(5, $app->redis['database']);
        $this->assertEquals('redis_secret', $app->redis['password']);
    }

    public function testEnvironmentVariablesForMailServiceDsn(): void
    {
        $_ENV['DB_HOST'] = 'localhost';
        $_ENV['DB_NAME'] = 'testdb';
        $_ENV['DB_USER'] = 'testuser';
        $_ENV['MAILER_DSN'] = 'smtp://mailuser:mailpass@smtp.example.com:587';

        $app = new AntragsgruenApp('{}');

        $this->assertEquals('smtp', $app->mailService['transport']);
        $this->assertEquals('smtp.example.com', $app->mailService['host']);
        $this->assertEquals(587, $app->mailService['port']);
        $this->assertEquals('mailuser', $app->mailService['username']);
        $this->assertEquals('mailpass', $app->mailService['password']);
        $this->assertEquals('tls', $app->mailService['encryption']);
    }

    public function testEnvironmentVariablesForMailServiceIndividual(): void
    {
        $_ENV['DB_HOST'] = 'localhost';
        $_ENV['DB_NAME'] = 'testdb';
        $_ENV['DB_USER'] = 'testuser';
        $_ENV['SMTP_HOST'] = 'mail.example.com';
        $_ENV['SMTP_PORT'] = '465';
        $_ENV['SMTP_USERNAME'] = 'sender@example.com';
        $_ENV['SMTP_PASSWORD'] = 'smtp_secret';
        $_ENV['SMTP_ENCRYPTION'] = 'ssl';

        $app = new AntragsgruenApp('{}');

        $this->assertEquals('smtp', $app->mailService['transport']);
        $this->assertEquals('mail.example.com', $app->mailService['host']);
        $this->assertEquals(465, $app->mailService['port']);
        $this->assertEquals('sender@example.com', $app->mailService['username']);
        $this->assertEquals('smtp_secret', $app->mailService['password']);
        $this->assertEquals('ssl', $app->mailService['encryption']);
    }

    public function testEnvironmentVariablesForApplicationSettings(): void
    {
        $_ENV['DB_HOST'] = 'localhost';
        $_ENV['DB_NAME'] = 'testdb';
        $_ENV['DB_USER'] = 'testuser';
        $_ENV['APP_DOMAIN'] = 'motion.example.org';
        $_ENV['APP_PROTOCOL'] = 'https';
        $_ENV['MULTISITE_MODE'] = 'true';
        $_ENV['BASE_LANGUAGE'] = 'de';
        $_ENV['RANDOM_SEED'] = 'production-seed-12345';
        $_ENV['RESOURCE_BASE'] = '/antragsgruen/';
        $_ENV['MAIL_FROM_EMAIL'] = 'noreply@example.org';
        $_ENV['MAIL_FROM_NAME'] = 'Motion Portal';

        $app = new AntragsgruenApp('{}');

        $this->assertEquals('https://motion.example.org/', $app->domainPlain);
        $this->assertTrue($app->multisiteMode);
        $this->assertEquals('de', $app->baseLanguage);
        $this->assertEquals('production-seed-12345', $app->randomSeed);
        $this->assertEquals('/antragsgruen/', $app->resourceBase);
        $this->assertEquals('noreply@example.org', $app->mailFromEmail);
        $this->assertEquals('Motion Portal', $app->mailFromName);
    }

    public function testEnvironmentVariablesForBooleanFlags(): void
    {
        $_ENV['DB_HOST'] = 'localhost';
        $_ENV['DB_NAME'] = 'testdb';
        $_ENV['DB_USER'] = 'testuser';
        $_ENV['PREPEND_WWW_TO_SUBDOMAIN'] = 'false';
        $_ENV['ALLOW_REGISTRATION'] = 'no';
        $_ENV['CONFIRM_EMAIL_ADDRESSES'] = '0';

        $app = new AntragsgruenApp('{}');

        $this->assertFalse($app->prependWWWToSubdomain);
        $this->assertFalse($app->allowRegistration);
        $this->assertFalse($app->confirmEmailAddresses);
    }

    public function testEnvironmentVariablesForToolPaths(): void
    {
        $_ENV['DB_HOST'] = 'localhost';
        $_ENV['DB_NAME'] = 'testdb';
        $_ENV['DB_USER'] = 'testuser';
        $_ENV['IMAGE_MAGICK_PATH'] = '/custom/bin/convert';
        $_ENV['WEASYPRINT_PATH'] = '/opt/weasyprint';
        $_ENV['LUALATEX_PATH'] = '/usr/local/texlive/bin/lualatex';
        $_ENV['PDFUNITE_PATH'] = '/usr/bin/pdfunite';

        $app = new AntragsgruenApp('{}');

        $this->assertEquals('/custom/bin/convert', $app->imageMagickPath);
        $this->assertEquals('/opt/weasyprint', $app->weasyprintPath);
        $this->assertEquals('/usr/local/texlive/bin/lualatex', $app->lualatexPath);
        $this->assertEquals('/usr/bin/pdfunite', $app->pdfunitePath);
    }

    public function testTablePrefixFromEnvironment(): void
    {
        $_ENV['DB_HOST'] = 'localhost';
        $_ENV['DB_NAME'] = 'testdb';
        $_ENV['DB_USER'] = 'testuser';
        $_ENV['TABLE_PREFIX'] = 'ag_';

        $app = new AntragsgruenApp('{}');

        $this->assertEquals('ag_', $app->tablePrefix);
    }

    public function testDbTablePrefixFromEnvironment(): void
    {
        $_ENV['DB_HOST'] = 'localhost';
        $_ENV['DB_NAME'] = 'testdb';
        $_ENV['DB_USER'] = 'testuser';
        $_ENV['DB_TABLE_PREFIX'] = 'prefix_';

        $app = new AntragsgruenApp('{}');

        $this->assertEquals('prefix_', $app->tablePrefix);
    }

    public function testTablePrefixPrecedence(): void
    {
        $_ENV['DB_HOST'] = 'localhost';
        $_ENV['DB_NAME'] = 'testdb';
        $_ENV['DB_USER'] = 'testuser';
        $_ENV['TABLE_PREFIX'] = 'first_';
        $_ENV['DB_TABLE_PREFIX'] = 'second_';

        $app = new AntragsgruenApp('{}');

        // TABLE_PREFIX should take precedence over DB_TABLE_PREFIX
        $this->assertEquals('first_', $app->tablePrefix);
    }

    public function testMinimalConfigWithoutDatabaseStillWorks(): void
    {
        // No environment variables set
        $app = new AntragsgruenApp('{}');

        // Should not throw exception
        $this->assertInstanceOf(AntragsgruenApp::class, $app);

        // Should have defaults
        $this->assertEquals('en', $app->baseLanguage);
        $this->assertTrue($app->allowRegistration);
    }

    public function testBackwardsCompatibilityWithExistingConfigJson(): void
    {
        // Simulate existing config.json format
        $json = json_encode([
            'dbConnection' => [
                'dsn' => 'mysql:host=oldhost;port=3306;dbname=olddb',
                'username' => 'olduser',
                'password' => 'oldpass',
                'charset' => 'utf8mb4',
                'emulatePrepare' => true,
            ],
            'randomSeed' => 'old-seed-value',
            'prettyUrl' => true,
            'multisiteMode' => false,
            'baseLanguage' => 'en',
            'mailService' => [
                'transport' => 'sendmail',
            ],
        ]);

        $_ENV['DB_HOST'] = 'newhost'; // Should be ignored
        $_ENV['RANDOM_SEED'] = 'new-seed'; // Should be ignored

        $app = new AntragsgruenApp($json);

        // All values should come from JSON, not environment
        $this->assertStringContainsString('oldhost', $app->dbConnection['dsn']);
        $this->assertEquals('olduser', $app->dbConnection['username']);
        $this->assertEquals('old-seed-value', $app->randomSeed);
        $this->assertEquals('sendmail', $app->mailService['transport']);
    }

    public function testDomainPlainDefaultNotOverriddenByEnvironment(): void
    {
        // Set a non-default domainPlain via JSON
        $json = json_encode([
            'domainPlain' => 'https://custom.example.com/',
        ]);

        $_ENV['APP_DOMAIN'] = 'env.example.com';

        $app = new AntragsgruenApp($json);

        // JSON value should be preserved
        $this->assertEquals('https://custom.example.com/', $app->domainPlain);
    }

    public function testEmptyDatabasePasswordIsValid(): void
    {
        $_ENV['DB_HOST'] = 'localhost';
        $_ENV['DB_NAME'] = 'testdb';
        $_ENV['DB_USER'] = 'root';
        $_ENV['DB_PASSWORD'] = '';

        $app = new AntragsgruenApp('{}');

        $this->assertNotNull($app->dbConnection);
        $this->assertEquals('', $app->dbConnection['password']);
    }

    public function testCompleteContainerizedDeploymentConfiguration(): void
    {
        // Simulate a complete containerized deployment
        $_ENV['DB_HOST'] = 'mariadb';
        $_ENV['DB_NAME'] = 'antragsgruen';
        $_ENV['DB_USER'] = 'antragsgruen';
        $_ENV['DB_PASSWORD'] = 'secure_password';
        $_ENV['DB_PORT'] = '3306';
        $_ENV['TABLE_PREFIX'] = '';
        $_ENV['REDIS_HOST'] = 'redis';
        $_ENV['REDIS_PORT'] = '6379';
        $_ENV['MAILER_DSN'] = 'smtp://noreply:smtp_pass@smtp.example.com:587';
        $_ENV['APP_DOMAIN'] = 'motion.tools';
        $_ENV['APP_PROTOCOL'] = 'https';
        $_ENV['BASE_LANGUAGE'] = 'en';
        $_ENV['RANDOM_SEED'] = 'container-generated-secure-seed';
        $_ENV['MAIL_FROM_EMAIL'] = 'noreply@motion.tools';
        $_ENV['MAIL_FROM_NAME'] = 'Motion Tools';
        $_ENV['ALLOW_REGISTRATION'] = 'true';
        $_ENV['CONFIRM_EMAIL_ADDRESSES'] = 'true';

        $app = new AntragsgruenApp('{}');

        // Verify complete configuration
        $this->assertStringContainsString('mariadb', $app->dbConnection['dsn']);
        $this->assertEquals('antragsgruen', $app->dbConnection['username']);
        $this->assertEquals('redis', $app->redis['hostname']);
        $this->assertEquals('smtp.example.com', $app->mailService['host']);
        $this->assertEquals('https://motion.tools/', $app->domainPlain);
        $this->assertEquals('container-generated-secure-seed', $app->randomSeed);
        $this->assertTrue($app->allowRegistration);
        $this->assertTrue($app->confirmEmailAddresses);
    }
}
