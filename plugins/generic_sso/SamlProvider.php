<?php

declare(strict_types=1);

namespace app\plugins\generic_sso;

use SimpleSAML\Auth\Simple;

/**
 * SAML Provider implementation using SimpleSAMLphp
 * Supports SAML 2.0 authentication
 */
class SamlProvider
{
    private Simple $samlClient;
    private array $config;
    private string $authSource;

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->authSource = $config['authSource'] ?? 'generic-sso-sp';
        $this->samlClient = new Simple($this->authSource);
    }

    /**
     * Initiate SAML authentication
     */
    public function requireAuth(array $params = []): void
    {
        $this->samlClient->requireAuth($params);
    }

    /**
     * Check if user is authenticated
     */
    public function isAuthenticated(): bool
    {
        return $this->samlClient->isAuthenticated();
    }

    /**
     * Get user attributes from SAML assertion
     */
    public function getAttributes(): array
    {
        return $this->samlClient->getAttributes();
    }

    /**
     * Get a single attribute value
     */
    public function getAttribute(string $name, $default = null)
    {
        $values = $this->getAttributeValues($name);
        return $values[0] ?? $default;
    }

    /**
     * Get all attribute values for a multi-valued attribute
     */
    public function getAttributeValues(string $name): array
    {
        $attributes = $this->getAttributes();

        if (!isset($attributes[$name])) {
            return [];
        }

        return is_array($attributes[$name]) ? $attributes[$name] : [$attributes[$name]];
    }

    /**
     * Logout from SAML session
     */
    public function logout(string $returnTo = ''): void
    {
        $params = [];
        if ($returnTo) {
            $params['ReturnTo'] = $returnTo;
        }

        $this->samlClient->logout($params);
    }

    /**
     * Get the SAML authentication data
     */
    public function getAuthData(?string $name = null)
    {
        if ($name === null) {
            return $this->samlClient->getAuthData();
        }

        return $this->samlClient->getAuthData($name);
    }

    /**
     * Get SAML NameID
     */
    public function getNameId(): ?string
    {
        $nameId = $this->samlClient->getAuthData('saml:sp:NameID');

        if (is_array($nameId) && isset($nameId['Value'])) {
            return $nameId['Value'];
        }

        return $nameId;
    }

    /**
     * Get SAML Session Index
     */
    public function getSessionIndex(): ?string
    {
        return $this->samlClient->getAuthData('saml:sp:SessionIndex');
    }

    /**
     * Get mapped user data based on configuration
     */
    public function getMappedUserData(array $attributeMapping): array
    {
        $attributes = $this->getAttributes();
        $userData = [];

        foreach ($attributeMapping as $targetField => $sourceAttribute) {
            if (isset($attributes[$sourceAttribute])) {
                $value = $attributes[$sourceAttribute];

                // Handle array values (SAML typically returns arrays)
                if (is_array($value)) {
                    if ($targetField === 'groups' || $targetField === 'roles') {
                        // Keep as array for groups/roles
                        $userData[$targetField] = $value;
                    } else {
                        // Take first value for single-value fields
                        $userData[$targetField] = $value[0] ?? '';
                    }
                } else {
                    $userData[$targetField] = $value;
                }
            }
        }

        return $userData;
    }

    /**
     * Validate SAML response
     */
    public function validateAuthentication(): bool
    {
        if (!$this->isAuthenticated()) {
            return false;
        }

        // Check for required attributes if configured
        if (isset($this->config['requiredAttributes'])) {
            $attributes = $this->getAttributes();

            foreach ($this->config['requiredAttributes'] as $required) {
                if (!isset($attributes[$required]) || empty($attributes[$required])) {
                    \Yii::error("SAML: Missing required attribute: {$required}");
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Get login URL for SAML authentication
     */
    public function getLoginUrl(string $returnTo = ''): string
    {
        return $this->samlClient->getLoginURL($returnTo);
    }

    /**
     * Get logout URL for SAML logout
     */
    public function getLogoutUrl(string $returnTo = ''): string
    {
        return $this->samlClient->getLogoutURL($returnTo);
    }
}
