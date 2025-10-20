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

        $options = array_merge([
            'scope' => $scopes,
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
     * Parse and verify ID Token (JWT)
     */
    public function verifyIdToken(string $idToken): ?array
    {
        try {
            // Split JWT into parts
            $parts = explode('.', $idToken);
            if (count($parts) !== 3) {
                return null;
            }

            // Decode payload (second part)
            $payload = base64_decode(strtr($parts[1], '-_', '+/'));
            $claims = json_decode($payload, true);

            if (!$claims) {
                return null;
            }

            // Basic validation
            if (isset($claims['exp']) && $claims['exp'] < time()) {
                throw new \Exception('ID Token has expired');
            }

            if (isset($claims['iss']) && isset($this->config['issuer'])) {
                if ($claims['iss'] !== $this->config['issuer']) {
                    throw new \Exception('Invalid issuer');
                }
            }

            if (isset($claims['aud'])) {
                $audiences = is_array($claims['aud']) ? $claims['aud'] : [$claims['aud']];
                if (!in_array($this->config['clientId'], $audiences)) {
                    throw new \Exception('Invalid audience');
                }
            }

            return $claims;
        } catch (\Exception $e) {
            error_log('ID Token verification failed: ' . $e->getMessage());
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

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200 || !$response) {
            throw new \Exception('Failed to discover OIDC configuration');
        }

        $config = json_decode($response, true);
        if (!$config) {
            throw new \Exception('Invalid OIDC discovery document');
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
