<?php

namespace Tests\Support\Helper;

/**
 * Trait for tests that work with environment variables
 *
 * Provides helper methods for setting up and tearing down environment
 * variable state to prevent test pollution.
 */
trait EnvironmentTestTrait
{
    /**
     * Clear all environment variables used by the environment config loader
     *
     * This should be called in setUp() and tearDown() to ensure clean test state.
     */
    protected function clearTestEnvVars(): void
    {
        $vars = [
            'DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASSWORD', 'DB_PORT', 'DB_CHARSET',
            'TABLE_PREFIX', 'DB_TABLE_PREFIX',
            'REDIS_HOST', 'REDIS_PORT', 'REDIS_DB', 'REDIS_PASSWORD',
            'SMTP_HOST', 'SMTP_PORT', 'SMTP_USERNAME', 'SMTP_PASSWORD', 'SMTP_ENCRYPTION',
            'MAILER_DISABLED', 'MAILER_DSN',
            'APP_DOMAIN', 'APP_PROTOCOL', 'MULTISITE_MODE', 'SITE_SUBDOMAIN', 'BASE_LANGUAGE',
            'RANDOM_SEED', 'RESOURCE_BASE', 'MAIL_FROM_EMAIL', 'MAIL_FROM_NAME',
            'PREPEND_WWW_TO_SUBDOMAIN', 'ALLOW_REGISTRATION', 'CONFIRM_EMAIL_ADDRESSES',
            'IMAGE_MAGICK_PATH', 'WEASYPRINT_PATH', 'LUALATEX_PATH'
        ];

        foreach ($vars as $var) {
            unset($_ENV[$var]);
            putenv($var);
        }
    }
}
