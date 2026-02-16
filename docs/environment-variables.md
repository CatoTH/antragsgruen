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
| `MULTISITE_MODE` | false | Enable multisite mode |
| `SITE_SUBDOMAIN` | | If multisite=false, this refers to the one subdomain |
| `BASE_LANGUAGE` | en | Base language (en, de, fr, etc.) |
| `RANDOM_SEED` | - | **Required!** Security seed: `openssl rand -base64 32` |
| `MAIL_FROM_EMAIL` | - | Default from email |
| `MAIL_FROM_NAME` | Antragsgrün | Default from name |

## Optional Tool Paths

| Variable | Description |
|----------|-------------|
| `IMAGE_MAGICK_PATH` | Path to ImageMagick convert binary |
| `WEASYPRINT_PATH` | Path to WeasyPrint binary |
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

## Backwards Compatibility

Existing `config.json` files continue to work without changes. Environment variables are only used as fallback when values are not present in `config.json`.

## Implementation Details

See `models/settings/EnvironmentConfigLoader.php` for the implementation.
