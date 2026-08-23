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

| Channel | Poll URL | Auth | Default interval | Keyed |
|---|---|---|---|---|
| `user/speech` | `/rest/<consultation>/speech/QUEUEIDS` | JWT if available | 3000 ms | yes |
| `admin/speech` | `/rest/<consultation>/speech/QUEUEIDS/admin` | JWT | 1000 ms | yes |
| `user/debate` | `/rest/<consultation>/debate` | JWT if available | 3000 ms | no |

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

The voting widgets still poll on their own; they are to be migrated separately.
