<?php

declare(strict_types=1);

namespace app\plugins\generic_sso;

use app\components\{LoginProviderInterface, RequestContext, UrlHelper};
use app\models\db\{ConsultationUserGroup, User};
use app\models\settings\{AntragsgruenApp, Site as SiteSettings};
use app\plugins\generic_sso\{OidcProvider, SamlProvider};
use yii\helpers\Url;

/**
 * Generic SSO Login Provider
 * Supports both OIDC and SAML authentication with JIT provisioning
 */
class SsoLogin implements LoginProviderInterface
{
    private array $config;
    private string $protocol;

    public function __construct()
    {
        $this->config = $this->loadConfiguration();
        $this->protocol = $this->config['protocol'] ?? 'oidc';
    }

    /**
     * Load configuration from config file
     */
    private function loadConfiguration(): array
    {
        $configFile = __DIR__ . '/../../config/generic_sso.json';

        if (!file_exists($configFile)) {
            return [
                'protocol' => 'oidc',
                'enabled' => false,
            ];
        }

        $config = json_decode(file_get_contents($configFile), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log('Generic SSO: Invalid configuration file: ' . json_last_error_msg());
            return ['protocol' => 'oidc', 'enabled' => false];
        }

        return $config;
    }

    public function getId(): string
    {
        return (string)SiteSettings::LOGIN_EXTERNAL;
    }

    public function getName(): string
    {
        // Use config value if provided, otherwise use translation
        return $this->config['providerName'] ?? \Yii::t('generic_sso', 'login_provider_name');
    }

    public function renderLoginForm(string $backUrl, bool $active): string
    {
        if (!$active || !($this->config['enabled'] ?? false)) {
            return '';
        }

        return \Yii::$app->controller->renderPartial('@app/plugins/generic_sso/views/login', [
            'backUrl' => $backUrl,
            'providerName' => $this->getName(),
            'buttonText' => $this->config['buttonText'] ?? \Yii::t('generic_sso', 'login_button'),
            'description' => $this->config['description'] ?? \Yii::t('generic_sso', 'login_description'),
        ]);
    }

    /**
     * Perform OIDC login
     */
    private function performOidcLogin(): User
    {
        $oidcConfig = $this->config['oidc'] ?? [];

        // Set redirect URI if not configured
        if (!isset($oidcConfig['redirectUri'])) {
            $oidcConfig['redirectUri'] = UrlHelper::absolutizeLink(
                Url::toRoute(['/generic_sso/login/callback'])
            );
        }

        $provider = new OidcProvider($oidcConfig);

        // Check if we have a code (callback from provider)
        $code = \Yii::$app->request->get('code');
        $state = \Yii::$app->request->get('state');

        if (!$code) {
            // Start authentication flow
            // Add any additional authorization parameters from config
            $authOptions = [];
            if (isset($oidcConfig['authorizationParams'])) {
                $authOptions = $oidcConfig['authorizationParams'];
            }

            $authUrl = $provider->getAuthorizationUrl($authOptions);

            // Store state and PKCE verifier in session
            \Yii::$app->session->set('oauth2state', $provider->getState());

            // Store PKCE code if PKCE is enabled
            if (isset($oidcConfig['pkce']) && $oidcConfig['pkce']) {
                $pkceCode = $provider->getPkceCode();
                if ($pkceCode) {
                    \Yii::$app->session->set('oauth2pkce', $pkceCode);
                }
            }

            \Yii::$app->response->redirect($authUrl);
            \Yii::$app->end();
        }

        // Validate state to prevent CSRF
        $sessionState = \Yii::$app->session->get('oauth2state');
        if (!$state || $state !== $sessionState) {
            \Yii::$app->session->remove('oauth2state');
            \Yii::$app->session->remove('oauth2pkce');
            throw new \Exception('Invalid state parameter');
        }

        \Yii::$app->session->remove('oauth2state');

        // Restore PKCE code verifier if it was stored
        if (isset($oidcConfig['pkce']) && $oidcConfig['pkce']) {
            $pkceCode = \Yii::$app->session->get('oauth2pkce');
            if ($pkceCode) {
                $provider->setPkceCode($pkceCode);
                \Yii::$app->session->remove('oauth2pkce');
            }
        }

        // Exchange code for access token
        $token = $provider->getAccessToken($code);

        // Get user information
        $userInfo = $provider->getUserInfo($token);

        // Verify ID token if present
        if (isset($token->getValues()['id_token'])) {
            $idTokenClaims = $provider->verifyIdToken($token->getValues()['id_token']);
            if ($idTokenClaims) {
                $userInfo = array_merge($userInfo, $idTokenClaims);
            }
        }

        // Create or update user with JIT provisioning
        return $this->getOrCreateUser($userInfo, 'oidc');
    }

    /**
     * Perform SAML login
     */
    private function performSamlLogin(): User
    {
        $samlConfig = $this->config['saml'] ?? [];
        $provider = new SamlProvider($samlConfig);

        $provider->requireAuth([]);

        if (!$provider->isAuthenticated()) {
            throw new \Exception('SAML authentication failed');
        }

        if (!$provider->validateAuthentication()) {
            throw new \Exception('SAML validation failed');
        }

        $attributes = $provider->getAttributes();

        // Add NameID as potential identifier
        if ($nameId = $provider->getNameId()) {
            $attributes['nameId'] = $nameId;
        }

        // Create or update user with JIT provisioning
        return $this->getOrCreateUser($attributes, 'saml');
    }

    public function performLoginAndReturnUser(): User
    {
        if (!($this->config['enabled'] ?? false)) {
            throw new \Exception('SSO is not enabled');
        }

        if ($this->protocol === 'oidc') {
            $user = $this->performOidcLogin();
        } elseif ($this->protocol === 'saml') {
            $user = $this->performSamlLogin();
        } else {
            throw new \Exception('Unsupported protocol: ' . $this->protocol);
        }

        // Login the user
        RequestContext::getYiiUser()->login($user, AntragsgruenApp::getInstance()->autoLoginDuration);

        // Update last login date
        $user->dateLastLogin = date('Y-m-d H:i:s');
        $user->save();

        return $user;
    }

    /**
     * Get or create user with JIT provisioning
     */
    private function getOrCreateUser(array $attributes, string $source): User
    {
        $attributeMapping = $this->config['attributeMapping'] ?? $this->getDefaultAttributeMapping($source);

        // Extract user data based on attribute mapping
        $userData = $this->extractUserData($attributes, $attributeMapping);

        // Validate required fields
        if (empty($userData['email'])) {
            throw new \Exception('Email is required but not provided by SSO provider');
        }

        if (empty($userData['username'])) {
            // Fallback to email as username
            $userData['username'] = $userData['email'];
        }

        // Create auth identifier
        $auth = $this->usernameToAuth($userData['username']);

        // Find or create user
        /** @var User|null $user */
        $user = User::findOne(['auth' => $auth]);

        if (!$user) {
            $user = new User();
            $user->auth = $auth;
            $user->status = User::STATUS_CONFIRMED;
            $user->emailConfirmed = 1;
        }

        // Update user data
        $user->email = $userData['email'];
        $user->name = trim(($userData['givenName'] ?? '') . ' ' . ($userData['familyName'] ?? '')) ?: $userData['email'];
        $user->nameGiven = $userData['givenName'] ?? '';
        $user->nameFamily = $userData['familyName'] ?? '';
        $user->organization = $userData['organization'] ?? $user->organization ?? '';

        // Set fixed data to prevent user modification
        $user->fixedData = User::FIXED_NAME;

        // Prevent SSO users from changing password (managed by SSO provider)
        $userSettings = $user->getSettingsObj();
        $userSettings->preventPasswordChange = true;
        $user->setSettingsObj($userSettings);

        if (!$user->save()) {
            $errors = json_encode($user->getErrors());
            throw new \Exception('Could not create/update user: ' . $errors);
        }

        // Sync user groups if configured
        if (isset($userData['groups']) && is_array($userData['groups'])) {
            $this->syncUserGroups($user, $userData['groups']);
        }

        return $user;
    }

    /**
     * Extract user data from attributes based on mapping
     */
    private function extractUserData(array $attributes, array $mapping): array
    {
        $userData = [];

        foreach ($mapping as $targetField => $sourceField) {
            $value = $this->getAttributeValue($attributes, $sourceField);

            if ($value !== null) {
                $userData[$targetField] = $value;
            }
        }

        return $userData;
    }

    /**
     * Get attribute value from nested structure
     */
    private function getAttributeValue(array $attributes, $sourceField)
    {
        // Handle dot notation for nested attributes (e.g., "address.country")
        if (is_string($sourceField) && strpos($sourceField, '.') !== false) {
            $parts = explode('.', $sourceField);
            $value = $attributes;

            foreach ($parts as $part) {
                if (is_array($value) && isset($value[$part])) {
                    $value = $value[$part];
                } else {
                    return null;
                }
            }

            return $value;
        }

        // Direct attribute access
        if (isset($attributes[$sourceField])) {
            $value = $attributes[$sourceField];

            // Handle array values (common in SAML)
            if (is_array($value) && count($value) === 1 && isset($value[0])) {
                return $value[0];
            }

            return $value;
        }

        return null;
    }

    /**
     * Get default attribute mapping based on protocol
     */
    private function getDefaultAttributeMapping(string $source): array
    {
        if ($source === 'oidc') {
            return [
                'email' => 'email',
                'username' => 'preferred_username',
                'givenName' => 'given_name',
                'familyName' => 'family_name',
                'organization' => 'organization',
                'groups' => 'groups',
            ];
        } elseif ($source === 'saml') {
            return [
                'email' => 'mail',
                'username' => 'uid',
                'givenName' => 'givenName',
                'familyName' => 'sn',
                'organization' => 'o',
                'groups' => 'memberOf',
            ];
        }

        return [];
    }

    /**
     * Sync user groups from SSO provider
     */
    private function syncUserGroups(User $user, array $groups): void
    {
        if (!$this->config['syncGroups'] ?? false) {
            return;
        }

        $groupMapping = $this->config['groupMapping'] ?? [];
        $groupPrefix = Module::AUTH_KEY_GROUPS . ':';

        // Map provider groups to external IDs
        $newGroupIds = [];
        foreach ($groups as $group) {
            // If group mapping is configured, use it
            if (!empty($groupMapping) && isset($groupMapping[$group])) {
                $mappedGroup = $groupMapping[$group];
                $newGroupIds[] = $groupPrefix . $mappedGroup;
            } else {
                // Otherwise use the group name as-is
                $newGroupIds[] = $groupPrefix . $group;
            }
        }

        // Get current SSO-managed groups
        $oldGroupIds = [];
        foreach ($user->userGroups as $userGroup) {
            if ($userGroup->externalId && str_starts_with($userGroup->externalId, $groupPrefix)) {
                $oldGroupIds[] = $userGroup->externalId;

                // Remove groups that are no longer present
                if (!in_array($userGroup->externalId, $newGroupIds)) {
                    $user->unlink('userGroups', $userGroup, true);
                }
            }
        }

        // Add new groups
        foreach ($newGroupIds as $groupId) {
            if (!in_array($groupId, $oldGroupIds)) {
                $userGroup = ConsultationUserGroup::findByExternalId($groupId);
                if ($userGroup) {
                    $user->link('userGroups', $userGroup);
                }
            }
        }
    }

    public function userWasLoggedInWithProvider(?User $user): bool
    {
        if (!$user || !$user->auth) {
            return false;
        }

        $authParts = explode(':', $user->auth);
        return $authParts[0] === Module::AUTH_KEY_USERS;
    }

    public function usernameToAuth(string $username): string
    {
        return Module::AUTH_KEY_USERS . ':' . $username;
    }

    public function getSelectableUserOrganizations(User $user): ?array
    {
        if (!($this->config['syncGroups'] ?? false)) {
            return null;
        }

        $organizations = [];
        $groupPrefix = Module::AUTH_KEY_GROUPS . ':';

        foreach ($user->userGroups as $userGroup) {
            if ($userGroup->externalId && str_starts_with($userGroup->externalId, $groupPrefix)) {
                $organizations[] = $userGroup;
            }
        }

        return empty($organizations) ? null : $organizations;
    }

    public function logoutCurrentUserIfRelevant(string $backUrl): ?string
    {
        $user = User::getCurrentUser();

        if (!$this->userWasLoggedInWithProvider($user)) {
            return null;
        }

        // Logout locally
        RequestContext::getYiiUser()->logout();

        // Perform SSO logout if configured
        if ($this->config['singleLogout'] ?? false) {
            if ($this->protocol === 'oidc') {
                return $this->performOidcLogout($backUrl);
            } elseif ($this->protocol === 'saml') {
                return $this->performSamlLogout($backUrl);
            }
        }

        return $backUrl;
    }

    /**
     * Perform OIDC logout
     */
    private function performOidcLogout(string $backUrl): string
    {
        $oidcConfig = $this->config['oidc'] ?? [];

        if (isset($oidcConfig['urlLogout'])) {
            $provider = new OidcProvider($oidcConfig);
            $logoutUrl = $provider->getLogoutUrl($backUrl);

            if ($logoutUrl) {
                return $logoutUrl;
            }
        }

        return $backUrl;
    }

    /**
     * Perform SAML logout
     */
    private function performSamlLogout(string $backUrl): string
    {
        try {
            $samlConfig = $this->config['saml'] ?? [];
            $provider = new SamlProvider($samlConfig);

            if ($provider->isAuthenticated()) {
                $provider->logout($backUrl);
                // Note: SAML logout typically redirects, so this may not return
            }
        } catch (\Exception $e) {
            error_log('SAML logout failed: ' . $e->getMessage());
        }

        return $backUrl;
    }

    public function renderAddMultipleUsersForm(): ?string
    {
        return null;
    }
}
