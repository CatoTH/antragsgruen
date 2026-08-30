# Votings on the Live-Data infrastructure

The data structures for moving the voting widgets onto the Live-Data mechanism.
Status: **not implemented yet**; §9 records the decisions behind the structures below.

Goal: replace the self-made polling of the voting widgets (`web/js/modules/frontend/VotingBlock.js`,
`web/js/modules/backend/VotingAdmin.js`, `CurrentDebateWidget.vue`) by the central Live-Data
mechanism described in `docs/technical/live-data.md`: REST for polling, RabbitMQ → Live proxy →
STOMP when a Live server is configured.

---

## 1. What the design has to work around

**The Live proxy can only *select*, not *decide*.** It knows, per connected client:

* the JWT subject (`login-<userId>` / `anonymous-<token>`),
* the roles in the JWT (today only `ROLE_SPEECH_ADMIN`; we would add `ROLE_VOTING_ADMIN`,
  granted when the user holds `PRIVILEGE_VOTINGS`),
* the consultation scope from the routing key.

It knows nothing about user groups, voting policies, `Vote.public`, deadlines or privilege
restrictions, and it must not have to.

**Proposed principle: the proxy performs no business logic at all.** For speech, the Java mappers
re-implement backend logic (`SpeechUserMapper` computes `haveApplied`, applies `showNames`). That
means the same rule exists twice, in two languages, and every new rule needs a Java release. For
votings — where the rules are the confidentiality guarantees — that is a bad trade. Instead: the
backend pre-computes one payload per audience, and the proxy only *picks* the parts a given
connection may see.

Consequence: one generic mapper serves every future topic, and the security argument is a
three-line rule instead of a code review of the Java mapper.

## 2. Visibility model

The message posted to the proxy is an envelope with three explicitly scoped sections:

| Section | Delivered to | Rule for the backend |
|---|---|---|
| `everyone` | every subscriber of the consultation | may only contain data every participant may see |
| `admin_only` | subscribers whose JWT has `ROLE_VOTING_ADMIN` | may only contain data every `PRIVILEGE_VOTINGS` holder may see |
| `per_user` | map, key = JWT subject; each entry only to that one subject | anything personal |

Proxy mapping, complete:

```
user  DTO = everyone                + { me: per_user[sub] ?? DEFAULT_ME }
admin DTO = everyone ∪ admin_only   + { me: per_user[sub] ?? DEFAULT_ME }
```

The three confidentiality settings map onto this without the proxy knowing them:

* `votesPublic = VOTES_PUBLIC_ALL` → the vote list is serialized into `everyone`.
* `votesPublic = VOTES_PUBLIC_ADMIN` → the vote list is serialized into `admin_only`.
* `votesPublic = VOTES_PUBLIC_NO` → **the vote list is not serialized at all**, in no section, in
  no message. Secrecy then does not depend on the proxy behaving correctly, only on the backend not
  writing the field. Same for the polling endpoints, which build the identical DTOs.
* `resultsPublic = RESULTS_PUBLIC_NO` → the per-item result counts go to `admin_only` only, and so
  does `abstention.count`: how many people abstained is a result like any other.
* **Turnout is not a result**: `statistics.votes` / `statistics.voters` stay in `everyone`
  regardless of `resultsPublic`. They say how many have voted, never how anyone voted - and the
  widgets show "143 of 200 have voted" while a secret vote is running.

`Vote.public` — the publicity a vote was cast under — is honoured **per vote**: each single vote is
placed into `everyone` or `admin_only` or dropped according to its own `public` value, not according
to the block's current setting. This generalizes today's `getFilteredVotesList()` safeguard into the
data structure itself. What that value is no longer depends on anything the client sends: it is
`min(publicity when the voting was opened, publicity now)`, computed server-side (D3).

## 3. What gets normalized away

Compared to `AgendaVoting::getApiObject()`:

| Today | Proposal |
|---|---|
| `votes` / `vote_results` / `quorum_votes` / `vote_eligibility` repeated **identically on every item of an item group**; the JS only ever reads `groupedVoting[0]` | results and votes hang off the **item group**; items only reference their group |
| ungrouped items vs. `item_group_same_vote` handled differently everywhere (JS `groupedVotings`, the vote POST body, `ResourceLock`) | every item belongs to exactly one group; standalone items get a synthetic group id. One code path |
| `vote_eligibility` (full user lists!) duplicated per item | one `eligibility` list per block |
| `answers` + `answers_template`; JS derives `votingHasMajority` / `votingIsPresenceCall` from template constants | `answers` is the canonical list; `has_majority` / `is_presence_call` are sent as booleans; the raw template id only in the admin `settings` object |
| `vote_results: {"0": {"yes": 3}}` — `"0"` is `VotingData::ORGANIZATION_DEFAULT` | explicit list `[{organization: null, counts: {...}}]` |
| closed votings store `votesYes` / `votesNo` / `votesAbstention` / `votesPresent` in `VotingData` | **unchanged in the database** (D6). The answer-keyed `results.counts` of the API is mapped from those four fields at the API boundary, as `mapToApiResults()` does today - so old rows keep working and answer templates beyond these four still have no stored result |
| general abstention = a `VotingQuestion` with the magic title `{GENERAL ABSTENTION}`, filtered out of `items` in five places, plus `has_general_abstention` / `abstentions_total` / `abstention_users` | one `abstention` object on the block; the magic question never appears in `items`. **The database is unchanged** (D5): the magic question stays, and the API is the only place that knows it is not an item |
| `quorum`, `quorum_custom_target`, `quorum_eligible` (block) + `quorum_votes`, `quorum_custom_current` (item) | one `quorum` object per block + one per item group |
| `votes_total`, `votes_users` | `statistics` object |
| `abstentions_total` (sent to everyone today) | `abstention.count`, alongside the count's audience rules |
| `votes_public` / `results_public` / `votes_names` raw ints, interpreted by the widget | resolved `publicity` for readers; raw values only in the admin `settings` object |
| `current_time`, `opened_ts`, `voting_time` | `current_time` (envelope), `opened_at`, `voting_time` |
| `status` int constants duplicated in `_constants.php`, `admin-votings.vue.php`, `voting-block.vue.php` | string enum (`BackedEnum`, like `DebateItemTargetType`) |
| item `voting_status` int | string enum |

## 4. Message posted to the Live proxy

Routing key `voting.<installation>.<subdomain>.<consultationPath>` — one message per **voting block**
(as for speech queues), not per consultation, so that a client can merge by id.

```jsonc
{
  "kind": "full",                  // "full" | "tally", see §7
  "block_id": 42,
  "state_version": 17,             // monotonic per block; clients drop out-of-order messages
  "current_time": 1724740000123,   // ms, for clock-skew correction of the countdown

  "everyone": { /* VotingBlockPublic, §5 */ },

  "admin_only": { /* VotingBlockAdminExtra, §6 — null if nothing admin-specific changed */ },

  "per_user": {                    // only in "full" messages
    "login-42": { /* VotingUserState, §5.3 */ },
    "login-77": { /* … */ }
  }
}
```

`per_user` contains an entry for every user who is eligible to vote or has already voted; everybody
else gets `DEFAULT_ME` (not eligible, no votes). For a 2000-delegate consultation that is roughly
100 KB — acceptable because `full` messages are triggered by admin actions (open / close / reset /
settings / items), not by cast votes (§7).

## 5. What clients receive: `VotingBlockUser`

This is *both* the body of the STOMP message on `user/voting` *and* the response of the polling
endpoint — byte-identical, as with `SpeechQueueUser` today.

### 5.1 Block level

```jsonc
{
  "id": 42,
  "title": "Amendments to A1",
  "status": "open",                       // offline|preparing|open|closed_published|closed_unpublished
  "assigned_motion_id": 123,              // null = shown on the votings page; used for client-side filtering
  "opened_at": 1724739000000,             // ms, null unless open
  "voting_time": 300,                     // seconds, null = no countdown
  "current_time": 1724740000123,

  "answers": [                            // canonical, ordered; identity is api_id
    {"api_id": "yes", "title": "Yes", "status_id": 5},
    {"api_id": "no", "title": "No", "status_id": 6},
    {"api_id": "abstention", "title": "Abstention", "status_id": null}
  ],
  "has_majority": true,                   // ex-answers_template logic, server-side
  "is_presence_call": false,

  "publicity": {                          // resolved, not the raw enums
    "single_votes": "nobody",             // nobody | admins | everybody
    "results": "everybody"                // admins | everybody
  },

  "quorum": {                             // null if NoQuorum
    "type": 2, "target": 100, "target_label": null, "eligible": 200
  },

  "statistics": {"votes": 143, "voters": 143},   // turnout, always visible (D1)

  "abstention": {"enabled": true, "count": 4},   // "count" only where results are visible, "users" only in the admin DTO

  "item_groups": [ /* §5.2 */ ],
  "items":       [ /* §5.2 */ ],

  "me": { /* §5.3 */ }
}
```

### 5.2 Items and item groups

```jsonc
"items": [
  {
    "type": "amendment",                  // motion | amendment | question
    "id": 3,
    "group_id": "abc123",
    "prefix": "Ä1",
    "title_with_prefix": "Ä1 to A1",
    "initiators_html": "…",
    "url_html": "…",
    "url_json": "…",
    "procedure_html": "…",                // ex-"procedure"
    "voting_status": "accepted"           // enum; null while running
  }
],

"item_groups": [
  {
    "id": "abc123",                       // VotingData::itemGroupSameVote, or "single:amendment:3"
    "name": "Block A",                    // null for synthetic single-item groups
    "items": [{"type": "amendment", "id": 3}],

    "results": {                          // absent if this audience may not see results
      "counts": [
        {"organization": null, "votes": {"yes": 120, "no": 12, "abstention": 4}}
      ],
      "quorum": {"votes": 130, "current_label": null}
    },

    "single_votes": [                     // absent unless this audience may see single votes
      {"answer": "yes", "user_id": 12, "user_name": "…", "user_group_ids": [3], "weight": 1}
    ]
  }
]
```

*A section the reader may not see is `null`, never an empty list* — so a client can never render
"0 votes" where it should render nothing, and a bug shows up as a missing section rather than as a
wrong result. (The DTOs make this an explicit `null` in the JSON rather than an absent key, which
says the same thing and is what a typed payload can express.)

### 5.3 `me` — `VotingUserState`

Everything personal, pre-computed by the backend (the proxy copies it verbatim):

```jsonc
{
  "eligible": true,                 // may vote here in principle (policy + user groups)
  "vote_weight": 1,
  "votes_remaining": 2,             // null = unlimited (maxVotesByGroup)
  "abstained": false,
  "votes": [{"group_id": "abc123", "answer": "yes"}],   // my own votes, whatever the publicity
  "can_vote_group_ids": ["def456"]  // groups I may vote on *right now*
}
```

`DEFAULT_ME` = `{eligible: false, vote_weight: 1, votes_remaining: null, abstained: false,
votes: [], can_vote_group_ids: []}`.

The vote POST carries no publicity of its own any more: how public a vote may become is decided
server-side (§2, D3). `publicity.single_votes` in the block is what the widget tells the voter before
they cast, nothing more.

## 6. `VotingBlockAdmin` — `everyone` ∪ `admin_only`

```jsonc
{
  /* …all of §5, plus: */

  "settings": {                     // raw configuration, for the admin form
    "votes_public": 1,
    "results_public": 1,
    "votes_names": 0,
    "answers_template": 0,
    "majority_type": 1,
    "quorum_type": 2,
    "voting_time": 300,
    "assigned_motion_id": 123,
    "policy": {"id": 5, "user_group_ids": [3, 4]},
    "max_votes_by_group": [{"group_id": null, "max_votes": 3}]
  },

  "user_groups": [                  // selectable groups incl. member counts (frozen after closing)
    {"id": 3, "title": "Delegates", "member_count": 120}
  ],

  "eligibility": [                  // once per block, not once per item
    {"group_id": 3, "title": "Delegates",
     "users": [{"user_id": 1, "user_name": "…", "weight": 1}]}
  ],

  "log": [{"type": "opened", "date": "2026-08-27T10:00:00+02:00"}],

  "editable": {                     // ex-itemsCanBeAdded()/itemsCanBeRemoved(), today re-derived in JS
    "items_can_be_added": true,
    "items_can_be_removed": true,
    "settings_can_be_changed": true
  },

  "abstention": {"enabled": true, "count": 4,
                 "users": [{"user_id": 12, "user_name": "…", "user_group_ids": [3]}]},

  /* and, in item_groups[], the "results"/"single_votes" that the public DTO omitted */
}
```

The "who has not voted yet" list stays a client-side diff of `eligibility` against
`item_groups[].single_votes` — it is only meaningful when single votes are visible anyway.

## 7. Message frequency: `full` vs. `tally`

Publishing a `full` message on every cast vote would mean, for a 2000-delegate vote, 2000 messages
of ~100 KB each, fanned out personally to 2000 connections. So:

* **`full`** — complete block state incl. `per_user`. Triggered by admin actions: open, close,
  reopen, reset, delete, settings change, item add/remove, sort, changed group membership.
  A handful per voting.
* **`tally`** — no `per_user`, no `settings`, no `eligibility`. Carries only
  `statistics`, `item_groups[].results`, `item_groups[].single_votes` (in whichever scope the
  publicity allows) and `abstention`. Published on every cast vote; throttling, if it is ever needed,
  happens in the Live server (see below).

Merge contract on the client: a `tally` message replaces exactly those four fields and leaves
everything else — importantly `me` — untouched. `me` for the acting user is updated from the
response of their own vote POST; no other user's `me` changes when someone votes.

Because `tally` has no per-user content, every recipient of a given role gets a byte-identical
message; the proxy may later broadcast it to a shared topic instead of fanning out N copies.

`state_version` guards against reordering and lets a client detect gaps; LiveData.js already
refetches after a websocket reconnect, so drift self-heals.

### Where the throttling happens

A `tally` is small, but it goes to everyone: 2000 delegates voting within two minutes are ~17 votes
per second, and at one message per vote each of the 2000 open connections would receive ~17 updates
per second. The number that has to come down is the *fan-out*, not the number of MQ messages -
so the throttling belongs in the Live server, and nowhere else.

Antragsgrün publishes a `tally` on every cast vote and keeps no state of its own. The Live server
holds the newest tally per block and role and flushes it to the subscribers at most every *N* ms
(Spring's `TaskScheduler`, next to the existing heartbeat scheduler). RabbitMQ then carries ~17
messages/s of a few KB, which is nothing, and the websocket traffic is bounded by *N* however fast
people vote. Coalescing there also solves "the last update must not be lost" for free: the flush
timer always fires after the final message. Rules for the scheduler: coalesce `tally` only, deliver
`full` immediately, and drop a pending tally that a `full` has overtaken.

This is a later step, not a prerequisite: without it every tally is delivered as it arrives, which is
correct, just chattier. The interval then belongs in `config.json` next to the existing `polling`
intervals, being the same kind of knob - how much load an installation spends on freshness.

Throttling in Antragsgrün itself is deliberately **not** part of this design. PHP-FPM has no timers,
so anything built there would be a leading-edge rate limit over the cache, which drops updates
without being able to send the trailing one - and it would save the fan-out nothing.

### What publishing costs the PHP side

For the same 2000-delegate vote: ~17 publishes per second, each one built and sent synchronously
inside the vote POST that triggered it.

* **The RabbitMQ call itself is the cheap half.** `LiveTools::sendToRabbitMq()` builds a new Guzzle
  client per call and posts to the *management* HTTP API, so every publish is a fresh TCP connection,
  an HTTP basic auth (which RabbitMQ hashes per request) and a JSON round-trip through the management
  plugin - a few ms on localhost, tens of ms across a network with TLS. At 17/s that is a fraction of
  one worker on average; what it really costs is latency added to each voter's response, and a longer
  hold on the block's `ResourceLock`, on which the votes serialize.
* **Building the tally is the expensive half, and it grows.** `getVoteStatistics()` walks
  `$block->votes` as hydrated ActiveRecord objects and additionally every motion and amendment of the
  consultation to rebuild the item-group maps; `Vote::calculateVoteResultsForApi()` walks the votes
  again per item group. The n-th vote therefore hydrates n vote rows - about 2 million row
  hydrations over the run, 2000 of them on the last vote alone.

Two things put that in perspective. The vote POST already pays this cost today, for the response it
returns to the voter; publishing doubles it rather than introducing it. And it replaces polling:
2000 delegates polling every 3 s are **~670 requests per second**, each one doing the same work for
the whole open-voting list. Publishing at 17/s is roughly 2.5% of the load the live path removes.

What to do about it, in the order the effort pays off:

1. Compute statistics and results with aggregate SQL (`SUM(weight)`, `COUNT(DISTINCT userId)`,
   `GROUP BY`) instead of hydrating every `Vote`. That removes the quadratic term, and helps the
   polling path just as much.
2. Publish after `ResourceLock::releaseAllLocks()`, and ideally after the response has been flushed,
   so that neither the lock nor the voter waits for RabbitMQ.
3. Only if the rate ever justifies it: replace the management API with a real AMQP client and a
   persistent connection. At 17/s it does not.

One robustness note, which applies to the existing speech events too: `sendToRabbitMq()` throws when
the publish fails *or* when RabbitMQ reports the message as unrouted (no queue bound). Inside a vote
POST that would turn a broker hiccup, or a Live server that has never declared its queues, into a
failed vote. A missed tally is cosmetic; the vote is not. Publishing must not be able to fail the
request that triggered it.

## 8. Channels and endpoints

| Channel | Poll URL | Auth | Default interval |
|---|---|---|---|
| `user/voting` | `GET /rest/<consultation>/voting?status=open` | JWT | 3000 ms |
| `admin/voting` | `GET /rest/<consultation>/voting/admin` | JWT | 2000 ms |

Both are **collection channels**: the poll response is a list of `VotingBlock*` objects, a live
message carries a single one, and the client merges it into its collection by `id`. This is the
keyed-channel idea of `LiveData.js` turned around — the client does not know the ids in advance
(a voting that is opened has to appear), so the key comes out of the message instead of out of the
widget. This is the one extension the JS module needs.

Filtering that the proxy cannot do (`assigned_motion_id`, "only open ones") happens client-side;
the poll URL takes the same filter as a query parameter so that the initial fetch stays small.
`/voting-results` (closed & published) keeps its own filter and needs no live channel — it changes
once, at closing time, which produces a `full` message the collection channel picks up anyway.

Also needed: `ROLE_VOTING_ADMIN` in `JwtCreator::getJwtConfigForCurrUser()` for holders of
`PRIVILEGE_VOTINGS`, and the corresponding entry in the proxy's `TopicPermissionChecker`.

## 9. Decisions

* **D1 — Turnout is public, the abstention count is not.** `statistics.votes` / `statistics.voters`
  go to everyone even when `resultsPublic = NO`; `abstention.count` follows the result publicity,
  because how many people abstained is a result. See §2 and §5.1.
* **D2 — Personal state travels in the `per_user` map**, built server-side, rather than being derived
  in the browser from what a client remembers of its own POSTs. The voting rules stay in PHP; the
  price is the ~100 KB `full` message of a 2000-voter block (§4).
* **D3 — The publicity a vote is stored under is decided server-side.** The vote POST no longer
  echoes back what it was shown. See below.
* **D4 — `eligibility` is one list per block**, not one per item, even though each item stores its own
  `VotingData::eligibilityList` snapshot after closing (they are written in the same operation and are
  identical in practice). See §6.
* **D5 — The database keeps the general abstention as it is**: a `VotingQuestion` carrying the magic
  title `{GENERAL ABSTENTION}`. Only the API hides it, as a block-level `abstention` object that never
  appears among the items.
* **D6 — The database keeps `votesYes` / `votesNo` / `votesAbstention` / `votesPresent`** in
  `VotingData`. The answer-keyed `results.counts` is a mapping at the API boundary
  (`mapToApiResults()`), so rows written by earlier versions keep working unchanged, and answer
  templates outside those four keep having no stored result.
* **D7 — The proposed-procedure export keeps a DTO of its own.** Reusing `VotingBlockUser` with
  `me = DEFAULT_ME` would have added results and turnout to a payload that has never carried any
  (`setApiObjectResultData()` runs for the admin and result contexts only), which is a visible change
  for whoever consumes `views/consultation/proposed_procedure_rest_get.php`. A separate slim DTO keeps
  that payload byte-identical, at the price of a third shape to maintain.

### D3 in detail: the publicity a vote is stored under

A vote is cast; how public may it become? The answer is `min(publicity when the voting was opened,
publicity now)`, evaluated server-side when the vote is inserted, and stored in `Vote.public` as
today.

This replaces the round-trip in which the client echoed the `votes_public` it had been shown and the
server stored `min($echoed, $current)`. Both cover the race the echo was built for - an admin raising
the publicity while a voter looks at a page that still says "secret" - because the run was opened as
secret and stays so. The snapshot additionally fixes two things the echo cannot:

* A client could send a *lower* value than it was shown; `min()` accepted it, because that direction
  looks like the safe one. In an explicit roll-call vote, a modified client could thereby keep itself
  out of the published list of who voted how.
* Two voters could end up with different publicity for the same voting, depending on how fresh their
  page was. The snapshot gives one answer for the whole run.

**When the snapshot is taken.** On the transition into `STATUS_OPEN`, but only if there is none yet -
so *reopening* a closed voting keeps the original one. That is the point: reopening does not delete
the votes of the first run, and all votes of one voting have to share one promise. It is **resetting**
that clears the snapshot (`switchToOnlineVoting()`, `switchToOfflineVoting()` - the operations that
log `ACTIVITY_TYPE_RESET` and, in the online case, delete every vote); the next opening then takes a
fresh one. It lives in the `VotingBlock` settings JSON, next to `openedTs` and `votingTime`, and a
block opened before this exists simply has none, in which case only the current setting applies.

**What the code already does.** `VotingMethods::voteSaveSettings()` only applies `votesPublic` while
the block is offline or preparing, so the setting is *already* frozen from the moment a voting opens
until it is reset - the snapshot mostly writes down a rule that exists. What is not frozen is the
path through offline: `switchToOfflineVoting()` keeps the votes, so a block can be taken offline, given
a different publicity, and opened again with the old votes still attached. That is why the per-vote
`Vote.public` column stays load-bearing whatever the snapshot does, and why
`VotingVisibilityTest::testVotesKeepThePublicityTheyWereCastUnder()` pins exactly that path.

**What this changes for admins.** Raising the publicity of a running voting no longer takes effect
for that run - it would need a reset. Lowering it still applies immediately, since `min()` also looks
at the current setting. The admin UI can reflect that by locking the setting while the voting is open
(`editable`, §6).

## 10. How to build it

The design has two halves that do not depend on each other: the **payload rewrite** (§2–§6), which
the polling path can use on its own, and the **transport** (§4, §7, §8), which only adds a second way
of delivering the same bytes. Doing them in that order keeps the application shippable after every
step, and makes the Live server purely additive - nothing breaks if it is not deployed.

**Stage 0 - the safety net. Done.** The regression oracle for stages 1 and 2 is the whole of
`tests/Acceptance/voting/` (8 Cepts: grouped votings, list votes, proposed procedure, questions,
quorum, roll call, sorting, user-group policies with weighted votes) plus
`admin/VotingResultsCept`, `debate/DebateProjectorVotingCept` and
`pasterrors/DeletingMotionBreaksVotingsCept` - all green, ~2 minutes.
(`plugins/YfjVotingCept` needs a plugin that is not in this checkout and fails for that reason.)

What that suite did not cover is the visibility matrix, which is exactly what the rewrite puts at
risk, so `tests/Unit/VotingVisibilityTest.php` was added: for each combination of `votesPublic` ×
`resultsPublic` × audience it asserts which keys the payload contains **and**, independently of the
structure, that a payload nobody may see the votes in does not contain the voters' names anywhere.
The second form is the one that survives the restructuring; the first one is expected to be rewritten
along with the payload. Both were verified by mutation - neutralizing `canSeeVotes()` fails the
secret and admins-only cases, neutralizing the per-vote filter in `getFilteredVotesList()` fails the
case below.

**Stage 1 - the payload, still on polling. Done.** The bulk of the work.

1. Describe the new structures in `docs/openapi.yaml` and generate the DTOs into `models/api/voting/`
   (`docs/openapi-generate-dtos.php`), as for the speech and debate DTOs.
2. Build them from the entities in one place: a builder that produces the `everyone` / `admin_only` /
   `per_user` sections, and two assemblers that compose `VotingBlockUser` and `VotingBlockAdmin` out
   of them. The polling endpoints and, later, the publisher use the same builder - that shared source
   is what makes the confidentiality rules hold identically in both paths, and it is worth having even
   while there is only one consumer.
3. Move `controllers/VotingController.php` to `controllers/rest/` (extending `RestBase`) and return
   the DTOs. The URLs barely change: `config/urls.php` already maps `/rest/<consultation>/votings/…`
   onto those actions.
4. Replace `AgendaVoting::getUserVotingApiObject()` / `getUserResultsApiObject()` /
   `getAdminApiObject()`; keep `AgendaVoting` itself for gathering the items of a block, and give
   `getProposedProcedureApiObject()` the slim DTO of its own (D7).
5. Rewrite the widgets against the new shape and switch the server-rendered initial state in
   `views/voting/*.php`. The status constants in `views/voting/_constants.php` (and their copies) can
   go as the statuses become string enums.
6. While in there: D1 and D3 are payload semantics and belong in this stage, as does replacing the
   per-vote hydration in `getVoteStatistics()` / `calculateVoteResultsForApi()` with aggregate SQL
   (§7) - the polling path benefits from that immediately.

The widgets keep their own `setInterval` throughout this stage; nothing about the transport changes.

What stage 1 turned out to need, beyond the list above:

* `user_groups` and `policy` belong to the **participant** payload, not to the administration: the
  public list of votes is grouped by user group. Only `eligibility` - who is entitled to vote - is
  for the administration alone.
* A vote is cast for an **item group** (`{groupId, vote}`), which is what the payload offers; the
  backend maps a group of a single item back to that item. The publicity the client used to echo
  back is gone with D3.
* The statuses of the payload are the strings of `VotingStatus` in both directions: the widgets
  compare against them, and `VotingMethods::voteStatusUpdate()` accepts them.
* **The controller stays session-authenticated for now.** Moving it to `controllers/rest/` would
  disable session authentication (`RestBase` turns it off, the widgets do not send a JWT yet), so it
  belongs to stage 2 together with the switch to `LiveData`/`authorizedFetch`. The URLs are already
  the REST ones; only the base class is not.
* `IVotingItem` gained `getId()` and `getVotingResult()`: everything implementing it is an
  ActiveRecord, but nothing in the type system said so, and the builder addresses items through the
  interface throughout.
* Two things the payload rewrite uncovered rather than caused: `voteSaveSettings()` fell over an
  empty string when a form-encoded request sent a policy without user groups, and
  `getVoteStatistics()` walked the whole consultation to find the items of one voting - now it walks
  the voting's own items, skipping amendments whose motion has been deleted (which the consultation
  walk never saw, and `AgendaVoting` skips for the same reason).

**Stage 2 - polling through the central module.** Teach `LiveData.js` the collection channel of §8: a
poll response that is a list, live messages that carry one element, merged by `id`, filtered
client-side. Register `user/voting` and `admin/voting` in `LiveDataChannels`, move the widgets to
`registerListener()`, and delete `startPolling()` / `stopPolling()` from
`web/js/modules/frontend/VotingBlock.js` and `web/js/modules/backend/VotingAdmin.js` as well as the
voting interval in `CurrentDebateWidget.vue`. Still no Live server involved - this only centralizes
what the widgets already do.

**Stage 3 - publishing.** `ROLE_VOTING_ADMIN` in `JwtCreator`, and a `LiveTools::sendVoting()` that
posts the envelope of §4 with the routing key `voting.<installation>.<site>.<consultation>`. Full
messages on every mutation in `VotingMethods` / the controller, tallies on cast votes. Two rules for
the call sites: publish after `ResourceLock::releaseAllLocks()`, and never let a publishing failure
fail the request that triggered it (§7).

**Stage 4 - the Live server.** A `voting.#` queue and binding, a receiver, and one handler that
applies the rule of §2 - `everyone`, plus `admin_only` for a subscriber holding `ROLE_VOTING_ADMIN`,
plus their own `per_user` entry. Because the proxy only selects, the MQ payload can stay opaque there
(a `JsonNode` per section): no per-field Java DTOs to keep in sync with Antragsgrün, which is the
whole point of the rule. `TopicPermissionChecker` maps the `voting` topic to `ROLE_VOTING_ADMIN`, and
the tests mirror the speech ones - including one that asserts an `admin_only` section never reaches a
subscriber without the role. Deploy this **before** the publisher of stage 3 goes live: until the
queue is declared, RabbitMQ reports every voting message as unrouted.

**Stage 5 - throttling in the Live server**, if and when the message rate justifies it (§7).

Two properties worth keeping while implementing: **no database migration is needed** - the publicity
snapshot fits into the `VotingBlock` settings JSON next to `openedTs`, and `state_version` can simply
be the publish timestamp in milliseconds, which is monotonic enough for clients that only need
ordering - and every stage is independently revertable, because the payload rewrite never depends on
the transport.
