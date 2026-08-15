# Environment Variable Configuration

Antragsgrün supports configuration via environment variables following the [12-factor app methodology](https://12factor.net/config). This is the recommended approach for containerized deployments.

## Quick Start

Set environment variables instead of creating `config/config.json`:

```bash
export DB_HOST=localhost
export DB_NAME=antragsgruen
export DB_USER=antragsgruen
export DB_PASSWORD=secret
export RANDOM_SEED=$(openssl rand -base64 32)
```

## Configuration Precedence

1. **config.json** (highest priority - existing installations continue to work)
2. **Environment variables** (fallback - used if not in config.json)
3. **Installer defaults** (lowest - used when no config at all)

## Database Configuration

| Variable | Required | Default | Description |
|----------|----------|---------|-------------|
| `DB_HOST` | Yes | - | Database hostname |
| `DB_NAME` | Yes | - | Database name |
| `DB_USER` | Yes | - | Database username |
| `DB_PASSWORD` | No | empty | Database password |
| `DB_PORT` | No | 3306 | Database port |
| `DB_CHARSET` | No | utf8mb4 | Character set |
| `TABLE_PREFIX` or `DB_TABLE_PREFIX` | No | empty | Database table prefix |

## Redis Configuration

| Variable | Required | Default | Description |
|----------|----------|---------|-------------|
| `REDIS_HOST` | Yes* | - | Redis hostname (*required to enable Redis) |
| `REDIS_PORT` | No | 6379 | Redis port |
| `REDIS_DB` | No | 0 | Redis database number |
| `REDIS_PASSWORD` | No | - | Redis password |

## Mail Configuration

### Disabling E-Mail Sending

| Variable | Default | Description |
|----------|---------|-------------|
| `MAILER_DISABLED` | false | Set to true to disable e-mail delivery |

### Option 1: Symfony Mailer DSN (Recommended)

| Variable | Description |
|----------|-------------|
| `MAILER_DSN` | Full DSN: `smtp://user:pass@host:587` |

### Option 2: Individual SMTP Settings

| Variable | Default | Description |
|----------|---------|-------------|
| `SMTP_HOST` | - | SMTP hostname (required) |
| `SMTP_PORT` | 587 | SMTP port |
| `SMTP_USERNAME` | - | SMTP username |
| `SMTP_PASSWORD` | - | SMTP password |
| `SMTP_ENCRYPTION` | tls | Encryption: tls, ssl, or empty |

## Application Configuration

| Variable | Default | Description |
|----------|---------|-------------|
| `APP_DOMAIN` | - | Domain name (e.g., motion.tools) |
| `APP_PROTOCOL` | https | Protocol: http or https |
| `RESOURCE_BASE` | Base path for static assets (local or CDN) |
| `MULTISITE_MODE` | false | Enable multisite mode |
| `SITE_SUBDOMAIN` | | If multisite=false, this refers to the one subdomain |
| `BASE_LANGUAGE` | en | Base language (en, de, fr, etc.) |
| `RANDOM_SEED` | - | **Required!** Security seed: `openssl rand -base64 32` |
| `MAIL_FROM_EMAIL` | - | Default from email |
| `MAIL_FROM_NAME` | Antragsgrün | Default from name |
| `TRUSTED_PROXIES` | - | Comma-separated trusted proxy IPs or CIDR ranges (e.g., `10.0.0.0/8,172.16.0.0/12`). Added to the default trusted hosts. |
| `BACKGROUND_JOBS_NOTIFICATIONS` | false | Enable asynchronous background jobs for notifications |
| `BACKGROUND_JOBS_SECTION_AUTOFILL` | false | Enable asynchronous background jobs for auto-filling empty motion/amendment sections (e.g. translation, see [Multi-language Support](../README.md#multi-language-support)) |
| `HEALTH_CHECK_KEY` | - | API key hash for background job health checks |
| `JS_ERROR_TRACKING` | - | Target for JS Error Tracking (e.g. file:///tmp/js_errors.log or otel://) |
| `JWT_PUBLIC_KEY` | - | Either link to public key (file:///secret/public.pem), or content of public key |
| `JWT_PRIVATE_KEY` | - | Either link to private key (file:///secret/private.pem), or content of private key |
| `PLUGINS` | - | Comma-separated list of plugins to enable (e.g. `generic_sso`) |
| `ADMIN_USER_IDS` | - | Comma-separated list of user IDs with superuser permissions |

## Automatic Initialization

With `AUTO_INIT` enabled, the container entrypoint brings the installation up to what the
environment describes before starting the web server: it creates the database schema if the
database is empty, and the first site if `SITE_ADMIN_EMAIL` is set. Both steps are skipped
when they are already done, so this is safe on every restart.

Only *missing* things are created. An existing schema is never migrated or altered, so
upgrades remain an explicit step.

| Variable | Default | Description |
|----------|---------|-------------|
| `AUTO_INIT` | false | Create the schema and the first site on startup |
| `SITE_ADMIN_EMAIL` | - | E-mail of the first administrator. Without it, only the schema is created |
| `SITE_TITLE` | - | **Required** with `SITE_ADMIN_EMAIL`. Name of the site |
| `SITE_CONTACT` | - | **Required** with `SITE_ADMIN_EMAIL`. Imprint / contact details |
| `SITE_ADMIN_PASSWORD` | - | If unset, a password is generated and written to the container log |
| `SITE_ADMIN_GIVEN_NAME` | - | Given name of the administrator |
| `SITE_ADMIN_FAMILY_NAME` | - | Family name of the administrator |
| `SITE_FORCE_PASSWORD_CHANGE` | true, unless `SITE_ADMIN_PASSWORD` is set | Require a password change on first login |
| `SITE_ORGANIZATION` | - | Organization the site belongs to |
| `SITE_LANGUAGE` | `BASE_LANGUAGE` | Language of the site's wording |
| `SITE_FUNCTIONALITY` | 1 | Comma-separated feature codes: 1=motions, 2=manifesto, 3=applications, 4=agenda, 5=speech lists, 6=statute amendments, 7=votings, 8=documents |
| `SITE_LOGIN_METHODS` | site default | Comma-separated login methods: 0=standard, 1=Grünes Netz, 3=external (SSO), 4=OpenSlides |

The subdomain of the site comes from `SITE_SUBDOMAIN` (defaulting to `std`). In multisite
mode there is no default, and sites are created with `./yii site/create` instead.

The administrator created here manages its own site through the regular user group.
Superuser permissions across all sites are granted with `ADMIN_USER_IDS`, since they are
stored in `config.json`, which this setup is designed not to need.

The same steps are available as commands, if you would rather run them yourself:

```bash
./yii database/init                    # create the schema if the database is empty
./yii site/init admin@example.org \    # create the site if it does not exist
    --title="My Organization" \
    --contact="My Org, Foo Street 1, foo@example.org"
```

## Single Sign-On (generic_sso plugin)

Requires `PLUGINS` to include `generic_sso`. These variables are the equivalent of
`config/generic_sso.json`; where both exist, the file takes precedence.

| Variable | Default | Description |
|----------|---------|-------------|
| `SSO_ENABLED` | false | Enables SSO. If unset, the plugin is configured from `config/generic_sso.json` only |
| `SSO_PROTOCOL` | oidc | `oidc` or `saml` |
| `SSO_PROVIDER_ID` | generic-sso | Identifier of the login provider |
| `SSO_SINGLE_LOGOUT` | false | Also log out at the identity provider |
| `SSO_SYNC_GROUPS` | false | Synchronize user groups from the identity provider |
| `SSO_LINK_BY_EMAIL` | false | Link an existing local account on first SSO login, unless the provider reports `email_verified: false`. Only enable this if the provider verifies e-mail addresses |

### OIDC

| Variable | Default | Description |
|----------|---------|-------------|
| `SSO_OIDC_ISSUER` | - | Issuer URL, used for discovery and to validate the `iss` claim |
| `SSO_OIDC_CLIENT_ID` | - | OAuth2 client ID |
| `SSO_OIDC_CLIENT_SECRET` | - | OAuth2 client secret |
| `SSO_OIDC_DISCOVERY` | true | Fetch endpoints from `$SSO_OIDC_ISSUER/.well-known/openid-configuration`. Explicitly set endpoints are never overwritten. The result is cached for 24 hours |
| `SSO_OIDC_REDIRECT_URI` | auto | Defaults to the `/sso-callback` route |
| `SSO_OIDC_URL_AUTHORIZE` | discovered | Authorization endpoint |
| `SSO_OIDC_URL_ACCESS_TOKEN` | discovered | Token endpoint |
| `SSO_OIDC_URL_USER_INFO` | discovered | Userinfo endpoint |
| `SSO_OIDC_URL_LOGOUT` | discovered | End session endpoint, used by `SSO_SINGLE_LOGOUT` |
| `SSO_OIDC_SCOPES` | openid,profile,email | Comma-separated scopes |
| `SSO_OIDC_PKCE` | false | Use PKCE (S256) |
| `SSO_OIDC_AUTHORIZATION_PARAMS` | - | JSON object of extra authorization request parameters, e.g. `{"prompt":"consent"}` |

### SAML

Only OIDC is configurable through the environment. With `SSO_PROTOCOL=saml` the
protocol-specific settings are still read from `config/generic_sso.json`, since
SimpleSAMLphp has to be installed and configured outside the application anyway.

### Attribute mapping

Defaults depend on the protocol; only set what differs from your provider.

| Variable | OIDC default | SAML default |
|----------|--------------|--------------|
| `SSO_ATTR_EMAIL` | email | mail |
| `SSO_ATTR_USERNAME` | preferred_username | uid |
| `SSO_ATTR_GIVEN_NAME` | given_name | givenName |
| `SSO_ATTR_FAMILY_NAME` | family_name | sn |
| `SSO_ATTR_ORGANIZATION` | organization | o |
| `SSO_ATTR_GROUPS` | groups | memberOf |
| `SSO_GROUP_MAPPING` | - | JSON object mapping provider group names to Antragsgrün group names, e.g. `{"admins":"Admins"}` |

Enabling SSO on a site additionally requires the site to allow external logins:

```bash
./yii site/set-login-methods std 0,3
```

## Optional Tool Paths

| Variable | Description |
|----------|-------------|
| `IMAGE_MAGICK_PATH` | Path to ImageMagick convert binary |
| `WEASYPRINT_PATH` | Path to WeasyPrint binary |
| `QPDF_PATH` | Path to QPDF binary |
| `LUALATEX_PATH` | Path to LuaLaTeX binary |

## Docker Example

```bash
docker run \
  -e DB_HOST=db \
  -e DB_NAME=antragsgruen \
  -e DB_USER=antragsgruen \
  -e DB_PASSWORD=secret \
  -e REDIS_HOST=redis \
  -e MAILER_DSN=smtp://smtp.example.com:587 \
  -e APP_DOMAIN=motion.tools \
  -e RANDOM_SEED=$(openssl rand -base64 32) \
  antragsgruen:latest
```

## Kubernetes Example

```yaml
apiVersion: v1
kind: ConfigMap
metadata:
  name: antragsgruen-config
data:
  DB_HOST: "mariadb"
  DB_NAME: "antragsgruen"
  APP_DOMAIN: "motion.tools"
  BASE_LANGUAGE: "en"
---
apiVersion: v1
kind: Secret
metadata:
  name: antragsgruen-secrets
stringData:
  DB_PASSWORD: "changeme"
  RANDOM_SEED: "generate-with-openssl-rand"
  MAILER_DSN: "smtp://user:pass@smtp.example.com:587"
```

## Pure Environment Variable Deployment

For containerized deployments where no `config.json` file exists, Antragsgrün can be fully configured via environment variables. This is ideal for Docker Compose, Kubernetes, or other orchestration platforms.

```yaml
# docker-compose.yml
version: '3.8'

services:
  app:
    image: antragsgruen:latest
    environment:
      # Database
      DB_HOST: db
      DB_NAME: antragsgruen
      DB_USER: antragsgruen
      DB_PASSWORD: secret
      # Application
      APP_DOMAIN: motion.example.com
      APP_PROTOCOL: https
      RANDOM_SEED: "change-me-to-a-random-string"
      BASE_LANGUAGE: en
      # Mail (DSN format)
      MAILER_DSN: "smtp://user:pass@smtp.example.com:587"
      MAIL_FROM_EMAIL: noreply@example.com
      MAIL_FROM_NAME: "Motion Tools"
      # Multisite (optional)
      MULTISITE_MODE: "true"
      SITE_SUBDOMAIN: std
      # Bootstrap an empty database into a running site on first start
      AUTO_INIT: "true"
      SITE_ADMIN_EMAIL: admin@example.com
      SITE_ADMIN_PASSWORD: change-me
      SITE_TITLE: "Motion Tools"
      SITE_CONTACT: "Example Org, Foo Street 1, foo@example.com"
    ports:
      - "8080:80"
    depends_on:
      - db

  db:
    image: mariadb:11
    environment:
      MYSQL_ROOT_PASSWORD: rootsecret
      MYSQL_DATABASE: antragsgruen
      MYSQL_USER: antragsgruen
      MYSQL_PASSWORD: secret
    volumes:
      - db_data:/var/lib/mysql

volumes:
  db_data:
```

When no `config.json` is present, the application will:
1. Read all configuration from environment variables
2. Skip the installation wizard if required variables (`DB_HOST`, `DB_NAME`, `DB_USER`, `RANDOM_SEED`) are set
3. Auto-configure mail delivery from `MAILER_DSN` or individual `SMTP_*` variables
4. Create the schema and the first site on startup, if `AUTO_INIT` is enabled

## Backwards Compatibility

Existing `config.json` files continue to work without changes. Environment variables are only used as fallback when values are not present in `config.json`.

## Implementation Details

See `models/settings/EnvironmentConfigLoader.php` for the implementation.
