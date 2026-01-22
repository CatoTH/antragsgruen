<?php

namespace Tests\Unit;

use app\models\settings\EnvironmentConfigLoader;
use Tests\Support\Helper\TestBase;

class EnvironmentConfigLoaderTest extends TestBase
{
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

    private function clearTestEnvVars(): void
    {
        $vars = ['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASSWORD', 'REDIS_HOST', 
                 'SMTP_HOST', 'MAILER_DSN', 'APP_DOMAIN', 'RANDOM_SEED'];
        foreach ($vars as $var) {
            unset($_ENV[$var]);
            putenv($var);
        }
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
}
