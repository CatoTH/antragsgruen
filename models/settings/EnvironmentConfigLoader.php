<?php

namespace app\models\settings;

/**
 * Loads configuration from environment variables
 *
 * This class provides a centralized, testable way to load application configuration
 * from environment variables. It's designed to complement (not replace) the existing
 * config.json approach, providing a fallback mechanism for containerized deployments.
 *
 * @see docs/environment-variables.md
 */
class EnvironmentConfigLoader
{
    /**
     * Get database configuration from environment variables
     *
     * Required environment variables:
     * - DB_HOST: Database hostname
     * - DB_NAME: Database name
     * - DB_USER: Database username
     * - DB_PASSWORD: Database password (can be empty for local dev)
     *
     * Optional environment variables:
     * - DB_PORT: Database port (default: 3306)
     * - DB_CHARSET: Character set (default: utf8mb4)
     *
     * Note: Table prefix is configured via TABLE_PREFIX or DB_TABLE_PREFIX
     * in getApplicationConfig(), not here.
     *
     * @return array|null Database config array compatible with Yii2, or null if required vars missing
     */
    public static function getDatabaseConfig(): ?array
    {
        $host = self::getEnv('DB_HOST');
        $name = self::getEnv('DB_NAME');
        $user = self::getEnv('DB_USER');

        // All three are required (check for null/false, not falsy values)
        if ($host === null || $name === null || $user === null) {
            return null;
        }

        $port = self::getEnv('DB_PORT', '3306');
        $password = self::getEnv('DB_PASSWORD', '');
        $charset = self::getEnv('DB_CHARSET', 'utf8mb4');

        return [
            'class' => 'yii\db\Connection',
            'dsn' => "mysql:host={$host};port={$port};dbname={$name}",
            'username' => $user,
            'password' => $password,
            'charset' => $charset,
            'emulatePrepare' => true,
        ];
    }

    /**
     * Get Redis configuration from environment variables
     *
     * Required environment variables:
     * - REDIS_HOST: Redis hostname
     *
     * Optional environment variables:
     * - REDIS_PORT: Redis port (default: 6379)
     * - REDIS_DB: Redis database number (default: 0)
     * - REDIS_PASSWORD: Redis password (default: null)
     *
     * @return array|null Redis config array, or null if REDIS_HOST not set
     */
    public static function getRedisConfig(): ?array
    {
        $host = self::getEnv('REDIS_HOST');

        if (!$host) {
            return null;
        }

        return [
            'hostname' => $host,
            'port' => (int)self::getEnv('REDIS_PORT', '6379'),
            'database' => (int)self::getEnv('REDIS_DB', '0'),
            'password' => self::getEnv('REDIS_PASSWORD'),
        ];
    }

    /**
     * Get mail service configuration from environment variables
     *
     * Supports two configuration formats:
     *
     * Format 1 - Symfony Mailer DSN (recommended):
     * - MAILER_DSN: Full DSN like "smtp://user:pass@host:587"
     *
     * Format 2 - Individual SMTP settings:
     * - SMTP_HOST: SMTP server hostname (required for this format)
     * - SMTP_PORT: SMTP port (default: 587)
     * - SMTP_USERNAME: SMTP username (optional)
     * - SMTP_PASSWORD: SMTP password (optional)
     * - SMTP_ENCRYPTION: Encryption type: tls, ssl, or empty (default: tls)
     *
     * @return array|null Mail service config array, or null if no mail config found
     */
    public static function getMailServiceConfig(): ?array
    {
        if (self::getEnv('MAILER_DISABLED', false)) {
            return [
                'transport' => 'none',
            ];
        }

        // Format 1: Modern DSN format
        $dsn = self::getEnv('MAILER_DSN');
        if ($dsn) {
            return self::parseMailerDsn($dsn);
        }

        // Format 2: Legacy individual settings
        $host = self::getEnv('SMTP_HOST');
        if (!$host) {
            return null;
        }

        return [
            'transport' => 'smtp',
            'host' => $host,
            'port' => (int)self::getEnv('SMTP_PORT', '587'),
            'username' => self::getEnv('SMTP_USERNAME'),
            'password' => self::getEnv('SMTP_PASSWORD'),
            'encryption' => self::getEnv('SMTP_ENCRYPTION', 'tls'),
        ];
    }

    /**
     * Parse Symfony Mailer DSN into config array
     *
     * Example DSN formats:
     * - smtp://user:pass@smtp.example.com:587
     * - smtps://user:pass@smtp.example.com:465
     * - smtp://smtp.example.com (no auth)
     *
     * @param string $dsn Mailer DSN
     * @return array Mail service config array
     */
    private static function parseMailerDsn(string $dsn): array
    {
        $parts = parse_url($dsn);

        $config = [
            'transport' => 'smtp',
            'host' => $parts['host'] ?? 'localhost',
            'port' => $parts['port'] ?? 587,
            'username' => isset($parts['user']) ? rawurldecode($parts['user']) : null,
            'password' => isset($parts['pass']) ? rawurldecode($parts['pass']) : null,
        ];

        // Determine encryption from scheme
        $scheme = $parts['scheme'] ?? 'smtp';
        if ($scheme === 'smtps') {
            $config['encryption'] = 'ssl';
        } elseif ($config['port'] === 465) {
            $config['encryption'] = 'ssl';
        } elseif ($config['port'] === 587) {
            $config['encryption'] = 'tls';
        } else {
            // Port 25 or other ports - no encryption
            $config['encryption'] = null;
        }

        return $config;
    }

    /**
     * Get application-level configuration from environment variables
     *
     * Supported environment variables:
     * - APP_DOMAIN: Domain name (e.g., motion.tools)
     * - APP_PROTOCOL: Protocol (http or https, default: https)
     * - MULTISITE_MODE: Enable multisite mode (true/false/1/0/yes/no)
     * - BASE_LANGUAGE: Base language code (en, de, fr, etc.)
     * - RANDOM_SEED: Random seed for security (required in production!)
     * - RESOURCE_BASE: Resource base path (default: /)
     * - TABLE_PREFIX or DB_TABLE_PREFIX: Database table prefix (default: empty)
     * - MAIL_FROM_EMAIL: Default "from" email address
     * - MAIL_FROM_NAME: Default "from" name
     * - PREPEND_WWW_TO_SUBDOMAIN: Prepend www to subdomain (true/false)
     * - ALLOW_REGISTRATION: Allow user registration (true/false)
     * - CONFIRM_EMAIL_ADDRESSES: Require email confirmation (true/false)
     *
     * @return array Associative array of config key => value pairs
     */
    public static function getApplicationConfig(): array
    {
        $config = [];

        // Domain configuration
        $domain = self::getEnv('APP_DOMAIN');
        if ($domain) {
            $protocol = self::getEnv('APP_PROTOCOL', 'https');
            $config['domainPlain'] = rtrim($protocol . '://' . $domain, '/') . '/';
        }

        // Table prefix (support both TABLE_PREFIX and DB_TABLE_PREFIX)
        $tablePrefix = self::getEnv('TABLE_PREFIX') ?? self::getEnv('DB_TABLE_PREFIX');
        if ($tablePrefix !== null) {
            $config['tablePrefix'] = $tablePrefix;
        }

        // Multisite mode
        if (self::hasEnv('MULTISITE_MODE')) {
            $config['multisiteMode'] = self::getBoolEnv('MULTISITE_MODE', false);
        }

        if (self::hasEnv('SITE_SUBDOMAIN')) {
            $config['siteSubdomain'] = self::getEnv('SITE_SUBDOMAIN', null);
        }

        // Base language
        if ($lang = self::getEnv('BASE_LANGUAGE')) {
            $config['baseLanguage'] = $lang;
        }

        // Random seed (critical for security)
        if ($seed = self::getEnv('RANDOM_SEED')) {
            $config['randomSeed'] = $seed;
        }

        // Resource base
        if ($base = self::getEnv('RESOURCE_BASE')) {
            $config['resourceBase'] = $base;
        }

        // Mail from configuration
        if ($email = self::getEnv('MAIL_FROM_EMAIL')) {
            $config['mailFromEmail'] = $email;
        }
        if ($name = self::getEnv('MAIL_FROM_NAME')) {
            $config['mailFromName'] = $name;
        }

        // Boolean flags
        if (self::hasEnv('PREPEND_WWW_TO_SUBDOMAIN')) {
            $config['prependWWWToSubdomain'] = self::getBoolEnv('PREPEND_WWW_TO_SUBDOMAIN', true);
        }
        if (self::hasEnv('ALLOW_REGISTRATION')) {
            $config['allowRegistration'] = self::getBoolEnv('ALLOW_REGISTRATION', true);
        }
        if (self::hasEnv('CONFIRM_EMAIL_ADDRESSES')) {
            $config['confirmEmailAddresses'] = self::getBoolEnv('CONFIRM_EMAIL_ADDRESSES', true);
        }

        // Optional paths for external tools
        if ($path = self::getEnv('IMAGE_MAGICK_PATH')) {
            $config['imageMagickPath'] = $path;
        }
        if ($path = self::getEnv('WEASYPRINT_PATH')) {
            $config['weasyprintPath'] = $path;
        }
        if ($path = self::getEnv('LUALATEX_PATH')) {
            $config['lualatexPath'] = $path;
        }

        return $config;
    }

    /**
     * Get environment variable value
     *
     * Checks both $_ENV and getenv() for maximum compatibility
     *
     * @param string $key Environment variable name
     * @param mixed $default Default value if not set
     * @return string|null
     */
    private static function getEnv(string $key, $default = null): ?string
    {
        // Try $_ENV first (more reliable in some configurations)
        if (isset($_ENV[$key])) {
            return $_ENV[$key];
        }

        // Fallback to getenv()
        $value = getenv($key);
        if ($value !== false) {
            return $value;
        }

        return $default;
    }

    /**
     * Check if environment variable is set (even if empty string)
     *
     * @param string $key Environment variable name
     * @return bool
     */
    private static function hasEnv(string $key): bool
    {
        return isset($_ENV[$key]) || getenv($key) !== false;
    }

    /**
     * Get boolean environment variable value
     *
     * Recognizes: true, false, 1, 0, yes, no, on, off (case-insensitive)
     *
     * @param string $key Environment variable name
     * @param bool $default Default value
     * @return bool
     */
    private static function getBoolEnv(string $key, bool $default): bool
    {
        $value = self::getEnv($key);

        if ($value === null) {
            return $default;
        }

        $value = strtolower(trim($value));

        // True values
        if (in_array($value, ['true', '1', 'yes', 'on'], true)) {
            return true;
        }

        // False values
        if (in_array($value, ['false', '0', 'no', 'off'], true)) {
            return false;
        }

        // Fallback to PHP's filter_var for other cases
        return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? $default;
    }
}
