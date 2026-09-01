# The Diff Pipeline

Almost everything Antragsgrün does with amendments is a diff. An `Amendment` does **not** store
"insert X in line 12"; it stores the *complete proposed text* of every section it changes
(`AmendmentSection::$data`). The change itself only ever exists as a computed diff between that text
and the corresponding `MotionSection::$data` of the motion it amends.

That has two consequences that shape everything below:

- The diff is recomputed on every page view (with caching in front of it), so it has to be fast.
- The diff has to be *good*: it is not a developer tool, it is the primary way a reader understands
  what an amendment does, and it is quoted verbatim in printed assembly documents.

Everything lives in `components/diff/`.

---

## 1. Bird's eye view

```
     MotionSection::$data                       AmendmentSection::$data
   (the original motion text)                  (the full proposed text)
              │                                            │
              ▼                                            ▼
      ┌───────────────────────────────────────────────────────────┐
   ①  │ HTMLTools::sectionSimpleHTML()  →  SectionedParagraph[]    │  split into paragraphs
      └───────────────────────────────────────────────────────────┘
              │                                            │
              ▼                                            │
      ┌────────────────────────────┐                       │
   ②  │ LineSplitter               │                       │  ###LINENUMBER### markers
      │ ###LINENUMBER### injection │                       │  (original only)
      └────────────────────────────┘                       │
              │                                            │
              └──────────────┬─────────────────────────────┘
                             ▼
      ┌───────────────────────────────────────────────────────────┐
   ③  │ ArrayMatcher::matchForDiff()                               │  align paragraph N of the
      │   → two arrays of equal length, ###EMPTYINSERTED### fillers│  original with paragraph N
      └───────────────────────────────────────────────────────────┘  of the amendment
                             │
                             ▼   (per paragraph pair)
      ┌───────────────────────────────────────────────────────────┐
   ④  │ Diff::computeLineDiff()                                    │
      │   tokenizeLine → Engine (LCS) → groupOperations             │
      │   → computeWordDiff → change-ratio safety valves            │
      └───────────────────────────────────────────────────────────┘
                             │
                             ▼
              a string with text markers, e.g.
              "Der ###DEL_START###alte###DEL_END######INS_START###neue###INS_END### Text"
                             │
             ┌───────────────┴────────────────┐
             ▼                                ▼
      ┌─────────────────┐            ┌──────────────────────────┐
   ⑤  │ DiffRenderer    │         ⑥  │ Diff::convertToWordArray │
      │ markers → HTML  │            │ markers → DiffWord[]      │
      │ <ins> / <del>   │            │ (anchored on the original)│
      └─────────────────┘            └──────────────────────────┘
             │                                ▼
             ▼                       amendmentMerger, AmendmentRewriter,
      ┌─────────────────────┐        MovingParagraphDetector
   ⑦  │ AffectedLinesFilter │        (§9)
      │ formatDiffGroup     │
      └─────────────────────┘
             │
             ▼
    "In line 12 delete: …"
```

The pipeline splits in two after stage ④. Both branches consume the *same* marker string:

- **The rendering branch (⑤/⑦)** turns markers into HTML for display, PDF and ODT.
- **The word-array branch (⑥)** turns markers into a per-token data structure so that *several*
  amendments' changes can be reasoned about together (merging, collision detection, rewriting).

---

## 2. Why text markers and not `<ins>`/`<del>`?

The diff works on HTML, and the changes it finds do not respect the HTML tree. An amendment that
splits one paragraph into two produces a change that *starts* inside one `<p>` and *ends* inside
another:

```html
<p>First half###INS_START###</p><p>###INS_END###second half</p>
```

There is no way to express that with a well-nested `<ins>` element. So the diff stage never emits
HTML — it emits flat text markers, and a separate stage (`DiffRenderer`) turns them into
*valid* HTML afterwards, splitting elements as necessary:

```html
<p>First half<ins></ins></p><p><ins></ins>second half</p>
```

The markers are defined in `DiffRenderer`:

| Constant | Value |
|---|---|
| `DiffRenderer::INS_START` | `###INS_START###` |
| `DiffRenderer::INS_END` | `###INS_END###` |
| `DiffRenderer::DEL_START` | `###DEL_START###` |
| `DiffRenderer::DEL_END` | `###DEL_END###` |

`*_START` markers can carry a **parameter**: `###INS_START<param>###`, where `<param>` matches
`[^#]{0,20}`. It is used by the amendment merger to record *which* amendment a change belongs to
(`###INS_START7-42-COLLISION###`, see §9.1), and it reaches the renderer's ins/del callbacks.

Two more placeholders travel through the same strings and are just as important:

| Placeholder | Meaning |
|---|---|
| `###LINENUMBER###` | a line break of the *original* motion text — the anchor for all line numbering |
| `###EMPTYINSERTED###` | a paragraph that exists only in the amendment (produced by `ArrayMatcher`) |

---

## 3. Stage ①: sectioning into paragraphs

`HTMLTools::sectionSimpleHTML()` flattens the section HTML into a list of `SectionedParagraph`
objects — roughly one per top-level block element, with list items split out individually:

```
<p>Lorem ipsum</p>          →  [0] <p>Lorem ipsum</p>
<ul>                           [1] <ul><li>one</li></ul>
  <li>one</li>                 [2] <ul><li>two</li></ul>
  <li>two</li>
</ul>
```

Note that each `<li>` is re-wrapped in its own `<ul>`. This is deliberate: it lets the diff treat a
single list item as an independent unit, so inserting one bullet point does not mark the whole list
as changed. `SectionedParagraph::$paragraphWithoutLineSplit` / `$paragraphWithLineSplit` keep the
mapping back to the un-split numbering, which the merger and the comment anchors need.

**The paragraph index is a stable identity.** Comments, the merger and the amendment rewriter all
address text by paragraph number, so this stage runs before anything else and its result is cached
(`HashedStaticCache`, key `sectionSimpleHTML2`).

## 4. Stage ②: line numbers

Antragsgrün numbers lines, not paragraphs, and the line numbers of the *original* motion are what
amendments refer to ("in line 42 delete…"). `AmendmentSectionFormatter::addLineNumberPlaceholders()`
calls `LineSplitter::splitHtmlToLines()` and inserts a `###LINENUMBER###` marker at every line break:

```
<p>###LINENUMBER###Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam
###LINENUMBER###nonumy eirmod tempor invidunt ut labore et dolore magna.</p>
```

The line length comes from `Consultation::getSettings()->lineLength`.

The amendment text is run through the *same* function and the markers are then **stripped again**
(`AmendmentSectionFormatter::prepareAmendedParagraphs()`). That looks pointless but is not:
`splitHtmlToLines()` also breaks overly long words and appends a hyphen, so both sides have to go
through it or every long word would show up as a change.

From here on, `###LINENUMBER###` is just another token that happens to appear only on the original
side. Two mechanisms keep it from being reported as a deletion:

- `Engine::setIgnoreStr('###LINENUMBER###')` makes the LCS comparison blind to it
  (`Engine::strCmp()`, plus special handling in `getArrayDiffStarts()`/`getArrayDiffEnds()`).
- After rendering, `Diff::compareHtmlParagraphs()` rewrites `<del …>###LINENUMBER###</del>` back
  into a bare marker.

At the very end of the pipeline, `LineSplitter::replaceLinebreakPlaceholdersByMarkup()` turns each
marker into `<br>` plus a `<span class="lineNumber">`.

## 5. Stage ③: paragraph alignment (`ArrayMatcher`)

Before any text can be diffed, the pipeline has to decide *which* paragraph of the amendment
corresponds to *which* paragraph of the original. `ArrayMatcher::matchForDiff()` returns two arrays
of equal length:

```
original                    amendment                →  aligned original      aligned amendment
[0] <p>Alpha</p>            [0] <p>Alpha</p>            [0] <p>Alpha</p>       <p>Alpha</p>
[1] <p>Beta</p>             [1] <p>Beta modified</p>    [1] <p>Beta</p>        <p>Beta modified</p>
[2] <p>Gamma</p>            [2] <p>NEW</p>              [2] ###EMPTYINSERTED### <p>NEW</p>
                            [3] <p>Gamma</p>            [3] <p>Gamma</p>       <p>Gamma</p>
```

It works in two steps:

1. Run the LCS `Engine` over the paragraphs themselves (with `$relaxedTags = true`, so that
   `<li value="3">` and `<li value="4">` still count as the same paragraph).
2. Where a block of deletions is followed by a block of insertions, `matchArrayUnresolved()` decides
   *which* deleted paragraph pairs with *which* inserted one. It enumerates all placements of the
   shorter list inside the longer one (`calcVariants()`) and picks the one with the highest
   `similar_text()` score (`getBestFit()`).

Step 2 is exponential in the size difference, so `calcVariants()` gives up and appends plain
fillers once the gap reaches 8 paragraphs. That is the pragmatic fallback for "a whole chapter was
replaced" — a suboptimal alignment rather than a hanging request.

## 6. Stage ④: the paragraph diff (`Diff::computeLineDiff`)

This is the heart. Input: two paragraphs. Output: one string with markers.

### 6.1 Tokenization

`Diff::tokenizeLine()` splits a paragraph into the units the LCS will compare:

```
<p>Der alte Text.</p>
   ↓
["<p>", "Der ", "alte ", "Text", ".", "</p>"]
```

Rules:
- Every HTML tag becomes its own token (`/(<br>\n+|<[^>]+>)/`).
- Text is split on `[ \-.:]`, keeping the delimiters.
- A trailing space or hyphen is **appended to the preceding token** (`"Der "`), so that word
  boundaries stay attached to their word. `.` and `:` become tokens of their own.

### 6.2 The LCS engine

`Engine::compareArrays()` is a classic longest-common-subsequence diff (originally from Stephen
Morley's public-domain implementation), returning `[token, UNMODIFIED|DELETED|INSERTED]` pairs. Three
Antragsgrün-specific additions:

- **Common prefix/suffix trimming** before building the LCS table — the table is O(n·m), and most
  amendments change a small part of a long paragraph.
- **`$IGNORE_STR`** so that `###LINENUMBER###` never counts as a difference.
- **Relaxed tag comparison** (`strCmp()`): any two `<ol…>`, `<ul…>` or `<li…>` tags compare equal.
  Renumbering a list (`<ol start="2">` → `<ol start="3">`) would otherwise flood the diff with
  changes nobody made.

`Engine::moveWordOpsToMatchSentenceStructure()` then shifts change blocks backwards where the words
immediately before the block are identical to the words at its end. This turns

> Wir fordern **eine** ~~schnelle~~ Umsetzung  →  Wir fordern ~~eine schnelle~~ **eine** Umsetzung

into the version that reads naturally.

### 6.3 Grouping and the word diff

`Diff::groupOperations()` merges runs of consecutive tokens with the same operation into single
strings — except that a token which is *purely* an HTML tag always stays its own group, and lists
force a group break. That is what keeps `<ins>` from swallowing a `</p>`.

Then, per group:

| Case | Result |
|---|---|
| `UNMODIFIED` | emitted verbatim |
| `DELETED` immediately followed by `INSERTED` (and the insert does not start with `<`) | `computeWordDiff()` |
| `DELETED` alone | each space-separated part wrapped in `###DEL_START###…###DEL_END###` |
| `INSERTED` alone | each space-separated part wrapped in `###INS_START###…###INS_END###` |

`computeWordDiff()` is the character-level refinement. Given `"1234567 "` replaced by `"1234568 "`
it peels off, in order: the common *word* prefix, the common *word* suffix, the common *character*
prefix, the common *character* suffix — and only marks the remainder:

```
computeWordDiff("4567", "4568")
  common word prefix : ""        (the difference is inside a word)
  common word suffix : ""
  common char prefix : "456"     (≥ 3 chars, kept)
  common char suffix : ""
  remainder          : "7" vs "8"   (both ≤ 3 chars → fine-grained)
  →  456###DEL_START###7###DEL_END######INS_START###8###INS_END###
```

The two `< 3` thresholds and the final `<= 3` check are what stop the diff from producing
unreadable letter-by-letter confetti: if the remainders are longer than three characters, the whole
word is struck and replaced instead.

### 6.4 The safety valves

Two heuristics collapse an over-fragmented diff back into a plain "replace this by that":

| Constant | Value | Applies to |
|---|---|---|
| `MAX_LINE_CHANGE_RATIO` | 0.6 | the whole paragraph |
| `MAX_LINE_CHANGE_RATIO_PART` | 0.4 | the changed middle part, if it is longer than `MAX_LINE_CHANGE_RATIO_MIN_LEN` (100) chars |

`computeLineDiffChangeRatio()` measures how much of the original survives unmarked. Above the
threshold the result becomes `###DEL_START###«whole old»###DEL_END######INS_START###«whole new»###INS_END###`,
because at that point a reader is better served by seeing both versions than by hunting for the
islands of unchanged text. `getUnchangedPrefixPostfix()` is what identifies the untouched
lead-in/lead-out so the second check can be applied to the middle only.

These two are not cosmetic. Once a paragraph has been rewritten substantially, the LCS will happily
match every stray `der`, `den` or `wird` that occurs in both versions, and those one-word islands
tear the deletion and the insertion into a dozen unreadable fragments. The heuristics are what turn
that back into "this was replaced by that" — and they matter just as much for the two-layer diff
(§11.5).

A third, unconditional collapse sits at the top: if the paragraph's own tag changes
(`htmlParagraphTypeChanges()`, e.g. `<p>` → `<ul>`), the paragraph is always replaced wholesale.

### 6.5 Worked example

```
original:  <p>Test 123 aber das hier nicht</p>
amendment: <p>Test 1234567 aber das hier </p>
```

```
tokenize   ["<p>", "Test ", "123 ", "aber ", "das ", "hier ", "nicht", "</p>"]
           ["<p>", "Test ", "1234567 ", "aber ", "das ", "hier ", "</p>"]

LCS        <p>      UNMODIFIED
           Test     UNMODIFIED
           123      DELETED  ┐
           1234567  INSERTED ┘ → computeWordDiff("123 ", "1234567 ")
           aber…    UNMODIFIED
           nicht    DELETED
           </p>     UNMODIFIED

markers    <p>Test ###DEL_START###123###DEL_END######INS_START###1234567###INS_END### aber das hier ###DEL_START###nicht###DEL_END###</p>

rendered   <p>Test <del>123</del><ins>1234567</ins> aber das hier <del>nicht</del></p>
```

Note that `123` → `1234567` comes out as a full replacement, not as `123<ins>4567</ins>`: the
common character prefix is `123`, but the remaining insertion `4567` is longer than 3 characters,
so §6.3's last rule falls through to replacing the whole word. This is worth remembering — it is
visible again in §11.

## 7. Stage ⑤: markers → HTML (`DiffRenderer`)

`DiffRenderer::renderHtmlWithPlaceholders()` parses the marker string into a DOM
(`HTMLTools::html2DOM()`) and walks it, carrying two state variables — `$inIns` and `$inDel`, each
holding the marker's parameter or `null`. For each node:

- **Text node** → `textToNodes()` splits it at the markers and creates `<ins>`/`<del>` elements.
- **Inline element while a marker is open** → wrapped in (or appended to) the open `<ins>`/`<del>`.
- **Block element while a marker is open** → cannot be wrapped, so it gets
  `class="inserted"` / `class="deleted"` instead (`addInsStyles()` / `addDelStyles()`).

That last rule is why the CSS has to style both forms:

```html
<ins>a word</ins>                       inline change
<p class="inserted">a whole paragraph</p>   block change
```

`setFormatting()` selects the output flavour:

| Constant | Output |
|---|---|
| `FORMATTING_NONE` | bare `<ins>`/`<del>` |
| `FORMATTING_CLASSES` | bare tags, styling via CSS classes |
| `FORMATTING_CLASSES_ARIA` | as above plus `aria-label="Einfügen: „…”"` — **the default for the web UI** |
| `FORMATTING_INLINE` | inline `style="color: green; …"` — where no stylesheet is available: TCPDF output, e-mails, `getAmendmentPlainHtml()` |
| `FORMATTING_ICE` | attributes for the CKEditor change-tracking plugin — the merge editor and the proposed-change editors |

`setInsCallback()`/`setDelCallback()` let callers decorate every created element; the marker
parameter is passed in as the second argument. `renderForInlineDiff()` uses this to attach the
amendment id, initiator name and permalink to every change shown inside a motion text.

## 8. Stage ⑦: from a diffed paragraph to "In line 12 delete: …"

The web UI shows an amendment in two modes (the cog dropdown in the section header):

- **Full text** — every diffed paragraph, in order. Just
  `LineSplitter::replaceLinebreakPlaceholdersByMarkup()` over the rendered paragraphs.
- **Only changed paragraphs** (default) — the pipeline continues:

```
rendered paragraphs
   │
   ▼  AmendmentSectionFormatter::groupConsecutiveChangeBlocks()
        pure "delete paragraph / insert paragraph" pairs are collected and re-ordered
        so all deletions come before all insertions
   │
   ▼  AffectedLinesFilter::splitToAffectedLines()
        splitToLines()            split at ###LINENUMBER###, assign a line number to each
        filterAffectedBlocks()    drop lines without changes (keeping $context lines around)
        groupAffectedDiffBlocks() re-join adjacent lines into AffectedLineBlock{text,lineFrom,lineTo}
   │
   ▼  TextSimpleCommon::formatDiffGroup()
        pick the right heading per block:
        "In line 12 delete:" / "From line 12 to 14:" / "After line 12 insert:" …
```

`filterAffectedBlocks()` has to track `<ins>`/`<del>` **across** line boundaries, because a change
can span several lines — a line in the middle of a long deletion contains no tag at all and would
otherwise look unchanged.

## 9. Stage ⑥: the word-array branch

For display, one amendment against one motion is enough. But three features need to reason about
*several* amendments at once, or about a change *per original word*:

`Diff::convertToWordArray()` re-anchors a marker string onto the tokens of the original:

```
original: "Der alte Text"
diff:     "Der ###DEL_START###alte###DEL_END######INS_START###neue###INS_END### Text"

→ DiffWord[]
  [0] word: "Der "    diff: "Der "
  [1] word: "alte "   diff: "###DEL_START###alte###DEL_END######INS_START###neue###INS_END### "
  [2] word: "Text"    diff: "Text"
```

Every `DiffWord` carries the original token (`word`), the diffed version (`diff`) and the id of the
amendment responsible (`amendmentId`, `null` if unchanged).

**Two invariants make this useful:**

1. `implode('', $words[*]->word)` reproduces the original paragraph exactly.
   `Diff::checkWordArrayConsistency()` asserts this and throws `Internal` otherwise.
2. Therefore, two *different* amendments diffed against the *same* original produce word arrays of
   identical length with identical `word` values — they are aligned by construction and can be
   walked in lockstep.

`Diff::compareHtmlParagraphsToWordArray()` does this for a whole section and returns
`DiffWord[][]`, indexed **by original paragraph**. Paragraphs that exist only in the amendment
(`###EMPTYINSERTED###`) are folded into the last word of the preceding paragraph, so the outer
array always has exactly as many entries as the original had paragraphs.

### 9.1 The amendment merger (`components/diff/amendmentMerger/`)

Used by the motion view ("show amendments inline") and by the merge-amendments editor.
`SectionMerger` holds one `ParagraphMerger` per original paragraph and feeds every amendment's word
array into it. `ParagraphMerger` then applies each amendment's changes to a shared
`ParagraphMergerWord[]`, and where two amendments touch the *same* original word, it detects a
**collision**:

```
original            Wir fordern eine Umsetzung
amendment 1         Wir fordern eine schnelle Umsetzung
amendment 2         Wir fordern eine sofortige Umsetzung
                                    ▲
                              same word → collision
```

Small, text-only collisions are merged into the text anyway (`$collisionMergingLimit`, 100 chars);
larger ones are separated out and rendered as their own "colliding paragraph" block below the text.
The amendment id ends up in the marker parameter (`###INS_START«cid»-«amendmentId»###`) so
`DiffRenderer::renderForInlineDiff()` can colour and link each change by amendment.

### 9.2 The amendment rewriter (`AmendmentRewriter`)

When an admin edits a motion that already has amendments, every amendment's stored text has to be
re-based onto the new motion text. The rewriter computes which paragraphs each side touched, and
where the two do not overlap it transplants the amendment's changes onto the new text. Where they
do overlap it reports a collision, and the admin has to resolve it by hand.

### 9.3 Moved paragraphs (`MovingParagraphDetector`)

If a paragraph is deleted in one place and an identical one inserted in another, both get
`class="moved"` plus `data-moving-partner-*` attributes, and the UI draws them as a move rather than
as a deletion plus an unrelated insertion. It works both on rendered strings
(`markupMovedParagraphs()`, used by `compareHtmlParagraphs()`) and on word arrays
(`markupWordArrays()`, used by `SectionMerger`).

## 10. Entry points, caching and dead ends

| Entry point | Returns | Used by |
|---|---|---|
| `AmendmentSectionFormatter::getDiffSectionsWithNumbers()` | rendered paragraphs | "full text" mode, exports |
| `AmendmentSectionFormatter::getDiffGroupsWithNumbers()` | `AffectedLineBlock[]` | "only changed" mode, PDF/ODT/TeX |
| `Diff::compareHtmlParagraphs()` | rendered paragraphs | the two above |
| `Diff::computeAffectedParagraphs()` | rendered paragraphs, changed ones only | tests only |
| `Diff::compareHtmlParagraphsToWordArray()` | `DiffWord[][]` | merger, two-layer diff |
| `MotionSection::getAmendmentDiffMerger()` | `SectionMerger` | inline amendments, merge editor |

Caching happens at three levels, all via `HashedStaticCache` (in-process, plus the configured Yii
cache):

- `sectionSimpleHTML2` and `splitHtmlToLines` — the input preparation.
- `compareHtmlParagraphs` — the diff itself.
- `getMaybeCachedDiffGroups` in `TextSimpleCommon` — the whole formatted result, but
  **only for original texts of 10 000 characters or more** (`setSkipCache(true)` below that, because
  the cache round-trip would cost more than recomputing).

Two methods survive only because tests still call them and are not reached from application code:
`Engine::shiftMisplacedHTMLTags()` and `Diff::computeSubsequentInsertsDeletes()`.

### Invariants worth not breaking

- Unchanged + deleted text, concatenated, is exactly the original. Unchanged + inserted text is
  exactly the amendment. Everything in §9 depends on this.
- `###LINENUMBER###` markers appear only on the original side, and their count per paragraph must
  survive the whole pipeline — they *are* the line numbering.
- `DiffRenderer` may split elements, but the HTML it emits must be well-formed; `AffectedLinesFilter`
  parses it again.

---

# Part II — Amendments amending amendments

> For the feature as a whole (data model, deadlines, numbering, permissions) see
> [statute-amendments.md](statute-amendments.md). This section only covers what it adds to the
> pipeline above.

Statute motion types (`amendmentsOnly`) can allow an amendment to amend *another amendment*
(`Amendment::$amendingAmendmentId`). Storage-wise nothing changes: `motionId` still points at the
base statute motion, and the child amendment's sections still hold a full text that is diffed
against the *original motion text*, exactly as in Part I.

What is new is a second way of *reading* it. Each section's view mode dropdown (§8) offers a toggle:

1. **Compared to the original text** — this amendment's diff, plus the parent amendment's diff,
   side by side. Two independent runs of the Part I pipeline, each with its own
   "only changed paragraphs" / "full text" mode.
2. **Compared to the amendment it amends** — one consolidated, two-layered diff, always shown as
   full text (hiding the unchanged paragraphs would hide the parent's proposal, which is the context
   this view exists to provide).

## 11. The two-layer diff (`TwoLayerDiff`)

### 11.1 What it shows

```
original   Test 123 aber das hier nicht
parent     Test 123⟨ins:4567⟩ aber das hier ⟨del:nicht⟩
child      Test 123⟨ins:4568⟩ aber das hier ⟨del:nich⟩t
```

The reader wants to see *both* at once: what the parent amendment proposes (**outer layer**), and
what this amendment changes about that proposal (**inner layer**).

```
Test <del class="outer">123</del><ins class="outer">123456<del>7</del><ins>8</ins></ins>
 aber das hier <del class="outer">nicht</del><ins>t</ins>
     └──────── outer ────────┘   └── inner ──┘
```

Read as: *the parent replaces `123` by `1234567`; this amendment wants `1234568` instead. The parent
deletes `nicht`; this amendment puts the `t` back.*

(The outer layer says `<del>123</del><ins>123456…</ins>` rather than `123<ins>456…</ins>` for the
reason explained in §6.5 — and deliberately so, see §11.5.)

### 11.2 Anchoring: both layers on the original

The obvious approach — diff the parent's text against the child's text — is wrong here, for one
decisive reason: **`###LINENUMBER###` markers live in the original motion text.** A diff anchored on
the parent amendment's text has no line numbers, and everything in §8 stops working.

So both layers are anchored on the original, and the second invariant from §9 does the heavy
lifting:

```
                    original motion text
                    ╱                  ╲
   compareHtmlParagraphsToWordArray    compareHtmlParagraphsToWordArray
        (parent amendment)                  (child amendment)
                    ╲                  ╱
              two DiffWord[][] of identical shape:
              same paragraph count, same tokens, same offsets
                          │
                          ▼
                  walk them in lockstep
```

There is no alignment problem left to solve — `checkWordArrayConsistency()` already guarantees that
both sides tile the same original text.

### 11.3 The combination table

For each stretch of the original text and each insertion position:

| parent | child | rendered as | meaning |
|---|---|---|---|
| kept | kept | plain text | untouched by either |
| deleted | deleted | `<del class="outer">` | both remove it — no change *between* the two amendments |
| kept | deleted | `<del>` | a deletion newly introduced by this amendment |
| deleted | kept | `<del class="outer"><ins>…</ins></del>` | this amendment **un-deletes** it |
| inserts `x` | inserts nothing | `<ins class="outer"><del>x</del></ins>` | this amendment drops the parent's insertion |
| inserts nothing | inserts `y` | `<ins>y</ins>` | text this amendment adds on its own |
| inserts `x` | inserts `y` | `<ins class="outer">` + `computeLineDiff(x, y)` + `</ins>` | both insert here — how the child rewrites the parent's insertion is diffed *within* the outer element |

The last row is where the pipeline recurses: the two insertion strings are fed back into
`Diff::computeLineDiff()` (§6), and its markers become the inner layer.

`TwoLayerDiff::parseDiff()` is what makes the table applicable. It decomposes each side's marker
string into the only two things that can be compared across amendments:

- **spans** — which byte ranges of the original are kept and which are deleted (they tile it completely),
- **inserts** — what is inserted at which offset of the original.

The union of all span boundaries and insert offsets gives the positions at which the table is
evaluated.

### 11.4 Markers and the two-pass renderer

The outer layer needs its own markers. They use a prefix chosen so that the *existing* patterns
cannot match them — every pattern in `DiffRenderer` requires three `#` immediately before `INS_`/`DEL_`,
which `###OUTER_INS_START###` does not have:

| Layer | Markers |
|---|---|
| inner | `###INS_START###` … (unchanged) |
| outer | `###OUTER_INS_START###`, `###OUTER_INS_END###`, `###OUTER_DEL_START###`, `###OUTER_DEL_END###` |

`DiffRenderer` was made parameterizable over this prefix (`setMarkerPrefix()`), and
`renderTwoLayerHtmlWithPlaceholders()` renders in **two passes**:

```
###OUTER_INS_START###123456###DEL_START###7###DEL_END###…###OUTER_INS_END###
        │
        ▼  pass 1: markerPrefix = "OUTER_", ins/del callbacks add the outer marking
<ins class="outer">123456###DEL_START###7###DEL_END###…</ins>
        │
        ▼  pass 2: the regular renderer, unchanged
<ins class="outer">123456<del>7</del><ins>8</ins></ins>
```

This works only because of one guarantee from the generator: **inner markers never cross the
boundary of an outer one.** `TwoLayerDiff::switchTo()` closes any open inner marker before changing
the outer state, so pass 2 always receives properly nested input — which is why the delicate
single-level state machine of §7 did not have to be rewritten to handle nesting.

Block elements need a second adjustment. A paragraph can be changed by both layers at once (the
parent deleted it, the child restored it), and one `class` attribute has to express both. The outer
layer therefore uses its own class names:

| | inline | block |
|---|---|---|
| inner | `<ins>` / `<del>` | `class="inserted"` / `class="deleted"` |
| outer | `<ins class="outer">` / `<del class="outer">` | `class="insertedOuter"` / `class="deletedOuter"` |

so `<p class="deletedOuter inserted">` unambiguously means "deleted by the parent, restored by this
amendment". CSS in `web/css/_elements.scss` renders the outer layer as a muted background tint and
leaves the red/green to the inner layer.

### 11.5 Deliberate design decisions

- **The outer layer is exactly what the parent amendment's own page shows** — same engine, same
  heuristics, no re-diffing. Where §6.3 renders a word change as a deletion plus an insertion, the
  outer layer does so too. The alternative (re-running `computeWordDiff()` on the
  original/parent/child triple) would give a tighter rendering but make the two views of the same
  parent amendment disagree with each other.
- **The change-ratio limits of §6.4 stay active for both layers.** It is tempting to suppress them
  here, on the grounds that collapsing a paragraph into one big deletion plus insertion is coarse
  enough with one layer. In practice the opposite is true: without them, every stray `der`/`den`
  that the LCS matches in both versions becomes an unchanged island that tears the outer deletion
  and the outer insertion into fragments, and the reader can no longer see which text belongs to
  which proposal. With them, a heavily rewritten paragraph comes out as

  ```
  <del class="outer">what the statute says</del>
  <ins class="outer">what the amended amendment proposes,
                     with this amendment's changes diffed inside it</ins>
  ```

  which is both readable and structurally identical to what the parent amendment's own page shows.
  The inner sub-diff (parent's insertion vs. the child's) rarely trips the limits, because a child
  amendment starts from a copy of its parent's text; when it does, the child really has replaced
  the parent's proposal wholesale and `<ins class="outer"><del>…</del><ins>…</ins></ins>` is the
  honest rendering.

  Note that the two layers make this decision *independently*: the parent's diff may collapse while
  the child's does not, or they may collapse at different boundaries. That is not a problem — the
  combination table stays well-defined, and the result reads as "this amendment deletes the whole
  paragraph, including what the parent added, and replaces it with …".
- **Exactly two layers.** The data model does not forbid an amendment to an amendment to an
  amendment, but the view always compares against the *direct* parent only.
- **The outer layer is context, not a change.** In "only changed paragraphs" mode a paragraph that
  only the *parent* touched must not show up. `AffectedLinesFilter::filterAffectedBlocks()` therefore
  tracks open `<ins>`/`<del>` elements as a **stack** (they can nest now) and ignores those carrying
  the `outer` class; `DiffRenderer::paragraphContainsDiff()` skips them too.

### 11.6 Failure handling

`TwoLayerDiff::computeParagraphs()` returns `null` rather than guessing whenever it cannot build a
consistent result:

- an input diff contains nested or unbalanced markers,
- the kept + deleted parts of a diff do not add up to the original text again,
- the two sides disagree on the paragraph count or the original text.

The `null` propagates up through `AmendmentSectionFormatter::getTwoLayerDiff*()` and
`TextSimpleCommon::getAmendmentFormattedAgainstParentAmendment()`, and the view falls back to
rendering the two amendments' diffs next to each other **for that section only**. Section types that
are not diffed paragraph by paragraph (title, image, PDF, tabular data) inherit
`ISectionType::getAmendmentFormattedAgainstParentAmendment()`, which returns `null` unconditionally
and takes the same path.

Exports (PDF, ODT, LaTeX, plain text) and the REST API are untouched and still show the diff against
the original motion text only.
