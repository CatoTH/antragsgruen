<?php

declare(strict_types=1);

namespace app\plugins\generic_sso;

/**
 * Builds the generic_sso configuration from environment variables.
 *
 * This is the environment-variable counterpart of config/generic_sso.json, so that
 * SSO can be configured in containerized deployments without a writable config
 * directory. The JSON file keeps precedence where both are present, mirroring the
 * config.json > environment order documented in docs/environment-variables.md.
 *
 * @see docs/environment-variables.md
 */
class EnvironmentConfig
{
    private const DISCOVERY_CACHE_PREFIX = 'generic_sso.discovery.';
    private const DISCOVERY_CACHE_TTL = 86400;

    /**
     * @return array Configuration array in generic_sso.json shape, or [] when SSO is not configured via environment.
     */
    public static function load(): array
    {
        if (!self::hasEnv('SSO_ENABLED')) {
            return [];
        }

        $protocol = strtolower(trim((string)self::getEnv('SSO_PROTOCOL', 'oidc')));
        if (!in_array($protocol, ['oidc', 'saml'], true)) {
            \Yii::error('Generic SSO: unsupported SSO_PROTOCOL "' . $protocol . '", falling back to oidc');
            $protocol = 'oidc';
        }

        $config = [
            'enabled' => self::getBoolEnv('SSO_ENABLED', false),
            'protocol' => $protocol,
            'singleLogout' => self::getBoolEnv('SSO_SINGLE_LOGOUT', false),
            'syncGroups' => self::getBoolEnv('SSO_SYNC_GROUPS', false),
            'linkByEmail' => self::getBoolEnv('SSO_LINK_BY_EMAIL', false),
        ];

        if (self::hasEnv('SSO_PROVIDER_ID')) {
            $config['providerId'] = self::getEnv('SSO_PROVIDER_ID');
        }

        // SAML-specific settings still come from config/generic_sso.json: SimpleSAMLphp
        // is configured outside the application anyway, so there is little to gain from
        // moving only half of it into the environment.
        if ($protocol === 'oidc') {
            $config['oidc'] = self::getOidcConfig();
        }

        $attributeMapping = self::getAttributeMapping();
        if ($attributeMapping !== []) {
            $config['attributeMapping'] = $attributeMapping;
        }

        $groupMapping = self::getJsonEnv('SSO_GROUP_MAPPING');
        if ($groupMapping !== null) {
            $config['groupMapping'] = $groupMapping;
        }

        return $config;
    }

    private static function getOidcConfig(): array
    {
        $oidc = [];

        $stringKeys = [
            'SSO_OIDC_CLIENT_ID' => 'clientId',
            'SSO_OIDC_CLIENT_SECRET' => 'clientSecret',
            'SSO_OIDC_REDIRECT_URI' => 'redirectUri',
            'SSO_OIDC_ISSUER' => 'issuer',
            'SSO_OIDC_URL_AUTHORIZE' => 'urlAuthorize',
            'SSO_OIDC_URL_ACCESS_TOKEN' => 'urlAccessToken',
            'SSO_OIDC_URL_USER_INFO' => 'urlUserInfo',
            'SSO_OIDC_URL_LOGOUT' => 'urlLogout',
        ];
        foreach ($stringKeys as $env => $key) {
            if (self::hasEnv($env)) {
                $oidc[$key] = (string)self::getEnv($env);
            }
        }

        if (self::hasEnv('SSO_OIDC_SCOPES')) {
            $oidc['scopes'] = self::getListEnv('SSO_OIDC_SCOPES');
        }
        if (self::hasEnv('SSO_OIDC_PKCE')) {
            $oidc['pkce'] = self::getBoolEnv('SSO_OIDC_PKCE', false);
        }
        $authorizationParams = self::getJsonEnv('SSO_OIDC_AUTHORIZATION_PARAMS');
        if ($authorizationParams !== null) {
            $oidc['authorizationParams'] = $authorizationParams;
        }

        // Fill in whatever the issuer's discovery document provides, without overriding
        // endpoints that were set explicitly. discover() also returns jwks_uri and
        // scopes_supported, which are not configuration keys, so only take the endpoints.
        $issuer = $oidc['issuer'] ?? null;
        if ($issuer !== null && $issuer !== '' && self::getBoolEnv('SSO_OIDC_DISCOVERY', true)) {
            $discovered = self::discover($issuer);
            $endpointKeys = ['urlAuthorize', 'urlAccessToken', 'urlUserInfo', 'urlResourceOwnerDetails', 'urlLogout'];
            foreach ($endpointKeys as $key) {
                $value = $discovered[$key] ?? '';
                if ($value !== '' && (!isset($oidc[$key]) || $oidc[$key] === '')) {
                    $oidc[$key] = $value;
                }
            }
        }

        // GenericProvider insists on urlResourceOwnerDetails; urlUserInfo is the name the
        // OIDC spec uses, so accept either and keep both populated.
        if (!isset($oidc['urlResourceOwnerDetails']) && isset($oidc['urlUserInfo'])) {
            $oidc['urlResourceOwnerDetails'] = $oidc['urlUserInfo'];
        }

        return $oidc;
    }

    private static function getAttributeMapping(): array
    {
        $mapping = [];

        $keys = [
            'SSO_ATTR_EMAIL' => 'email',
            'SSO_ATTR_USERNAME' => 'username',
            'SSO_ATTR_GIVEN_NAME' => 'givenName',
            'SSO_ATTR_FAMILY_NAME' => 'familyName',
            'SSO_ATTR_ORGANIZATION' => 'organization',
            'SSO_ATTR_GROUPS' => 'groups',
        ];
        foreach ($keys as $env => $key) {
            if (self::hasEnv($env)) {
                $mapping[$key] = (string)self::getEnv($env);
            }
        }

        return $mapping;
    }

    /**
     * The login provider is instantiated per request, so an uncached discovery request
     * would add a round trip to the identity provider on every page load.
     */
    private static function discover(string $issuer): array
    {
        $cacheKey = self::DISCOVERY_CACHE_PREFIX . md5($issuer);

        // Yii throws when the component is not configured, and this runs during
        // application setup, so never let caching problems break the login flow.
        $cache = null;
        try {
            $component = \Yii::$app->has('cache') ? \Yii::$app->get('cache') : null;
            if ($component instanceof \yii\caching\CacheInterface) {
                $cache = $component;
            }
        } catch (\Throwable $e) {
            $cache = null;
        }

        if ($cache !== null) {
            $cached = $cache->get($cacheKey);
            if (is_array($cached)) {
                return $cached;
            }
        }

        try {
            $discovered = OidcProvider::discover($issuer);
        } catch (\Throwable $e) {
            \Yii::error('Generic SSO: OIDC discovery failed for ' . $issuer . ': ' . $e->getMessage());
            return [];
        }

        if ($cache !== null) {
            $cache->set($cacheKey, $discovered, self::DISCOVERY_CACHE_TTL);
        }

        return $discovered;
    }

    /**
     * Merge environment-derived configuration with the JSON file, letting the file win.
     */
    public static function mergeWithFileConfig(array $envConfig, array $fileConfig): array
    {
        foreach ($fileConfig as $key => $value) {
            if (is_array($value) && isset($envConfig[$key]) && is_array($envConfig[$key])) {
                $envConfig[$key] = self::mergeWithFileConfig($envConfig[$key], $value);
            } else {
                $envConfig[$key] = $value;
            }
        }

        return $envConfig;
    }

    /**
     * @return string[]
     */
    private static function getListEnv(string $key): array
    {
        $value = (string)self::getEnv($key, '');

        return array_values(array_filter(array_map('trim', explode(',', $value)), static fn(string $v): bool => $v !== ''));
    }

    private static function getJsonEnv(string $key): ?array
    {
        if (!self::hasEnv($key)) {
            return null;
        }

        $value = trim((string)self::getEnv($key, ''));
        if ($value === '') {
            return null;
        }

        try {
            $decoded = json_decode($value, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            \Yii::error('Generic SSO: ' . $key . ' is not valid JSON: ' . $e->getMessage());
            return null;
        }

        return is_array($decoded) ? $decoded : null;
    }

    private static function getEnv(string $key, ?string $default = null): ?string
    {
        if (isset($_ENV[$key])) {
            return (string)$_ENV[$key];
        }

        $value = getenv($key);
        if ($value !== false) {
            return $value;
        }

        return $default;
    }

    private static function hasEnv(string $key): bool
    {
        return isset($_ENV[$key]) || getenv($key) !== false;
    }

    private static function getBoolEnv(string $key, bool $default): bool
    {
        $value = self::getEnv($key);
        if ($value === null) {
            return $default;
        }

        $value = strtolower(trim($value));
        if (in_array($value, ['true', '1', 'yes', 'on'], true)) {
            return true;
        }
        if (in_array($value, ['false', '0', 'no', 'off'], true)) {
            return false;
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? $default;
    }
}
