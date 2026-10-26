# egp_usersync

Provides a webhook that synchronizes externally managed user lists into the **participant** user groups of one or more consultations of a site.

## Activation

Add the plugin to the `plugins` array of `config/config.json`:

```json
{
    "plugins": ["egp_usersync"]
}
```

## Configuration: `config/egp_usersync.json`

The plugin-specific settings live in a separate file, `config/egp_usersync.json`. The plugin refuses to boot (`ConfigurationError`) if the file is missing, if one of the three keys is missing, or if `consultationMapping` is malformed.

```json
{
    "apiToken": "a-long-random-secret",
    "allowedIps": ["1.2.3.4", "2001:db8::10"],
    "consultationMapping": {
        "congress2026": "congress2026",
        "congress2027": ["congress2027-1", "congress2027-2"]
    }
}
```

### `apiToken` (string, required)

Shared secret. Every request has to send it in the `X-API-Key` HTTP header. A missing or different value is answered with `401`.

### `allowedIps` (string[], required)

Client IP addresses allowed to call the webhook. `$_SERVER['REMOTE_ADDR']` is compared literally against the entries: no CIDR ranges, no wildcards, no `X-Forwarded-For` handling. Behind a reverse proxy, the proxy's address is what is checked unless the web server rewrites `REMOTE_ADDR`. Other addresses get `403`.

### `consultationMapping` (object, required, may be `{}`)

Maps a list name (the `listName` of a list sent to the webhook) to the consultation(s) whose participant group should mirror that list.

- **Key**: the list name, matched exactly (case-sensitive).
- **Value**: either a single consultation URL path (`"congress2026"`) or a non-empty array of them (`["council2026", "congress2026"]`). Both forms are normalized to an array when the config is loaded, so a single string behaves exactly like a one-element array.
- Consultation URL paths are matched **case-insensitively** against the `urlPath` of the consultations of the current site.
- **Lists without a mapping entry** are treated as if they were mapped to a consultation with the same URL path as the list name. `{}` therefore means "list name = consultation URL path".
- Anything else (a number, `null`, an empty array, an array containing non-strings) is rejected with a `ConfigurationError` at boot time.

## Webhook

`POST https://<site>/webhook/usersync`

Headers: `X-API-Key: <apiToken>`, `Content-Type: application/json`

Body: an array of user lists:

```json
[
    {
        "listName": "Council Members",
        "users": [
            {"name": "Jane", "lastName": "Doe", "party": "Example Greens", "email": "jane@example.org"}
        ]
    }
]
```

`snake_case` keys (`list_name`, `last_name`) are accepted as well.

Checks run in this order: HTTP method (`405` if not `POST`), API key (`401`), IP address (`403`).

### Sync behavior

Each list is processed separately. First, every consultation the list maps to is resolved, along with its participant group (the user group created from the *Participant* template). If any of them cannot be resolved, the request fails before anything is changed for that list:

- unknown consultation URL → `400`, the error message lists the known URL paths of the site;
- consultation without a participant group → `500`.

Lists processed earlier in the same request stay synchronized; there is no transaction spanning the whole request.

Then the list is synchronized into each mapped consultation's participant group one after the other, in the order given in the config. Users are identified by their e-mail address (e-mail login).

| Situation | Action | Counted as |
|---|---|---|
| No account with this e-mail exists | Account is created (confirmed, random password), added to the group, and an invitation e-mail with the login data is sent | `invited` |
| Account exists, not yet in the group | Account is added to the group, a welcome e-mail is sent | `added` |
| Account exists, already in the group | Given name, family name and organization (`party`) are updated if they differ | `unchanged` |
| Account is in the group but not in the list | Removed from **this** group only. The account itself and other group memberships are kept | `removed` |

Name and organization of synchronized users are marked as fixed (`FIXED_NAME | FIXED_ORGA`), so users cannot change them themselves.

**Multiple consultations / e-mails:** when a list is mapped to several consultations, each user gets **at most one e-mail per list and request**. A new user is created and invited while the first consultation is processed. In the following consultations the same account already exists and is added without another e-mail (and is counted as `added` there). The same applies to existing users who are newly added to several of the mapped consultations at once.

Removal is decided per consultation: a user missing from the list is removed from the participant group of every consultation the list maps to.

### Response

```json
{
    "success": true,
    "Council Members": {
        "council2026":  {"invited": 1, "added": 0, "removed": 2, "unchanged": 40},
        "congress2026": {"invited": 0, "added": 1, "removed": 0, "unchanged": 41}
    }
}
```

For each list name, the counts are listed per consultation, keyed by the consultation's actual `urlPath`. This happens even if the list maps to only one consultation. If the same consultation is listed twice (e.g. with different capitalization), it is synchronized only once.
