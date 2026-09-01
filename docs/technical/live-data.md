# Live Data: keeping widgets up to date

Several widgets need to show data that changes while the page is open: the speaking lists (user- and
admin-facing, inline and on the fullscreen projector) and the "Currently debated" widget. There are
two ways they can learn about changes:

- **Live events**: if the Antragsgrün Live server is configured (`live` in `config.json`), the backend
  publishes changes to RabbitMQ (`LiveTools::sendSpeechQueue()`, `sendDebate()`, …), and the browser
  receives them over a STOMP websocket connection.
- **Polling**: the REST API is asked for the current state at a fixed interval.

Neither of those is the widgets' business. They register their interest in a *channel* with the
central JS module and get called back with new data; whether that data arrived over the websocket or
was polled, and from which URL, is decided centrally.

---

## Channels

A channel is a **role** (whose view of the data: `user` or `admin`) plus a **topic** (`speech`,
`debate`). It is defined once, server-side, in `components/LiveDataChannels.php`:

| Channel | Poll URL | Auth | Default interval | Kind |
|---|---|---|---|---|
| `user/speech` | `/rest/<consultation>/speech/QUEUEIDS` | JWT if available | 3000 ms | keyed |
| `admin/speech` | `/rest/<consultation>/speech/QUEUEIDS/admin` | JWT | 1000 ms | keyed |
| `user/debate` | `/rest/<consultation>/debate` | JWT if available | 3000 ms | plain |
| `user/voting` | `/rest/<consultation>/votings/open` | JWT | 3000 ms | collection |
| `admin/voting` | `/rest/<consultation>/votings/admin` | JWT | 2000 ms | collection |

The intervals can be changed per installation using the `polling` setting of `config.json`, keyed by
the channel ID:

```json
"polling": { "user/speech": 5000, "user/debate": 5000 }
```

or, equivalently, using one environment variable per channel - the name of the channel in upper case,
with the slash replaced by an underscore:

```
POLLING_INTERVAL_USER_SPEECH=5000
POLLING_INTERVAL_USER_DEBATE=5000
```

A configured interval is binding: unlike the default, it cannot be undercut by a widget asking for
more frequent updates (see `intervalMs` below), so that the load an event causes stays predictable.

**Keyed** channels do not carry one global stream, but the state of specific objects — currently
speaking lists. Widgets pass the ID of the list they show; the JS module collects the IDs of all
registered widgets and substitutes them into the `QUEUEIDS` placeholder, so a page showing three
speaking lists still only issues one request. The `admin/speech` endpoint accepts a comma-separated
list of IDs for exactly this reason, just like the user-facing one.

**Collection** channels carry a list whose members come and go — currently the votings. A poll
answers with the whole list and is authoritative: whatever it does not contain has left the
collection. A live event carries a single member, which is merged into the list by its ID; an event
marked `partial` describes only part of a member and is merged field by field, leaving everything it
does not mention as it was (a cast vote reports the counting alone, see
[voting-live-data.md](voting-live-data.md) §6). A member marked `removed` has left the collection and
is dropped from it — polls say that on their own by not listing it any more, but a client with a live
connection stops polling, so an object that is deleted has to be able to say so itself. Widgets
always receive the whole list and filter it themselves, because which members a page shows is
nothing the backend can decide for it: the same list feeds the votings page, the widget on a motion
and the one embedded in the debate.

## Declaring channels in a view

```php
$layout->addLiveDataChannel(LiveDataChannels::ROLE_USER, LiveDataChannels::CHANNEL_SPEECH);
$layout->provideJwt = true; // every channel authenticates by JWT, so this is always needed
```

All channels authenticate by JWT: `RestBase` turns the session off for the user component, so the
REST endpoints only ever see the token. A channel polled with just the session cookie would be
answered as a guest - which is why there is no session-authenticated channel type. The difference
between `jwt` and `jwt-optional` is only what happens when the view forgot `provideJwt`: the former
fails the channel, the latter falls back to an anonymous request.

`views/layouts/main.php` renders the configuration of all declared channels into the
`live-data-config` meta tag, and loads the STOMP client if a Live server is configured. The meta tag
is written **regardless** of whether a Live server exists: without one, the `live` key is `null` and
the widgets simply poll.

A widget can only register for a channel its view declared - otherwise `LiveData.js` throws.

## Reader languages

A REST response is rendered for the one user who requested it, in the language they are browsing the
site in. A live event is different: it is published **once per consultation** and delivered to
everyone reading it - who may well be using a different language than the moderator whose click
triggered it, or than the console command that sent it.

### How a REST request states its language

The API is stateless: it authenticates by JWT and never touches the session (`RestBase`), so it
cannot look up the language the user picked with the language switcher - that pick lives in the
session. The client has to say it instead, in the **`Accept-Language`** header:

* `ApiClient.js` sends the language the current page was rendered in (`<html lang>`, which
  `views/layouts/main.php` fills from `LanguageTools::getCurrentLanguage()`) on every API request.
  Since that value *is* the user's pick, the widgets answer in the same language as the page around
  them - per browser tab rather than per user, the same granularity, and for the same reason, as the
  destination-based language of the live subscriptions below.
* Other API clients get their own `Accept-Language` honoured, falling back to the consultation's
  primary language when it names nothing the site supports.

`Accept-Language` rather than a header of our own because it is CORS-safelisted: a custom header
would add a preflight `OPTIONS` request to every single poll.

Server-side this is `LanguageTools::resolveCurrentLanguage()`, which skips its session lookup when
`RequestContext::isRestApiRequest()`. It writes nothing back - a session write from the API is a bug,
and the session backends (`RestSessionGuard`) reject one.

**Every REST call from the frontend must therefore go through `apiFetch()` or `authorizedFetch()`**
(`web/js/modules/shared/ApiClient.js`); a plain `fetch()` would send the browser's language
preference rather than the page's, so a reader who picked a language differing from their browser
setting would get widgets in the wrong one.

Every string of a payload that depends on the reader's language is therefore an
`app\models\api\LocalizedString` (the motion title, the title of a speaking list, …) instead of a
plain string. It holds a callback rather than a rendered string, and `LocalizedStringNormalizer`
decides what ends up in the JSON:

| Consumer | Serialized as | Rendered |
|---|---|---|
| REST response | `"Redeliste zu A4"` | the reader's language only |
| Live event | `{"de": "Redeliste zu A4", "en": "Speaking list for A4"}` | every language of the consultation |

`LiveTools` is the only place setting the `CONTEXT_ALL_LANGUAGES` flag that switches to the second
form, and it adds the consultation's primary language as the `default_language` message header. The
Live server resolves both back into a plain string per subscriber, so **widgets always see a plain
string**, no matter where their data came from.

The votings are the one channel whose payload the Live server does not know field by field - it only
decides which sections of it a subscriber may see - so it finds the localized strings within it by
their shape instead: an object keyed by exactly the languages the event states it was rendered in
(`languages`). Same resolution, different way of finding what to resolve.

Which language a subscriber gets is part of the **destination** they subscribed to:

```
/user/<installation>/<subdomain>/<consultation>/<user id>/<channel>/<language>
```

It is deliberately not a claim of the JWT. A JWT identifies a person, and the same person can read
the consultation in two browser tabs in two languages: both tabs share a user id, the Live server
addresses destinations rather than people, and Spring's user registry only keeps the principal of
the connection that happened to arrive first - so a language taken from the token would give both
tabs the same wording, and keep doing so until the last of them disconnects. A destination is per
subscription, which is exactly the granularity the language needs. The language is not authorized:
it selects a wording, not access to data.

The last part may be missing, which is how a Live server of a newer Antragsgrün recognizes a client
of an older one; those subscribers get the `default_language` of the event.

What this means when adding a field to a live payload: if its value depends on who is reading it
(anything going through `\Yii::t()`, or content that exists per language), it has to be a
`LocalizedString`, and the Live server's matching MQ DTO field an `MQLocalizedText`. On a
single-language site nothing changes - the map simply has one entry.

The same holds for fields a *future* language could reach: a speaking list's subqueue names
(`SpeechSubqueue::$name` and its copy on each slot, `SpeechQueueActiveSlot::$subqueueName`) are
backed by one database column today and therefore resolve to the same text in every language, but
they travel localized, so storing them per language later needs no change to the payloads, the Live
server, or the widgets.

Which class to change is decided by what is actually published: `LiveTools` serializes
`app\models\api\SpeechQueue` and `debate\DebateState`, so those - and everything they contain -
carry `LocalizedString`s. The `models/api/speech/*` DTOs are REST-only views derived from them
(`SpeechController` returns nothing else) and resolve the value with `->get()` instead. If the class
is one of the generated ones (`docs/openapi.yaml` → `docs/openapi-generate-dtos.php`), the property
type has to be declared in the spec with the `x-php-type` vendor extension - editing the PHP alone
is undone by the next regeneration, and the OpenAPI type stays `string`, which is what API clients
receive:

```yaml
        title:
          type: string
          x-php-type: '\app\models\api\LocalizedString'
```

Two things to watch out for when building one:

- The callback is invoked once per language, so it must not be built from something already rendered
  in the current user's language, and must not use caches that are not keyed by language
  (`IMotion::getInitiatorsStr()` is the one such cache these payloads would otherwise hit).
- Only the *live* payloads need this. Poll-only payloads (`DebateSelectables`, the admin speech
  endpoints) are rendered for their requester and stay plain strings.

## Registering a widget

```js
import { registerListener } from "/js/modules/shared/LiveData.js";

const handle = registerListener('user', 'speech', {
    key: queueId,          // only for keyed channels
    intervalMs: 1000,      // optional, lowers the channel default (the lowest value wins); ignored
                           // if the interval is set in the configuration
    initialFetch: false,   // for widgets rendered without any data, see below
    onData: (queue) => { … },
    onError: (err) => { … }, // optional
});

handle.setKey(otherQueueId);  // the widget now shows a different speaking list
handle.refreshNow();          // after a change this widget made, so the others see it too
handle.unregister();          // in beforeUnmount()
```

### What the module does

- Polls a channel **only** while at least one widget is registered, the tab is visible, and the live
  connection for that channel is *not* established. Live data always wins over polling.
- Fires one request per channel, no matter how many widgets are registered, and hands the response to
  the widgets by key. Live events (which carry a single object) are dispatched the same way.
- Never stacks requests: a refresh asked for while a request is in flight is performed afterwards.
- Retries failing requests with a growing delay (up to 30 s), so a backend restart does not stop a
  projector for good. A `401`/`403` is retried a few times with a freshly fetched JWT - a token can be
  rejected although the browser still considered it valid - and stops the channel only afterwards, or
  right away if there is no token to renew (a page that did not set `provideJwt`).
- Refreshes when the tab becomes visible again, and after a websocket **re**connect - live events
  only carry changes, so everything that happened during the outage would be missing otherwise.

### The initial data

Live events never contain the current state, only changes to it - so the state a widget starts with
always comes from somewhere else, and the module has to decide whether that state can be trusted.

The module remembers when it was loaded. A widget registering **more than five seconds** after that
was not rendered together with the page - it belongs to the fullscreen projector, which may be opened
hours later, or it was created by another widget - so its initial data is assumed to be stale and the
current state is loaded right away. Widgets registering during page load use the state the backend
rendered them with and cause no extra request.

`initialFetch: true` forces that request regardless of the timing. It is only needed for widgets that
have no data at all to begin with, like the debate widget's speaking list.

## Where the widgets are

- `web/js/vue/speech/SpeechCommonMixins.js` - shared by all user-facing speaking list widgets
  (footer, inline, full page, fullscreen). Components can set `pollIntervalMs` / `initialFetch` in
  their own `data()`.
- `web_src/js/vue/speech/AdminWidget.vue` - the moderation widget (`admin/speech`).
- `web_src/js/vue/debate/CurrentDebateWidget.vue` - registers `user/debate` for the debate state and
  `user/speech` for the speaking list of the debated item.
- `web_src/js/vue/debate/DebateAdminWidget.vue` - the moderation widget. It follows `user/debate` as
  well: the debated item is the same data for moderators, and this way a debate started by another
  moderator (or in another tab) shows up here without a Live server channel of its own. The speaking
  list inside it updates through the embedded admin widget; the voting tab is still loaded on demand.

- `web/js/modules/frontend/VotingBlock.js` and `web/js/modules/backend/VotingAdmin.js` - the voting
  widgets (`user/voting` and `admin/voting`). Which votings a page shows is decided in the browser:
  the widget is given the ID of the motion it belongs to, or nothing at all on the votings page.
  `/voting-results` shows votings that are over and therefore registers no channel at all.
