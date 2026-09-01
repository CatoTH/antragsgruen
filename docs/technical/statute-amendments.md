# Statute Amendments (`amendmentsOnly` Motion Types)

Statute amendments ("Satzungsänderungsanträge") are the mechanism for maintaining a
standing document (bylaws/statutes) where members don't submit whole new motions, but
diffs against the current, fixed text. There is no dedicated `StatuteAmendment` model —
the entire feature is built by giving `Amendment` an unusual role within a specially
configured `ConsultationMotionType`.

## 1. The `amendmentsOnly` motion type

`ConsultationMotionType::$amendmentsOnly` (int/bool column) marks a motion type as a
"statutes" type. It is normally created via the *Statutes* preset
(`models/motionTypeTemplates/Statutes.php`, `doCreateStatutesType()`), which sets:

- `amendmentsOnly = 1`
- `policyMotions = POLICY_ADMINS` — only admins/privileged users may create the base
  motion(s) (the statute text itself).
- `policyAmendments = POLICY_ALL` — anyone (subject to the usual amendment policy) may
  submit an amendment against that text.

Nothing else in the data model is special: a statutes type is a normal
`ConsultationMotionType`/`Motion`/`Amendment` setup. `amendmentsOnly` only changes
*behavior* in a number of targeted places, listed below.

### Creating the base motion(s)

Because `policyMotions` is admin-only, and because `ConsultationMotionType::getCreateLink()`
special-cases `amendmentsOnly` types to point at amendment creation instead of motion
creation (see §2), there is no regular "create motion" button for these types on the
public site. Instead, admins create the base text — the actual statute — from
**Edit Motion Types** (`admin/motion-type/type`): when `$motionType->amendmentsOnly` is
set, the settings page renders `views/admin/motion-type/_amendments_only_motions.php`,
which lists the existing base motions (`ConsultationMotionType::getAmendableOnlyMotions()`)
and links to the plain `/motion/create?motionTypeId=…` route to add a new one. This is
just the ordinary `MotionController::actionCreate` — no special-casing there — it's
reachable for admins because `policyMotions` grants them access even though the public
create link is hidden.

A statutes type can have more than one base motion (e.g. separate documents/chapters);
each is amendable independently.

## 2. Amendments *are* the visible "motions"

The base motion (the statute text) is treated as an internal reference document, not as
a list item. Wherever the app enumerates "the motions" of a consultation, `amendmentsOnly`
types substitute the *direct* amendments of the base motion for the motion itself:

- `IMotionSorter::getIMotionsAndResolutions()` — for `amendmentsOnly` types, iterates
  `$motion->amendments` and adds every amendment with `amendingAmendmentId === null`
  to the "motions" list; the base `Motion` itself is never added.
- `IMotionSorter::moveAmendmentsToMotions()` — sorting/grouping treats such top-level
  amendments as siblings of `Motion` entries (`'amendment' . $entry->id` is registered
  as a "main IMotion" key alongside `'motion' . $entry->id`), and further amendments
  (amendments-to-amendments, see §3) are nested under them the same way ordinary
  amendments are nested under a motion.
- `ConsultationMotionType::getCreateLink()` / `mayCreateIMotion()` — the public "create"
  action for an `amendmentsOnly` type routes to `/amendment/create` against the (usually
  single) base motion, or to `/motion/create-select-statutes` if there are several base
  motions to choose from (`MotionController::actionCreateSelectStatutes`).
- Various admin/list/export views (`views/admin/motion-list/list_all.php`,
  `_list_all_item_amendment.php`, `xlsx_list.php`, `ods_list*.php`,
  `AmendmentController::actionPdfcollection`) branch on `amendmentsOnly` to display/skip
  entries consistently with this "amendments are motions" model.

### Agenda-based home pages

The agenda layouts build their lists from `ConsultationAgendaItem::getMyIMotions()` /
`getIMotionsFromConsultation()` rather than from `getIMotionsAndResolutions()`, so they need
the same distinction separately. `Amendment::isShownAtAgendaItemDirectly()` is the single
place that expresses it:

```php
public function isShownAtAgendaItemDirectly(): bool
{
    return $this->agendaItemId !== null && $this->amendingAmendmentId === null;
}
```

An amendment with an explicit agenda item is listed *at* that agenda item instead of below
the motion it amends. An amendment-to-amendment never is: it always belongs below the
amendment it amends, exactly as on an agenda-less home page.

Testing `agendaItemId` alone is not enough, and that was the cause of amendments-to-amendments
showing up as if they were main motions on agenda pages: `Amendment::getMyAgendaItem()` falls
back to the agenda item of the *motion*, and `AmendmentEditForm::createForUserEdit()` passes
that fallback back in, so simply editing an amendment-to-amendment persists an `agendaItemId`
it never had. It then satisfied the `agendaItemId === $this->id` check in the two agenda
listings *and* was filtered out of its parent by the mirrored
`agendaItemId === null` check in `LayoutHelper::showMotionSubAmendments()`.

Both sides now go through the predicate, so they cannot drift apart again. (The duplicate
copies of that filter in `showMotion()` and `showStatuteAmendment()` were removed —
`showMotionSubAmendments()` applies it anyway.)

Regression tests: `tests/Unit/AgendaAmendmentsToAmendmentsTest.php` covers the two listing
methods and the predicate; `tests/acceptance/amendments/AmendmentsToAmendmentsCept.php` covers
the rendered home page. The latter creates the amendment-to-amendment through
`amendment/create?…&createFromAmendment=…&agendaItemId=…` — that route takes both parameters,
which is the reachable way to end up with an amendment-to-amendment that carries an agenda item
of its own. Without the fix it renders as a top-level `<li class="motion">` of the agenda item
instead of below the motion it belongs to.

## 3. Numbering

Numbering is where the "treated like main motions" behavior is concrete and
observable. `Amendment::getNewNumberForAmendment()`:

```php
public static function getNewNumberForAmendment(Amendment $amendment): string
{
    if ($amendment->getMyMotionType()->amendmentsOnly) {
        if ($amendment->amendingAmendmentId === null) {
            // top-level: numbered like a motion, in the motion-type's own prefix sequence
            return $amendment->getMyConsultation()->getNextMotionPrefix(
                $amendment->getMyMotionType()->id,
                $amendment->getPublicTopicTags()
            );
        } else {
            // amendment-to-amendment: numbered like a normal amendment,
            // relative to the amendment it amends
            $numbering = $amendment->getMyConsultation()->getAmendmentNumbering();
            return $numbering->getAmendmentNumber($amendment, $amendment->amendedAmendment, $amendment->amendedAmendment->amendingAmendments);
        }
    } else {
        $numbering = $amendment->getMyConsultation()->getAmendmentNumbering();
        return $numbering->getAmendmentNumber($amendment, $amendment->getMyMotion(), $amendment->getMyMotion()->amendments);
    }
}
```

- A **top-level statute amendment** (`amendingAmendmentId === null`) gets its number
  from `Consultation::getNextMotionPrefix()` — the same counter used for numbering
  ordinary motions (e.g. `A1`, `A2`, …, using the motion type's `motionPrefix`).
  `getNextMotionPrefix()` scans both `Motion` records *and* their amendments for
  collisions, so statute amendments and regular motions sharing a prefix don't
  collide.
- An **amendment-to-amendment** gets its number from the configured
  `IAmendmentNumbering` implementation (`models/amendmentNumbering/*`, e.g.
  `PerMotionCompact`), called exactly as it would be for a normal amendment — except
  the "base `IMotion`" passed in is the *parent amendment* (`$amendment->amendedAmendment`)
  rather than a `Motion`. `IAmendmentNumbering::getAmendmentNumber()` accepts `IMotion`
  precisely so this substitution works transparently (`Motion` and `Amendment` both
  implement `IMotion`).

Numbers are assigned lazily, on first publication/screening
(`setInitialCreated()` / `setScreened()` / `screen()`), not at creation time — a draft
or unscreened amendment has `titlePrefix === ''`.

## 4. Amendments to amendments (`allowAmendmentsToAmendments`)

By default, a statute amendment cannot itself be amended — only the original statute
text can be amended, all at the same "level". `MotionType::$allowAmendmentsToAmendments`
(`models/settings/MotionType.php`, a per-motion-type JSON setting, exposed in the admin
UI and the REST API as `settings.allow_amendments_to_amendments`) turns this on, letting
users submit amendments *against another amendment* — e.g. a counter-proposal to
someone else's statute amendment.

### Data model

`Amendment::$amendingAmendmentId` (nullable FK to `amendment.id`, added in migration
`m220904_083241_amendment_to_other_amendments`) records this:

- `null` — the amendment amends the base motion directly (the normal case, and the
  only case when `allowAmendmentsToAmendments` is off).
- set — the amendment amends *another amendment*, referenced by
  `getAmendedAmendment()` / `getAmendingAmendments()` (inverse relation).

Note that `motionId` is **always** the base statute motion, even for an
amendment-to-amendment — `amendingAmendmentId` is what records the actual parent. This
means an amendment-to-amendment's own text/sections are still diffed against the base
motion's original text (see §6), and it remains reachable/listed via the same
`motion->amendments` collection as every other amendment of that motion.

### Creating one

The entry point is `AmendmentController::actionCreate`'s `$createFromAmendment`
parameter (an amendment ID), surfaced as a "create amendment based on this amendment"
link in `views/amendment/_view_sidebar.php`, shown whenever
`$motionType->getSettingsObj()->allowAmendmentsToAmendments` is true (the link is
present on every amendment's page for such types, including on amendments that are
themselves already amendments-to-amendments — the app does not hard-block deeper
chains, though the UI/workflow is designed around a single extra level):

```php
UrlHelper::createUrl([
    'amendment/create',
    'motionSlug' => $amendment->getMyMotion()->getMotionSlug(),
    'createFromAmendment' => $amendment->id,
]);
```

In `actionCreate()`, `$createFromAmendment` is resolved into `$amendingAmendment` up front
(both because the deadline check in §5 needs to know about it, and because the login/403
gate needs to reflect the right deadline before the form is even rendered), then reused
both for that gate and for pre-filling the form:

```php
$amendingAmendment = null;
if ($createFromAmendment > 0 && $motion->getMyMotionType()->getSettingsObj()->allowAmendmentsToAmendments) {
    $adoptAmend = $this->consultation->getAmendment($createFromAmendment);
    if ($adoptAmend && $adoptAmend->motionId === $motion->id) {
        $amendingAmendment = $adoptAmend;
    }
}

if (!$motion->isCurrentlyAmendable(true, false, false, $amendingAmendment)) { /* login redirect / 403 */ }

// ...

} elseif ($amendingAmendment !== null) {
    $form->cloneAmendmentText($amendingAmendment, false);
    $form->toAnotherAmendment = $amendingAmendment->id;
}
```

`cloneAmendmentText()` pre-fills the new amendment's paragraph-diff sections with the
*parent amendment's* proposed text (not the original statute text), so editing starts
from what the parent amendment proposed. `AmendmentEditForm::$toAnotherAmendment` is
carried through `createAmendment()` and written to `$amendment->amendingAmendmentId` on
save.

The same flow exists via the REST API: `AmendmentCreateRequest` accepts
`createFromAmendment` in the POST body / `amendingAmendmentId` in the DTO
(`models/api/imotion/AmendmentCreateRequest.php`); it resolves and validates the
referenced amendment the same way and maps it to the `amendingAmendmentId` constructor
argument, which `AmendmentEditForm::createAmendment()` then stores identically to the
web flow.

## 5. Deadlines for amendments to amendments

Because an amendment-to-amendment is internally still just an `Amendment` row
(`motionId` pointing at the base motion, `amendingAmendmentId` pointing at its real
parent — see §4), it used to be governed by exactly the same deadline as a top-level
amendment: `ConsultationMotionType::DEADLINE_AMENDMENTS`. There was no way to close
submissions for new statute amendments while still leaving time for amendments-to-those-
amendments (e.g. counter-proposals) to be worked out, or vice versa.

`ConsultationMotionType::DEADLINE_AMENDMENTS_TO_AMENDMENTS` (`'amendmentsToAmendments'`)
is a second, independent deadline type for exactly this case. Like all deadline types it
lives as just another key in the `deadlines` JSON column (`ConsultationMotionType::$deadlines`)
— no schema change was needed. The resolution rule, centralized in
`ConsultationMotionType::isInAmendmentDeadline(bool $isAmendmentToAmendment)`:

```php
public function isInAmendmentDeadline(bool $isAmendmentToAmendment): bool
{
    if ($isAmendmentToAmendment && count($this->getDeadlinesByType(self::DEADLINE_AMENDMENTS_TO_AMENDMENTS)) > 0) {
        return $this->isInDeadline(self::DEADLINE_AMENDMENTS_TO_AMENDMENTS);
    }
    return $this->isInDeadline(self::DEADLINE_AMENDMENTS);
}
```

i.e. an amendment-to-amendment only breaks away from `DEADLINE_AMENDMENTS` once an admin
has explicitly configured at least one window for `DEADLINE_AMENDMENTS_TO_AMENDMENTS` —
until then it silently falls back to the amendments deadline, so upgrading doesn't change
behavior for existing consultations. Once configured, it's fully independent: it can be
open while `DEADLINE_AMENDMENTS` is closed, or closed while `DEADLINE_AMENDMENTS` is open.

Every deadline-sensitive check that needs to distinguish "amending the base motion" from
"amending another amendment" now threads this through:

- `Permissions::isCurrentlyAmendable(Motion $motion, ..., ?Amendment $amendingAmendment = null)`
  — the core gate for whether a new amendment may be created at all — branches via
  `isInAmendmentDeadline($amendingAmendment !== null)` instead of checking
  `DEADLINE_AMENDMENTS` directly. `Motion::isCurrentlyAmendable()` just threads the
  parameter through.
- `AmendmentEditForm::createAmendment()` resolves `$amendingAmendment` from
  `$dto->amendingAmendmentId` *before* calling `isCurrentlyAmendable()`. Since this
  method is the single choke point for both the web form and the REST API create path
  (`AmendmentCreateRequest`), fixing it here is what actually enforces the deadline —
  everything else (see below) is about the gate being *consistent* with this before the
  user gets that far.
- `AmendmentController::actionCreate()` resolves `$amendingAmendment` from
  `$createFromAmendment` up front (§4) so its own entry gate/login-redirect reflects the
  right deadline instead of always checking `DEADLINE_AMENDMENTS`.
- `views/amendment/_view_sidebar.php` passes the amendment being viewed as
  `$amendingAmendment` into both `isCurrentlyAmendable()` calls that control the "create
  amendment based on this amendment" link and its "(admins only)" hint.
- `Permissions::amendmentCanEditText(Amendment $amendment)` and
  `Amendment::isDeadlineOver()` branch on `$amendment->amendingAmendmentId !== null`
  directly (there's no "other amendment" to resolve here — the amendment itself already
  says whether it's one).

### Admin UI and API

Wired exactly like `DEADLINE_MERGING` — i.e. as a "complex case" only, never exposed in
the simple two-field (motions/amendments) deadline form:

- `views/admin/motion-type/_deadlines.php` renders a `.deadlineTypeComplex.amendmentsToAmendmentsDeadlines`
  block (same `_deadline_row` partial, same generic `.deadlineHolder`/`data-type` JS in
  `MotionTypeEdit.js`) that only ever appears once "complex" mode is toggled on.
- `DeadlineForm::$deadlinesAmendmentsToAmendments` is wired through `createFromMotionType()`,
  `createFromInputComplex()`, and `generateDeadlineArray()`; it also counts toward
  `isSimpleConfiguration()` returning `false` (a configured value forces complex mode),
  same as merging.
- `ConsultationMotionType::applySettingsUpdate()` persists it from
  `MotionTypeUpdateRequest->deadlines->amendmentsToAmendments`.
- REST API: `MotionTypeDeadlinesUpdateRequest.amendments_to_amendments` (write) and
  `MotionTypeSettings.amendments_to_amendments_deadlines` (read, alongside
  `merging_deadlines`) in `docs/openapi.yaml`, DTOs regenerated via
  `docs/openapi-generate-dtos.php`.

## 6. Diffing and display

Every `AmendmentSection`'s paragraph-level diff is always computed against the **base
motion's** original section text — `motionId` never points at the parent amendment, so
the storage/diff mechanism used for amendments-to-amendments is identical to that of a
normal amendment. What changes is only the *presentation*:
`views/amendment/_view_text.php` detects `$isAmendingOtherAmendment =
($motionType->amendmentsOnly && $amendment->amendedAmendment)` and offers a toggle
between two ways of reading such an amendment:

1. **Compared to the original text** (default) — this amendment's own diff against the
   original, followed by the parent amendment's version of the same section
   (`$amendment->amendedAmendment->getSection($section->sectionId)`, labelled via the
   `statute_original_title` / `statute_amending_title` message keys).
2. **Compared to the amendment it amends** — one consolidated, two-layered diff (below).

The toggle lives in the **section's existing view mode dropdown** (the cog in the section
header, `.amendmentTextModeSelector`), so switching happens per section, exactly like the
"only changed paragraphs" / "full text" toggle next to it:

| Shown as | Entries in the dropdown |
|---|---|
| regular amendment | only changed paragraphs · full text |
| amendment-to-amendment, compared to the original | only changed paragraphs · full text · **changes to «parent»** |
| amendment-to-amendment, compared to the parent | **changes to the original text** · changes to «parent» |

The consolidated view has no "only changed paragraphs" mode: hiding the unchanged
paragraphs would hide the parent amendment's proposal, which is the very context that view
exists to provide. `TextSimpleCommon::formatAmendmentDiff()` therefore skips the
`.onlyChangedText` container entirely for it.

Which entries a section renders is controlled by
`ISectionType::setAmendmentComparison(?string $mode, ?string $parentName)`
(`AMENDMENT_COMPARISON_TO_ORIGINAL` / `AMENDMENT_COMPARISON_TO_PARENT`), set by the view
right before rendering. Both variants of a section are rendered into two
`.amendmentComparison` containers inside one `.amendmentComparisonSection`; the JS in
`IMotionShow::initAmendmentComparisonMode()` toggles between them. A section that cannot be
consolidated is rendered without the containers and without the extra dropdown entry.

Note that the *parent* amendment's block gets the switching entry too: this amendment's own
block is empty whenever it changes nothing in that section, and the toggle still has to be
reachable.

Directly below the section header, `TextSimpleCommon::getComparisonLegend()` renders a
`.amendmentComparisonLegend` hint box explaining the six markings (`statute_legend_*` message
keys). Its samples are marked up with the very styles they describe, so the legend stays correct
in whatever layout/theme renders them, rather than naming colors that a plugin theme may have
changed:

| Sample | Meaning |
|---|---|
| `<ins class="outer">` | inserted by the amended amendment |
| `<del class="outer">` | deleted by the amended amendment |
| `<ins>` | inserted by this amendment |
| `<del>` | deleted by this amendment |
| `<del class="outer"><ins>…</ins></del>` | deleted by the amended amendment, **restored** by this one |
| `<ins class="outer"><del>…</del></ins>` | inserted by the amended amendment, **dropped again** by this one |

The last two are the ones readers cannot guess: a marking of the inner layer *nested* into one of
the outer layer, i.e. this amendment changing what the amended amendment proposes rather than
changing the original text.

### The two-layered diff (`components/diff/TwoLayerDiff.php`)

The consolidated view shows the changes of the **parent** amendment as an *outer* layer
and the changes this amendment makes *to those* as an *inner* layer:

```
original: Test 123 aber das hier nicht
parent:   Test 123<ins>4567</ins> aber das hier <del>nicht</del>
child:    Test 123<ins>4568</ins> aber das hier <del>nich</del>t

result:   Test <del class="outer">123</del><ins class="outer">123456<del>7</del><ins>8</ins></ins>
          aber das hier <del class="outer">nicht</del><ins>t</ins>
```

Both layers are anchored on the **original motion text**, not on the parent's text —
otherwise the `###LINENUMBER###` markers, and with them the whole line numbering, would be
lost. How the two are combined, which markers are used, how the renderer handles the
nesting, and what happens when no consistent result can be built is documented in
[diff-pipeline.md § Part II](diff-pipeline.md#part-ii--amendments-amending-amendments).

Exports (PDF, ODT, LaTeX, plain text) and the REST API are unchanged and still show the
diff against the original motion text only.

## 7. Access/consistency checks

`Base::loadConsultation()` rejects any request where an amendment's
`amendingAmendmentId` is set but the referenced amendment can no longer be resolved
(`amendingAmendmentId && !$checkAmendment->amendedAmendment` → `err_amend_no_parent`),
e.g. because the parent amendment was deleted.

## Summary of the key fields/params

| Symbol | Location | Role |
|---|---|---|
| `ConsultationMotionType::$amendmentsOnly` | DB column | Marks a motion type as a statutes type: base motions are admin-only, amendments are the public-facing "motions". |
| `MotionType::$allowAmendmentsToAmendments` | JSON setting (`consultationMotionType.settings`) | Enables submitting amendments against other amendments, not just against the base motion. |
| `$createFromAmendment` (GET/POST param) | `AmendmentController::actionCreate` | ID of the amendment being amended; triggers the amendment-to-amendment flow. |
| `Amendment::$amendingAmendmentId` | DB column (`amendment.amendingAmendmentId`) | Persisted result: null = amends the base motion; set = amends another amendment (whose ID this is). |
| `ConsultationMotionType::DEADLINE_AMENDMENTS_TO_AMENDMENTS` | Key in the `deadlines` JSON column | Independent deadline for amendments-to-amendments; falls back to `DEADLINE_AMENDMENTS` until explicitly configured (`isInAmendmentDeadline()`). |
