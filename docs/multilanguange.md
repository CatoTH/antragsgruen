# Multi-language Support — Technical Overview

Antragsgrün supports two related but independent capabilities on top of the pre-existing
`consultation.wordingBase` UI-translation mechanism:

1. **Readers can browse the site in any of several languages**, chosen via a picker and remembered
   for their session.
2. **Motions and amendments can hold their actual content — titles, texts, reasons — in several
   languages at once**, by defining several parallel sections per motion type, one per language.

This is deliberately distinct from [custom language variants](../README.md#custom-language-variants-as-plugin)
and the per-consultation wording-override mechanism (`ConsultationText`, the "Translation" admin
page): those translate Antragsgrün's own UI strings (buttons, labels, e-mails). This feature
translates the *content that submitters write*.

Everything below is opt-in and backwards compatible: a site with fewer than two entries in
`site.settings.supportedLanguages` behaves exactly as it did before this feature existed — no
picker, no language dropdowns in the motion type admin, no behavioral change anywhere.

## Core concepts

- **Site-level supported languages.** `supportedLanguages` lives on `models/settings/Site.php`, not
  on the consultation — multi-language is a site-wide capability, configured once and available to
  every consultation on that site (multisite installations can still have some sites be
  single-language and others multi-language).
- **Primary language.** Derived from `consultation.wordingBase` (e.g. `de-parteitag` → `de`), not a
  separate setting. Content without an explicit language, and the motion's canonical `title` DB
  column, are anchored to this language.
- **Reader language.** Session-only (`LanguageTools::SESSION_KEY = 'userLanguage'`), seeded from the
  browser's `Accept-Language` header, overwritten by the picker. Not persisted to the user account or
  across devices.
- **Section language + grouping.** Each `ConsultationSettingsMotionSection` can be tagged with a
  `language` (`null` = valid for every language) and a free-text `languageGrouping` identifier.
  Sections sharing a non-empty grouping are treated as translations of each other; a motion/amendment
  then has one `MotionSection`/`AmendmentSection` row per language within that group.

## Data model

No database migration was needed — both new settings live in existing JSON `settings` columns, which
`JsonConfigTrait` reads/writes tolerantly (absent keys default cleanly).

```php
// models/settings/Site.php
/** @var string[] e.g. ['de', 'en', 'fr']; empty or single entry = single-language site */
public array $supportedLanguages = [];
```

```php
// models/settings/MotionSection.php
public ?string $language = null;         // null = valid for all languages (default)
public ?string $languageGrouping = null; // free-text id; sections sharing it are translations of each other
```

Accessors on `models/db/ConsultationSettingsMotionSection.php`:

```php
public function getLanguage(): ?string
public function getLanguageGrouping(): ?string
public function matchesLanguage(string $language): bool  // language === null || language === $language
```

Accessors on `models/db/ConsultationMotionType.php`:

```php
/** distinct non-null languages used by this type's sections */
public function getDefinedSectionLanguages(): array
/** true if the type defines no languages at all (available everywhere), or defines the given one */
public function isAvailableInLanguage(string $language): bool
/** sections with language === null || language === $language */
public function getMotionSectionsForLanguage(string $language): array
/** non-blocking admin hints: a language without a grouping, a duplicate language in one grouping, a grouping mixing section types */
public function getLanguageSetupWarnings(): array
```

## Language resolution

`components/LanguageTools.php` is the single source of truth for everything language-related:

```php
final class LanguageTools
{
    public const SESSION_KEY = 'userLanguage';

    public static function getSupportedLanguages(?Site $site = null): array;
    public static function isMultiLanguageSite(?Site $site = null): bool;         // count >= 2
    public static function getPrimaryLanguage(?Consultation $con = null): string;
    public static function getCurrentLanguage(): string;                          // memoized per request
    public static function setCurrentLanguage(string $language): bool;            // false if unsupported
    public static function getLanguageName(string $language): string;             // target-language endonym, e.g. "English"
    public static function getLanguageIcon(string $language): string;             // flag emoji, from that language's own messages
    public static function getContentLanguages(?Consultation $consultation): array;
    public static function renderInLanguage(?Consultation $con, string $language, callable $cb): mixed;
    public static function resetRequestCache(): void;

    // Pure helpers the above wrap; unit-testable without a database
    public static function filterSupportedLanguages(array $languages): array;
    public static function resolvePrimaryLanguage(?string $wordingBase): string;
    public static function matchBrowserLanguage(array $acceptable, array $supported): ?string;
}
```

`getCurrentLanguage()` resolution order:

1. per-request memo (only set once the site is known, so an early call can't poison the rest of the
   request);
2. the session value, if it's still in `supportedLanguages`;
3. Yii's `Accept-Language` handling (`Request::getPreferredLanguage()`) — the result is written back
   to the session so it stays stable for the rest of the visit;
4. the current consultation's primary language;
5. the base of `AntragsgruenApp::$baseLanguage`, else `'en'`.

Safe in console and REST contexts (no session): guarded with
`\Yii::$app instanceof \yii\web\Application`, which skips steps 2–3 and returns the primary language.

**Language icons** are not a hard-coded PHP map: each language defines its own flag emoji as a
`language_icon` message in its own `messages/<language>/base.php`, resolved with that language as an
explicit `\Yii::t()` override rather than the ambient current language — the icon is the same symbol
no matter who's looking at it. Catalan has no dedicated national flag and uses a neutral 🏳️
placeholder.

### Wiring into the framework

| Location | What it does |
|---|---|
| `models/settings/Layout::setConsultation()` | Sets `\Yii::$app->language` to the reader's language. When it equals the primary language, keeps the full `wordingBase`-derived variant; otherwise uses the plain base language — wording variants only ever apply in the primary language. |
| `models/settings/Layout::getHTMLLanguageCode()` | Drives `<html lang="…">`. |
| `components/yii/MessageSource::loadMessages()` | For a non-primary language: loads `messages/<language>/` only (no `wordingBase` variant merging) and skips `ConsultationText` string overrides, so a primary-language admin customization can't leak into a translated UI. |
| `components/Tools::getCurrentDateLocale()` | Date formatting follows the reader's language (an explicit `consultation.dateFormat` still wins). |
| `components/StaticResourceTools` | Picks the JS translation bundle for the reader's language. |

## Admin configuration

### Site-level supported languages

Configured on **Consultation Settings** (`views/admin/index/consultation_settings.php`,
`IndexController::actionConsultation()`), gated by `PRIVILEGE_SITE_ADMIN` since it's a site-wide, not
per-consultation, setting. A checkbox list over `MessageSource::getBaseLanguages()`, submitted as
`siteSettings[supportedLanguages][]` through the page's existing generic site-settings save path — no
dedicated controller code was needed. A purely client-side "activate multi-language support" checkbox
toggles the fieldset's visibility (progressive enhancement: without JS everything just shows).

### Motion type sections

`views/admin/motion-type/_sections.php` gains, only when the site supports more than one language, a
language dropdown (`— all languages —` plus each supported language) and a free-text grouping input
next to each section's title field. Persisted via
`ConsultationSettingsMotionSection::setAdminAttributes()`; an unsupported/stale submitted language is
silently reset to `null` rather than raising a form error. The admin motion-type page also surfaces
`getLanguageSetupWarnings()` as non-blocking hints above the section list.

### Motion type templates

The built-in templates (Motion, Manifesto, Application, PDFApplication, Statutes, ProgressReport,
`models/motionTypeTemplates/*`) create one section per language, correctly grouped, when the target
site has several supported languages — via a shared `SectionTemplateBuilder`
(`models/motionTypeTemplates/SectionTemplateBuilder.php`). Only *text-content* section types are
duplicated per language (`TYPE_TITLE`, `TYPE_TEXT_SIMPLE`, `TYPE_TEXT_EDITORIAL`); upload/structured
types (`TYPE_IMAGE`, `TYPE_TABULAR`, `TYPE_PDF_ALTERNATIVE`) stay a single, language-neutral section
regardless of how many languages the site supports. The consultation's primary language is sorted
first, and each generated section's own admin-facing label (e.g. "Begründung"/"Reason") is resolved
in *that section's own language* via `\Yii::t()`'s explicit language override, not the language the
admin creating the type happens to be browsing in.

### Motion type labels (`titleSingular`/`titlePlural`/`createTitle`)

Unlike section content, a motion type's own labels are plain DB columns — one value per type, not one
row per language. These can be translated per language too, stored separately from the DB columns:

```php
// models/settings/MotionType.php
/**
 * @var array<string, array{titleSingular?: string, titlePlural?: string, createTitle?: string}>
 */
public array $labelTranslations = [];
```

The consultation's primary language is never a key here — it's always represented by the DB columns
themselves. Resolution, on `ConsultationMotionType`:

```php
public function getTitleSingularForDisplay(?string $language = null): string
public function getTitlePluralForDisplay(?string $language = null): string
public function getCreateTitleForDisplay(?string $language = null): string
// non-falling-back accessor, used only to prefill the admin form:
public function getLabelTranslation(string $language, string $field): string
public function setLabelTranslations(array $translations): void
```

`$language` defaults to the reader's current language; the primary language, or any language without
a (complete) override, falls back to the DB column.

Admin UI: a "Multi-language labels" section (`views/admin/motion-type/_labelTranslations.php`) on the
motion type's own edit page, right after "Names" — hidden entirely on a single-language site. One
`titleSingular`/`titlePlural`/`createTitle` triplet per non-primary supported language, each
placeholder'd with the main-language value. Posted as a separate `labelTranslations[language][field]`
field, saved by `MotionTypeController::labelTranslationsSave()`. This is admin-web-UI only — not
exposed through the REST API, since the shared `MotionTypeUpdateRequest` DTO is generated from
`docs/openapi.yaml`.

Every reader-facing call site that used to read `$motionType->titleSingular` etc. directly (motion and
amendment views, create/edit forms, breadcrumbs, the sidebar, PDF exports/layouts, the site-search
result label) was switched to the `*ForDisplay()` methods. Admin backend views/controllers
deliberately keep reading the raw DB columns — admins configure and triage in the consultation's
primary language regardless of which language they're browsing the backend in. The two
submitted-motion notification e-mails and the Discourse-forum integration are also deliberately left
on the primary-language value: a notification/forum post can be triggered by someone other than the
recipient (e.g. an admin publishing on a submitter's behalf), so there is no single reader whose
current browsing language would be the "right" one to pick.

## Language picker

- `UserController::actionSetlanguage()` validates the `language` GET parameter against the site's
  supported list, calls `LanguageTools::setCurrentLanguage()`, and 302s to `backUrl` (open-redirect
  protection already exists centrally in `RedirectResponse::sanitizeRedirect()`).
- Rendered in `models/layoutHooks/StdHooks::getStdNavbarHeader()`, behind
  `LanguageTools::isMultiLanguageSite()`, and exposed as `Layout::getStdNavbarHeader()` so themed
  layouts (`gruen_ci`, `green_layout`) inherit it automatically.
- Markup: one `<li class="languagePicker languagePicker<code>">` per language *other* than the
  current one, the visible link text being that language's flag icon
  (`LanguageTools::getLanguageIcon()`), with `lang`/`hreflang`/`rel="nofollow"` and an
  `aria-label`/`title` tooltip phrased in the *current* UI language ("Show this site in %LANGUAGE%",
  `%LANGUAGE%` being the target language's own endonym). The picker always returns to the current URL.

## Reading path: which sections are shown

The chokepoint is `IMotion::getSortedSections()`:

```php
public function getSortedSections(
    bool $withoutTitle = false,
    bool $includeNonPublicIfPossible = false,
    SectionLanguageMode $languageMode = SectionLanguageMode::ReaderLanguage,
): array
```

`models/sectionTypes/SectionLanguageMode.php` is a two-case enum:

- `ReaderLanguage` (default): the section list is passed through
  `MotionSectionLanguageFilter::filter($sections, LanguageTools::getCurrentLanguage())`.
- `AllLanguages`: every section is returned regardless of language — used everywhere the caller must
  see every language version at once: admin editing, merging, REST API reads, GDPR user-data exports,
  notification e-mails (no single "correct" recipient language), bulk admin exports/lists, and any
  method that looks a section up by some computed property rather than by ID.

`getActiveSections()` itself is **not** filtered — it feeds amendment rewriting, merging and diff
logic that must see every section regardless of language; only `getSortedSections()`, which wraps it,
applies the filter.

### Filtering logic (`components/MotionSectionLanguageFilter.php`)

A pure, side-effect-free filter:

1. Sections with `language === null` are always kept (valid for every language).
2. The remaining sections are grouped by `languageGrouping` (a section with a language but no
   grouping forms its own single-member group).
3. Within a group: if a section matching the reader's language exists **and has content**, keep only
   that one.
4. Otherwise, keep every section in the group that has content — the *reading views* decide whether
   to show a "not available in your language, showing X instead" disclaimer, via
   `needsLanguageLabel()`; the filter itself doesn't store that decision anywhere.
5. If nothing in the group has content anywhere, keep the reader's own (empty) section — matches how
   a single-language site already renders an unfilled section.
6. If the group has content, but none of it is in the reader's language and the reader's language
   isn't even part of the group, nothing is shown for that group.
7. Ordering is untouched by the filter; the caller always re-sorts by the motion type's section order
   afterwards.

"Has content" is `IMotionSection::hasContentForFiltering()`, which defaults to `!isEmpty()` but is
overridden on `AmendmentSection`: an amendment section is *always* pre-filled with the motion's
original text (even where the amendment proposes no change there), so raw non-emptiness would make
every language look like it had content. The override instead compares against
`getOriginalMotionSection()->getData()` — content only counts if the amendment actually changed it.
This same override is reused by the section-autofill mechanism (below) and by `SectionAutofill`'s
"is this section empty" check.

### Labelling and disclaimers

Derived, not stored — no extra state on section objects:

```php
// on IMotionSection
public function getDisplayLanguage(): ?string;
public function needsLanguageLabel(?string $readerLanguage = null): bool; // language !== null && !== reader
```

`components/HTMLTools::getSectionLanguageHint(IMotionSection $section): string` renders the
`.alertLanguageFallback` disclaimer (empty string if no label is needed), using the
`structure.section_lang_fallback_hint` message ("This content has not been translated into your
language yet. Showing the %LANGUAGE% version instead."). Wired into every view that renders section
content for reading: the main cached motion body, the plain-HTML/PDF-adjacent motion view, the motion
title heading (via `getTitleSectionForDisplay()`), and the amendment diff/text views (wrapping the
pre-built per-section-type HTML rather than touching each section type's renderer individually).

### Title resolution

A motion type can have several title sections (one per language), so `IMotion::getTitleSection()`
needed a real rewrite, not just a new parameter:

```php
public function getTitleSection(?string $language = null): ?IMotionSection
public function getTitleSectionForDisplay(?string $language = null): ?IMotionSection
```

- With no argument (the *canonical* resolution, also what `Motion::refreshTitle()` writes into the
  `motion.title` DB column and what slug generation is based on): the primary language's title
  section is returned if it actually has content; otherwise resolution falls through to the first
  non-empty title section of *any* language; otherwise the primary section (or the first one found),
  fully empty. This fallback-across-languages step is what makes a motion submitted entirely in a
  non-primary language still get a real (non-blank) canonical title and a readable URL slug, instead
  of silently staying blank just because the primary-language section technically exists but has no
  content.
- With a specific language: an exact match if it has content, else a language-neutral section, else
  the canonical resolution above.
- `getTitleSectionForDisplay(?string $language = null)` is the section actually used for display: the
  exact match for the given (or reader's current) language if it has content, else the canonical
  fallback — exposed separately from `getTitleForDisplay()` so a view can call `needsLanguageLabel()`
  on the *exact* section being shown.

`Motion::getTitleForDisplay(?string $language = null): string` (and its `*WithIntro`/`*WithPrefix`
siblings) live on `Motion`, not `IMotion`, since they fall back to `$this->title`, a real DB column
that only `Motion` has (`Amendment`'s title is always computed from its parent motion's title, never
per-language).

Breadcrumbs, the sidebar, tag-list teasers, and an amendment's own title (`Amendment::getTitle()`,
always derived from the parent motion's *canonical* title) deliberately stay on the canonical/primary
value rather than the reader's language — they're secondary/summary surfaces, not the primary reading
experience. Slugs, export filenames, and notification subjects likewise stay on the canonical title,
so they remain stable and language-independent.

## Write path: motion and amendment creation/editing

`models/forms/MotionEditForm.php` / `models/forms/AmendmentEditForm.php` keep building **every**
section definition unfiltered internally — filtering only happens when deciding what to *render*:

```php
public readonly string $formLanguage;      // LanguageTools::getCurrentLanguage() at construction
public function getSectionsToRender(): array;  // adminMode ? all sections : reader's language + language-neutral
```

`views/motion/edit_form.php` / `views/amendment/edit_form.php` iterate `getSectionsToRender()`
instead of the raw section list; the admin update views keep rendering every section, since admin
mode is unaffected. A submitter therefore only ever sees, and can edit, their own current language's
fields — other languages' sections are simply never part of the request, so they're saved unchanged
(`setAndVerifySectionContent()` already skips any section absent from the submitted data, and
required-field validation correctly only applies to what was actually rendered).

For amendments specifically, this needed no extra code: `AmendmentEditForm` pre-fills every amendable
section with the motion's current text and never wipes that array between construction and saving, so
an unsubmitted (other-language) section's pre-filled content survives untouched — "hide a language's
fields" and "no change to that language" are the same thing for free.

### Motion type availability

A motion type that defines no language-specific sections at all is available in every language;
otherwise it's only available in the languages it actually defines sections for
(`ConsultationMotionType::isAvailableInLanguage()`). This gates:

- motion creation (`MotionEditForm::getMotionTypeForCreate()` throws, and
  `ConsultationMotionType::mayCreateIMotion()` returns `false`, if the type isn't available in the
  reader's language);
- the sidebar's own "create" button/list computation;
- amendment creation, centralized in `Permissions::isCurrentlyAmendable()`, inside the same bypass
  block admins already use to ignore deadlines — an admin can propose an amendment in an
  otherwise-unavailable language, since (unlike motion creation) the amendment form never wipes
  pre-filled content, so nothing breaks if they do.

Admin-side creation (the admin motion-list "new motion" dropdown, and the amendment gate's admin
bypass) is deliberately not restricted — admins can always create any type in any language from the
backend.

## Caching

Rendered output now varies by reader language, so language had to enter every relevant cache key:
the motion view-cache key (`views/motion/LayoutHelper::getViewCacheKey()`), PDF cache keys
(`Motion`/`Amendment::getPdfCacheKey()`, one cached PDF per language), the consultation homepage/tag
caches (`views/consultation/LayoutHelper.php`), the proposed-procedure cache, and the amendment
diff/TeX rendering caches (both embed `\Yii::t()`-translated strings).

`LanguageTools::getContentLanguages(?Consultation $consultation): array` returns every supported
language on a multi-language site, else a single-element array with the consultation's primary
language — used by `Motion`/`Amendment::flushCacheWithChildren()` and
`views/consultation/LayoutHelper::flushViewCaches()` to loop over every language a cached render might
exist under. It takes an explicit `?Consultation` rather than relying on the ambient current-request
language, since a flush can be triggered by anyone (an admin action, a console command, a background
job) touching a consultation other than whichever one the current request happens to be about.

## Automatic translation (section autofill)

A section left empty because the submitter only filled in their own language can optionally be filled
in automatically by a plugin. This mechanism is deliberately **not language-specific at the interface
level** — it's a generic "fill this empty section" hook that any plugin could implement for any
purpose (a translation backend, or something unrelated like generating a short abstract from a
motion's main text).

Extension point (`plugins/ModuleBase.php`, following the file's existing static-method-with-null-
default pattern):

```php
public static function fillEmptyMotionSectionsContent(Motion $motion, array $sections): array;     // sectionId => content
public static function fillEmptyAmendmentSectionsContent(Amendment $amendment, array $sections): array;
```

`$sections` is every currently-empty section belonging to that motion/amendment (batched, not one
call per section); a plugin returns a map of only the ones it actually filled — anything left out is
offered to the next active plugin in line, and finally left empty if none of them handle it.

Orchestration (`components/SectionAutofill.php`):

```php
public static function fillEmptyMotionSections(Motion $motion): void;
public static function fillEmptyAmendmentSections(Amendment $amendment): void;
```

Collects every currently-empty section once (via `hasContentForFiltering()`, so an amendment section
is correctly judged by "differs from the original", not raw emptiness), then loops over
`AntragsgruenApp::getActivePlugins()`, offering each plugin the full remaining batch and carrying
forward only what's still unfilled to the next plugin.

### Dispatch: background jobs

Wired in at the end of all four save flows (`MotionEditForm::createMotion()`/`saveMotion()`,
`AmendmentEditForm::createAmendment()`/`saveAmendment()`), routed through the same background-job
system `SendNotification` uses for e-mails (`components/BackgroundJobScheduler.php`,
`commands/BackgroundJobController.php`, `components/BackgroundJobProcessor.php`) rather than calling
`SectionAutofill` directly — synchronous (inline, in the same request) by default, or queued if
enabled:

```json
{
  "backgroundJobs": {
    "sectionAutofill": true
  }
}
```

This is an independent flag from `backgroundJobs.notifications` — each background job type names its
own config flag via `IBackgroundJob::getConfigFlagName()`, rather than all job types sharing one.
`models/backgroundJobs/FillEmptyMotionSections.php` / `FillEmptyAmendmentSections.php` carry only a
motion/amendment ID (everything else is re-derived in `execute()`); a motion/amendment that no longer
exists by the time the job runs is a silent no-op, matching the "never break a save over an optional
enhancement" principle the whole mechanism follows.

### Marking auto-generated content

Modeled on how `Image` sections store metadata, new methods on `IMotionSection`
(`models/db/IMotionSection.php`) apply uniformly across section types:

```php
public function markAsAutofilled(string $pluginId): void;
public function getAutofillPluginId(): ?string;
protected function clearAutofillMarker(): void;
```

Stored under `metadata['autofill']['plugin']`. `clearAutofillMarker()` is called from
`MotionSection::setData()`/`AmendmentSection::setData()`, but only when the incoming value actually
differs from the section's current content — an admin re-saving a form that resubmits an unchanged,
previously-autofilled value must not lose the marker. `HTMLTools::getSectionAutofillHint()` renders an
admin-facing "this text was auto-generated" hint above the edit field in both admin update views,
using `getAutofillPluginId()`.

### The `translation_claude` plugin

A reference implementation (`plugins/translation_claude/`) using Anthropic's Messages API — see its
own `README.md` for setup. Architecture:

- `Module.php` — the two `ModuleBase` hooks; loads credentials and delegates to `SectionTranslator`,
  declining (empty array) if no `credentials.json` is present.
- `Credentials.php` — loads `plugins/translation_claude/credentials.json` (`apiKey`, optional
  `model`, default `claude-sonnet-5`); gitignored, with `credentials.example.json` as the tracked
  template. Returns `null` on anything missing/malformed rather than throwing.
- `ClaudeClient.php` — a single, non-streaming call to `POST /v1/messages`, using
  [Anthropic tool use](https://docs.anthropic.com/en/docs/build-with-claude/tool-use) rather than
  free-form text: `Prompts::translationsToolSchema()` defines a `provide_translations` tool with a
  JSON Schema for `{translations: [{sectionId, translatedHtml}, ...]}`, and the request forces
  `tool_choice` to that tool — this makes multi-item responses reliably parseable regardless of how
  many fragments are batched together, rather than relying on a free-text delimiter that HTML content
  could plausibly contain. Every failure mode (non-2xx, no `tool_use` block, a thrown exception)
  returns `null` rather than breaking the save it's attached to.
- `Prompts.php` — default prompts, instructing Claude to preserve every HTML tag/attribute/class and
  whitespace exactly and translate only the human-readable text. The amendment-specific prompt
  additionally asks Claude to identify what actually changed (comparing the amendment's original vs.
  edited text) and reuse the *existing* translation of the motion's text for everything that didn't
  change — so the translated amendment's diff mirrors the original-language diff instead of
  introducing translation-only noise.
- `SectionTranslator.php` — combines every section a motion/amendment needs translated into a single
  batched request. For each target section it finds a source section sharing the same
  `languageGrouping` with content, preferring the primary language. For amendments, it additionally
  gathers the target language's existing motion translation as an anchor to stay close to.
- `ClaudeLogger.php` — every request/response is logged to `runtime/logs/claude.log` in JSON-lines
  format (one call per line: timestamp, status, duration, input/output token counts, the full request
  and response), from a `finally` block so both successes and failures are captured. Never throws — a
  logging failure must not break translation.

## Known limitations

- Motion type names (outside the `titleSingular`/`titlePlural`/`createTitle` labels, which are
  translatable — see above), agenda items, tags, and content pages stay in whichever language the
  admin typed them in.
- Per-consultation string overrides (`ConsultationText`, the "Translation/Wording" admin page) are
  inactive outside the primary language, so a renamed UI string reverts to the shipped default in
  other languages.
- Amendment reasons/editorial notes (`changeExplanation`/`changeEditorial`) are single-language plain
  DB columns, not per-language sections.
- The reader's language choice is session-only, not remembered across devices or after login/logout
  in a new session.
- Cloning a motion (`?cloneFrom=`) into a multi-language motion type only carries over the source
  motion's content in the *submitter's own* browsing language — the create form only renders (and the
  browser only submits back) that one language, so cloned content for other languages never reaches
  the server. Amendment cloning is unaffected, since the amendment form never wipes pre-filled
  sections between languages.
- The admin motion-metadata screen's `motion[title]` field is a single input bound to the motion's
  canonical title, not to one of the potentially several per-language title sections — admins cannot
  translate a motion's title through that field. (Amendments have no equivalent limitation: an
  amendment's title has no separate canonical cache to begin with, so its per-language title sections
  are only ever reachable — and always editable — through the generic per-section admin edit form.)
- `getAmendableOnlyMotions()` (the statute-amendment picker) can still list a statute whose type isn't
  available in the reader's language; selecting it is blocked on the next page by the availability
  check, so this is a listing inconsistency, not a functional gap.
- The REST API does not yet expose `language`/`languageGrouping` on section definitions/content, or
  filter/expose per-language behavior — sections are returned exactly as the API always returned
  them, unfiltered by any language.
