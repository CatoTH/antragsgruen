# Joulefish Simplify Plugin for Antragsgrün

Integrates the [Joulefish Simplification API](https://simplify-joulefish.fn.deploybase.eu) into Antragsgrün to provide accessible language versions of motions:
- **Einfache Sprache** (Plain Language, DIN ISO 24495-1 / DIN 8581-1)
- **Leichte Sprache** (Easy-to-Read Language, DIN SPEC 33429:2025-03)

## Features

- **Inline Motion View Widget**: Renders a dedicated "Vereinfachte Sprache" card above motion texts.
- **On-Demand Simplification**: Readers can choose between Plain Language and Easy Language with one click.
- **Smart Caching**: Simplified texts are cached within motion metadata (`extraData`) using a SHA-1 content hash. Multiple readers get instant responses without incurring duplicate API calls. When a motion's text is modified, the cache invalidates automatically.

## Activation

### Option 1: Via `config.json`

Add `"joulefish_simplify"` to the `"plugins"` array:

```json
{
    "plugins": [
        "joulefish_simplify"
    ]
}
```

### Option 2: Via Docker Environment Variable

In `docker-compose.yml` or `.env`:

```yaml
environment:
  - PLUGINS=joulefish_simplify
```

## Configuration

Configuration values are resolved in the following priority order:

1. **Environment Variables**:
   - `JOULEFISH_API_URL`: Custom API endpoint (defaults to `https://simplify-joulefish.fn.deploybase.eu`)
   - `JOULEFISH_API_KEY`: API authentication key (optional Bearer token)

2. **Local Configuration File (`plugins/joulefish_simplify/config.json`)**:
   ```json
   {
       "apiUrl": "https://simplify-joulefish.fn.deploybase.eu",
       "apiKey": "your-api-key-here"
   }
   ```

3. **Built-in Defaults**:
   Uses `https://simplify-joulefish.fn.deploybase.eu` with public anonymous access.

## Architecture

- `Module.php`: Plugin entry point extending `app\plugins\ModuleBase`. Registers URL routes and layout hooks.
- `LayoutHooks.php`: Hooks into `beforeMotionView()` to inject the frontend widget above the motion content.
- `controllers/SimplifyController.php`: Handles AJAX requests (`/joulefish_simplify/simplify/process`), fetches motion section text, queries the API using Guzzle, and manages cache persistence.
- `views/motion_hook.php`: HTML & JavaScript component rendering the UI and handling button interactions.

