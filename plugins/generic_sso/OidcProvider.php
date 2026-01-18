<?php

declare(strict_types=1);

namespace app\plugins\generic_sso;

use League\OAuth2\Client\Provider\GenericProvider;
use League\OAuth2\Client\Token\AccessToken;

/**
 * OIDC Provider implementation using OAuth2 Client
 * Supports standard OpenID Connect Discovery
 */
class OidcProvider
{
    private GenericProvider $provider;
    private array $config;

    public function __construct(array $config)
    {
        $this->config = $config;

        $providerConfig = [
            'clientId'                => $config['clientId'],
            'clientSecret'            => $config['clientSecret'],
            'redirectUri'             => $config['redirectUri'],
            'urlAuthorize'            => $config['urlAuthorize'],
            'urlAccessToken'          => $config['urlAccessToken'],
            'urlResourceOwnerDetails' => $config['urlResourceOwnerDetails'],
        ];

        // Add PKCE support if enabled
        if (isset($config['pkce']) && $config['pkce']) {
            $providerConfig['pkceMethod'] = \League\OAuth2\Client\Provider\GenericProvider::PKCE_METHOD_S256;
        }

        $this->provider = new GenericProvider($providerConfig);
    }

    /**
     * Get the authorization URL to redirect users to
     */
    public function getAuthorizationUrl(array $options = []): string
    {
        $scopes = $this->config['scopes'] ?? ['openid', 'profile', 'email'];

        // Ensure scopes are space-separated string for OIDC compliance
        $scopeString = is_array($scopes) ? implode(' ', $scopes) : $scopes;

        $options = array_merge([
            'scope' => $scopeString,
        ], $options);

        return $this->provider->getAuthorizationUrl($options);
    }

    /**
     * Get the state parameter for CSRF protection
     */
    public function getState(): string
    {
        return $this->provider->getState();
    }

    /**
     * Access PKCE code property via reflection (internal helper)
     */
    private function accessPkceProperty(string $operation, ?string $value = null): ?string
    {
        try {
            $reflection = new \ReflectionClass($this->provider);
            $property = $reflection->getProperty('pkceCode');
            $property->setAccessible(true);

            if ($operation === 'get') {
                return $property->getValue($this->provider);
            } elseif ($operation === 'set' && $value !== null) {
                $property->setValue($this->provider, $value);
            }
        } catch (\Exception $e) {
            \Yii::error("Failed to {$operation} PKCE code: " . $e->getMessage());
        }
        return null;
    }

    /**
     * Get the PKCE code verifier (if PKCE is enabled)
     */
    public function getPkceCode(): ?string
    {
        return $this->accessPkceProperty('get');
    }

    /**
     * Set the PKCE code verifier (restore from session)
     */
    public function setPkceCode(?string $pkceCode): void
    {
        if ($pkceCode) {
            $this->accessPkceProperty('set', $pkceCode);
        }
    }

    /**
     * Exchange authorization code for access token
     */
    public function getAccessToken(string $code): AccessToken
    {
        return $this->provider->getAccessToken('authorization_code', [
            'code' => $code
        ]);
    }

    /**
     * Get user information from the provider
     */
    public function getResourceOwner(AccessToken $token): array
    {
        $user = $this->provider->getResourceOwner($token);
        return $user->toArray();
    }

    /**
     * Get user information from UserInfo endpoint (standard OIDC)
     */
    public function getUserInfo(AccessToken $token): array
    {
        if (isset($this->config['urlUserInfo'])) {
            $request = $this->provider->getAuthenticatedRequest(
                'GET',
                $this->config['urlUserInfo'],
                $token
            );

            $response = $this->provider->getParsedResponse($request);
            return $response;
        }

        // Fallback to resource owner details
        return $this->getResourceOwner($token);
    }

    /**
     * Parse ID Token claims (JWT payload) without signature verification
     *
     * NOTE: This method does NOT verify the JWT signature. It only extracts claims.
     * For security-critical operations, rely on the getUserInfo() method which uses
     * a validated access token to fetch user information from the userinfo endpoint.
     *
     * To implement proper JWT signature verification, use a library like firebase/php-jwt
     * or lcobucci/jwt and verify against the provider's JWKS endpoint.
     */
    public function parseIdTokenClaims(string $idToken): ?array
    {
        try {
            // Split JWT into parts
            $parts = explode('.', $idToken);
            if (count($parts) !== 3) {
                return null;
            }

            // Decode payload (second part) - WARNING: NOT VERIFYING SIGNATURE
            $payload = base64_decode(strtr($parts[1], '-_', '+/'), true);
            if ($payload === false || $payload === '') {
                return null;
            }
            $claims = json_decode($payload, true);

            if (!$claims) {
                return null;
            }

            // Basic validation (without signature verification)
            if (isset($claims['exp']) && $claims['exp'] < time()) {
                \Yii::warning('ID Token has expired');
                return null;
            }

            if (isset($claims['iss']) && isset($this->config['issuer'])) {
                if ($claims['iss'] !== $this->config['issuer']) {
                    \Yii::warning('Invalid issuer in ID token');
                    return null;
                }
            }

            if (isset($claims['aud'])) {
                $audiences = is_array($claims['aud']) ? $claims['aud'] : [$claims['aud']];
                if (!in_array($this->config['clientId'], $audiences)) {
                    \Yii::warning('Invalid audience in ID token');
                    return null;
                }
            }

            return $claims;
        } catch (\Exception $e) {
            \Yii::error('ID Token parsing failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Discover OIDC configuration from well-known endpoint
     */
    public static function discover(string $issuer): array
    {
        $wellKnownUrl = rtrim($issuer, '/') . '/.well-known/openid-configuration';

        $ch = curl_init($wellKnownUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($httpCode !== 200 || !$response || !is_string($response)) {
            $errorMsg = 'Failed to discover OIDC configuration';
            if ($error) {
                $errorMsg .= ': ' . $error;
            }
            throw new \Exception($errorMsg);
        }

        try {
            $config = json_decode($response, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new \Exception('Invalid OIDC discovery document: ' . $e->getMessage());
        }

        return [
            'issuer' => $config['issuer'] ?? $issuer,
            'urlAuthorize' => $config['authorization_endpoint'] ?? '',
            'urlAccessToken' => $config['token_endpoint'] ?? '',
            'urlUserInfo' => $config['userinfo_endpoint'] ?? '',
            'urlResourceOwnerDetails' => $config['userinfo_endpoint'] ?? '',
            'jwks_uri' => $config['jwks_uri'] ?? '',
            'scopes_supported' => $config['scopes_supported'] ?? ['openid', 'profile', 'email'],
        ];
    }

    /**
     * Get logout URL if supported by provider
     */
    public function getLogoutUrl(string $postLogoutRedirectUri = ''): ?string
    {
        if (isset($this->config['urlLogout'])) {
            $url = $this->config['urlLogout'];

            if ($postLogoutRedirectUri) {
                $separator = strpos($url, '?') !== false ? '&' : '?';
                $url .= $separator . 'post_logout_redirect_uri=' . urlencode($postLogoutRedirectUri);

                if (isset($this->config['clientId'])) {
                    $url .= '&client_id=' . urlencode($this->config['clientId']);
                }
            }

            return $url;
        }

        return null;
    }
}
