# Generic SSO Plugin for Antragsgrün

A provider-agnostic Single Sign-On (SSO) plugin that supports both **OpenID Connect (OIDC)** and **SAML 2.0** authentication protocols with Just-In-Time (JIT) user provisioning.

## Features

- ✅ **OIDC Support**: Standards-compliant OpenID Connect implementation
- ✅ **SAML 2.0 Support**: Enterprise SAML authentication via SimpleSAMLphp
- ✅ **JIT Provisioning**: Automatically creates users on first login
- ✅ **Provider Agnostic**: Works with any OIDC/SAML compliant identity provider
- ✅ **Group Synchronization**: Sync user groups/roles from your IdP
- ✅ **Single Logout**: Optional SSO logout support
- ✅ **Attribute Mapping**: Flexible mapping of IdP attributes to user fields
- ✅ **PKCE Support**: Enhanced security for OIDC flows
- ✅ **Security**: CSRF protection, state validation, token verification

## Supported Identity Providers

### OIDC Providers
- Keycloak
- Okta
- Azure AD / Microsoft Entra ID
- Google Workspace
- Auth0
- Authentik
- Zitadel
- Any OIDC-compliant provider

### SAML Providers
- Azure AD SAML
- Okta SAML
- SimpleSAMLphp
- Shibboleth
- Any SAML 2.0 compliant provider

## Installation

### 1. Install Dependencies

For **OIDC** support, install the OAuth2 client library:

```bash
cd /path/to/antragsgruen
composer require league/oauth2-client
```

For **SAML** support, SimpleSAMLphp should already be available if using existing SAML plugins. If not:

```bash
composer require simplesamlphp/simplesamlphp
```

### 2. Enable the Plugin

Add the plugin to your Antragsgrün configuration. Edit `config/config.json`:

```json
{
  "plugins": [
    "generic_sso"
  ]
}
```

### 3. Configure the Plugin

Copy the example configuration:

```bash
cp plugins/generic_sso/config.example.json config/generic_sso.json
```

Edit `config/generic_sso.json` with your IdP settings (see Configuration section below).

## Configuration

### OIDC Configuration

#### Basic Configuration

```json
{
  "enabled": true,
  "protocol": "oidc",
  "providerId": "my-company-sso",
  "providerName": "Company SSO",
  "buttonText": "Login with Company Account",
  "description": "You will be redirected to your company login page.",
  "singleLogout": true,
  "syncGroups": true,
  "oidc": {
    "clientId": "your-client-id",
    "clientSecret": "your-client-secret",
    "redirectUri": "https://antragsgruen.example.com/sso-callback",
    "urlAuthorize": "https://idp.example.com/oauth2/authorize",
    "urlAccessToken": "https://idp.example.com/oauth2/token",
    "urlResourceOwnerDetails": "https://idp.example.com/oauth2/userinfo",
    "urlLogout": "https://idp.example.com/oauth2/logout",
    "issuer": "https://idp.example.com",
    "scopes": ["openid", "profile", "email", "groups"],
    "pkce": true
  },
  "attributeMapping": {
    "email": "email",
    "username": "preferred_username",
    "givenName": "given_name",
    "familyName": "family_name",
    "organization": "organization",
    "groups": "groups"
  }
}
```

#### OIDC Discovery

Many OIDC providers support automatic discovery via the `.well-known/openid-configuration` endpoint. You can use the `OidcProvider::discover()` method to automatically retrieve endpoints:

```php
$config = OidcProvider::discover('https://idp.example.com');
```

### SAML Configuration

#### Plugin Configuration

```json
{
  "enabled": true,
  "protocol": "saml",
  "providerId": "company-saml",
  "providerName": "Company SAML SSO",
  "buttonText": "Login with SAML",
  "singleLogout": true,
  "syncGroups": true,
  "saml": {
    "authSource": "company-saml-sp",
    "requiredAttributes": ["mail", "uid"]
  },
  "attributeMapping": {
    "email": "mail",
    "username": "uid",
    "givenName": "givenName",
    "familyName": "sn",
    "organization": "o",
    "groups": "memberOf"
  }
}
```

#### SimpleSAMLphp Configuration

Configure SimpleSAMLphp in your `simplesamlphp/config/authsources.php`:

```php
<?php
$config = [
    'company-saml-sp' => [
        'saml:SP',
        'entityID' => 'https://antragsgruen.example.com',
        'idp' => 'https://idp.example.com/saml/metadata',
        // Or use metadata URL
        'metadata.sign.enable' => true,
        'metadata.sign.algorithm' => 'http://www.w3.org/2001/04/xmldsig-more#rsa-sha256',
    ],
];
```

### Translation and Customization

The plugin supports multi-language content through Antragsgrün's translation system.

#### Translatable Strings

The following UI elements are translatable:

- **Provider Name**: The title shown above the login button
- **Button Text**: The text displayed on the login button
- **Description**: Optional explanatory text shown below the form

#### Using Translations

**Option 1: Use default translations (recommended)**

Simply omit `providerName`, `buttonText`, and `description` from your configuration:

```json
{
  "enabled": true,
  "protocol": "oidc",
  "providerId": "my-sso",
  "oidc": {
    "clientId": "...",
    "clientSecret": "..."
  }
}
```

The plugin automatically displays text in the user's selected language using defaults from:
- `plugins/generic_sso/messages/de/generic_sso.php` (German)
- `plugins/generic_sso/messages/en/generic_sso.php` (English)
- `plugins/generic_sso/messages/fr/generic_sso.php` (French)
- `plugins/generic_sso/messages/nl/generic_sso.php` (Dutch)

**Option 2: Override via configuration**

Provide specific values in `config/generic_sso.json`:

```json
{
  "enabled": true,
  "protocol": "oidc",
  "providerId": "my-company-sso",
  "providerName": "Acme Corp Login",
  "buttonText": "Sign in with Acme",
  "description": "Use your Acme employee credentials.",
  "oidc": { ... }
}
```

Configuration values take precedence over translations.

**Option 3: Customize via Admin Panel**

Administrators can customize translations for their specific consultation:

1. Navigate to **Admin Panel** → **Settings** → **Translation**
2. Select category: **generic_sso**
3. Customize the following keys:
   - `login_provider_name` - Provider name/title
   - `login_button` - Login button text
   - `login_description` - Description/help text

Changes are immediately visible and consultation-specific (don't affect other consultations).

#### Supported Languages

The plugin provides translations for:
- **German (de)** - Primary language
- **English (en)** - International language
- **French (fr)** - For Swiss/French users
- **Dutch (nl)** - For Dutch users

To add additional languages, create a new file at:
```
plugins/generic_sso/messages/{language_code}/generic_sso.php
```

Example for Spanish (`plugins/generic_sso/messages/es/generic_sso.php`):

```php
<?php
return [
    'login_provider_name' => 'Inicio de sesión SSO',
    'login_button' => 'Iniciar sesión con SSO',
    'login_description' => 'Será redirigido a la página de inicio de sesión de su organización.',
];
```

#### Translation Priority

When displaying text, the plugin uses this priority order:

1. **Configuration values** (if provided in `config/generic_sso.json`)
2. **Admin panel overrides** (if customized per consultation)
3. **Translation files** (language-specific defaults)
4. **Translation key name** (fallback if translation missing)

This ensures backward compatibility while providing maximum flexibility.

### Provider-Specific Examples

#### Keycloak

```json
{
  "protocol": "oidc",
  "oidc": {
    "clientId": "antragsgruen",
    "clientSecret": "your-secret",
    "redirectUri": "https://antragsgruen.example.com/sso-callback",
    "urlAuthorize": "https://keycloak.example.com/realms/your-realm/protocol/openid-connect/auth",
    "urlAccessToken": "https://keycloak.example.com/realms/your-realm/protocol/openid-connect/token",
    "urlResourceOwnerDetails": "https://keycloak.example.com/realms/your-realm/protocol/openid-connect/userinfo",
    "urlLogout": "https://keycloak.example.com/realms/your-realm/protocol/openid-connect/logout",
    "issuer": "https://keycloak.example.com/realms/your-realm",
    "scopes": ["openid", "profile", "email", "groups"],
    "pkce": true
  }
}
```

#### Azure AD / Entra ID

```json
{
  "protocol": "oidc",
  "oidc": {
    "clientId": "your-app-id",
    "clientSecret": "your-client-secret",
    "redirectUri": "https://antragsgruen.example.com/sso-callback",
    "urlAuthorize": "https://login.microsoftonline.com/{tenant-id}/oauth2/v2.0/authorize",
    "urlAccessToken": "https://login.microsoftonline.com/{tenant-id}/oauth2/v2.0/token",
    "urlResourceOwnerDetails": "https://graph.microsoft.com/oidc/userinfo",
    "urlLogout": "https://login.microsoftonline.com/{tenant-id}/oauth2/v2.0/logout",
    "issuer": "https://login.microsoftonline.com/{tenant-id}/v2.0",
    "scopes": ["openid", "profile", "email", "User.Read"]
  },
  "attributeMapping": {
    "email": "email",
    "username": "preferred_username",
    "givenName": "given_name",
    "familyName": "family_name",
    "groups": "groups"
  }
}
```

**Note**: For Azure AD groups, you need to configure group claims in your app registration.

#### Authentik

```json
{
  "protocol": "oidc",
  "oidc": {
    "clientId": "your-client-id",
    "clientSecret": "your-secret",
    "redirectUri": "https://antragsgruen.example.com/sso-callback",
    "urlAuthorize": "https://authentik.example.com/application/o/authorize/",
    "urlAccessToken": "https://authentik.example.com/application/o/token/",
    "urlResourceOwnerDetails": "https://authentik.example.com/application/o/userinfo/",
    "urlLogout": "https://authentik.example.com/application/o/antragsgruen/end-session/",
    "issuer": "https://authentik.example.com/application/o/antragsgruen/",
    "scopes": ["openid", "profile", "email", "groups"],
    "pkce": true
  }
}
```

## Attribute Mapping

The `attributeMapping` section maps IdP attributes to Antragsgrün user fields:

```json
{
  "attributeMapping": {
    "email": "email",           // Required: User's email
    "username": "sub",          // Required: Unique identifier
    "givenName": "given_name",  // Optional: First name
    "familyName": "family_name",// Optional: Last name
    "organization": "company",  // Optional: Organization
    "groups": "groups"          // Optional: Group memberships
  }
}
```

### Supported Target Fields

- `email`: User's email address (required)
- `username`: Unique username/identifier (required, falls back to email)
- `givenName`: First/given name
- `familyName`: Last/family/surname
- `organization`: Organization or company name
- `groups`: Array of group memberships

### Nested Attributes

Use dot notation for nested attributes:

```json
{
  "attributeMapping": {
    "organization": "company.name",
    "groups": "resource_access.antragsgruen.roles"
  }
}
```

## Group Synchronization

### Enable Group Sync

```json
{
  "syncGroups": true,
  "attributeMapping": {
    "groups": "groups"
  }
}
```

### Group Mapping

Map IdP groups to Antragsgrün groups:

```json
{
  "groupMapping": {
    "idp-admin-group": "administrators",
    "idp-user-group": "members",
    "idp-moderator": "moderators"
  }
}
```

### Prerequisites

1. Groups must exist in Antragsgrün with external ID format: `generic-sso-groups:{group-name}`
2. The IdP must send group information in the configured attribute

### Creating Groups in Antragsgrün

Groups are managed per consultation. Create them with the external ID:

```
External ID: generic-sso-groups:administrators
```

## Single Logout (SLO)

Enable SSO logout to log users out from the IdP when they logout from Antragsgrün:

```json
{
  "singleLogout": true
}
```

### OIDC Logout

Requires `urlLogout` configuration in OIDC settings.

### SAML Logout

SAML logout is handled automatically by SimpleSAMLphp if configured in the IdP metadata.

## Security Considerations

### HTTPS Required

Always use HTTPS in production. SSO should never be used over HTTP.

### Client Secret Protection

Keep your client secret secure:
- Never commit `config/generic_sso.json` to version control
- Use environment variables or secure secret management
- Restrict file permissions: `chmod 600 config/generic_sso.json`

### Redirect URI

Ensure your redirect URI exactly matches the configuration in your IdP:
- OIDC: `https://your-domain.com/sso-callback`
- SAML: Configured in SimpleSAMLphp

### PKCE

Enable PKCE for enhanced security (OIDC):

```json
{
  "oidc": {
    "pkce": true
  }
}
```

### Token Validation

The plugin automatically validates:
- OIDC: State parameter, token expiration, issuer, audience
- SAML: Signatures, assertions, response validity

## Troubleshooting

### Enable Debug Logging

Check your PHP error logs for detailed SSO errors:

```bash
tail -f /var/log/php/error.log
```

### Common Issues

#### "Invalid state parameter"
- CSRF protection triggered
- Ensure cookies are enabled
- Check session configuration

#### "Email is required but not provided"
- IdP not sending email attribute
- Check attribute mapping configuration
- Verify IdP scope configuration

#### "Could not create/update user"
- Database constraint violation
- Check user model validation rules
- Review error logs for details

#### OIDC: "Failed to discover OIDC configuration"
- Check issuer URL
- Verify `.well-known/openid-configuration` is accessible
- Check network/firewall settings

#### SAML: "SimpleSAML authentication failed"
- Check SimpleSAMLphp configuration
- Verify SP metadata is registered with IdP
- Review SimpleSAMLphp logs

### Testing Configuration

Test your configuration with different accounts:

1. Admin account (if group sync enabled)
2. Regular user account
3. New user (JIT provisioning)
4. User with groups/roles

## Architecture

### Components

1. **Module.php**: Plugin registration and routing
2. **SsoLogin.php**: Main login provider implementation
3. **OidcProvider.php**: OIDC protocol handler
4. **SamlProvider.php**: SAML protocol handler
5. **LoginController.php**: Login flow controller

### Authentication Flow

#### OIDC Flow

1. User clicks "Login with SSO"
2. Redirect to IdP authorization endpoint
3. User authenticates at IdP
4. IdP redirects to callback with authorization code
5. Exchange code for access token
6. Retrieve user info from userinfo endpoint
7. JIT provision user in Antragsgrün
8. Sync groups (if enabled)
9. Login user and redirect

#### SAML Flow

1. User clicks "Login with SSO"
2. SimpleSAMLphp initiates SAML request
3. User authenticates at IdP
4. IdP returns SAML assertion
5. SimpleSAMLphp validates assertion
6. Extract user attributes
7. JIT provision user in Antragsgrün
8. Sync groups (if enabled)
9. Login user and redirect

## Upgrading

### From Provider-Specific Plugins

If migrating from `gruene_de_saml` or `gruene_ch_saml`:

1. Export existing users (if needed)
2. Install and configure generic_sso plugin
3. Update auth identifiers if necessary
4. Test with a few users before full rollout
5. Disable old plugin

### Breaking Changes

None currently. First release.

## Development

### Adding Custom Attribute Extractors

Extend `SsoLogin::extractUserData()` for custom attribute processing.

### Supporting Additional Protocols

Implement a new provider class similar to `OidcProvider` or `SamlProvider`.

## Support

For issues, please check:

1. This README and configuration examples
2. Antragsgrün documentation
3. Your IdP documentation
4. SimpleSAMLphp documentation (for SAML)

## License

Same as Antragsgrün (likely GPL or similar - check main repository).

## Credits

Created for Antragsgrün to provide flexible, provider-agnostic SSO authentication.

Based on patterns from existing SAML plugins (`gruene_de_saml`, `gruene_ch_saml`).