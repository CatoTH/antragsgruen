# Claude Translation Plugin for Antragsgrün

Auto-translates empty motion/amendment sections using Claude (Anthropic's API), so submitters only
need to fill in one language and other configured languages get filled in automatically. This is one
possible implementation of Antragsgrün's generic **section-autofill** mechanism - see
[Multi-language Support](../../README.md#multi-language-support) in the main README for the
user-facing feature and [`ModuleBase::fillEmptyMotionSectionsContent()`](../ModuleBase.php) for the
extension point other plugins could implement instead (or in addition).

Translation is entirely optional: without a configured API key the plugin does nothing, and
multi-language motions work fine with sections simply staying empty until a human fills them in.

## How it works

- Whenever a motion or amendment is saved with empty sections (because the site has multiple
  languages configured and the submitter only wrote one of them), `SectionAutofill` asks every active
  plugin - including this one - whether it can fill them.
- This plugin collects the still-empty sections of a single motion/amendment and sends **one batched
  request** to Claude for all of them (not one request per section/language), using
  [tool use](https://docs.anthropic.com/en/docs/build-with-claude/tool-use) to get back a reliably
  structured `sectionId => translated text` response.
- The source text is the same section's content in whichever other language of that motion/amendment
  is already filled in (preferring the motion/amendment's primary language).
- For amendments, the prompt is additionally instructed to stick as closely as possible to the
  amendment's own translated motion text, to keep the diff between motion and amendment minimal in
  every language, not just the submitter's.
- Content is HTML. The prompt instructs Claude to only translate visible text and leave tags,
  attributes, CSS classes and surrounding whitespace untouched.
- Filled-in sections are marked as auto-generated (the admin update view shows a hint above them,
  see `HTMLTools::getSectionAutofillHint()`); the marker disappears as soon as a human edits the text.
- If credentials are missing, incomplete, or the API call fails for any reason, the plugin simply
  leaves the affected sections empty - it never blocks or breaks a motion/amendment save.

## Setup

### 1. Enable the plugin

Add `translation_claude` to the `plugins` array in `config/config.json`:

```json
{
  "plugins": [
    "translation_claude"
  ]
}
```

### 2. Configure your Claude API key

Copy the example credentials file:

```bash
cp plugins/translation_claude/credentials.example.json plugins/translation_claude/credentials.json
```

Edit `plugins/translation_claude/credentials.json`:

```json
{
    "apiKey": "sk-ant-...",
    "model": "claude-sonnet-5"
}
```

- `apiKey` (required) - your Anthropic API key. Get one at
  [console.anthropic.com](https://console.anthropic.com/).
- `model` (optional) - defaults to `claude-sonnet-5` if omitted or empty.

`credentials.json` is gitignored (see `plugins/translation_claude/.gitignore`) and must never be
committed. Without it (or with an empty `apiKey`), the plugin stays inactive.

### 3. (Optional) Run translation as a background job

By default, translation happens synchronously while a motion/amendment is saved, which adds the
Claude API round-trip to the save request's response time. If you have
[background job processing](../../README.md#enable-background-job-processing) enabled, set
`sectionAutofill` to `true` in `config.json`'s `backgroundJobs` block (or set the
`BACKGROUND_JOBS_SECTION_AUTOFILL` environment variable) so translations happen asynchronously
instead:

```json
{
  "backgroundJobs": {
    "sectionAutofill": true
  }
}
```

## Logging

Every request/response is logged (regardless of success or failure) to `runtime/logs/claude.log` in
JSON-lines format, one line per API call, including timing and token usage - useful for auditing cost
and behavior. Logging never throws and never blocks translation, even if the log file can't be
written.

## License

AGPL-3.0-only (same as Antragsgrün)
