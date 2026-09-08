# Votings on the Live-Data infrastructure

The data structures for moving the voting widgets onto the Live-Data mechanism.
Status: **implemented**. What the payloads look like is easiest read from the worked example
below, which shows one voting as all three of its consumers receive it.

Goal: replace the self-made polling of the voting widgets (`web/js/modules/frontend/VotingBlock.js`,
`web/js/modules/backend/VotingAdmin.js`, `CurrentDebateWidget.vue`) by the central Live-Data
mechanism described in `docs/technical/live-data.md`: REST for polling, RabbitMQ → Live proxy →
STOMP when a Live server is configured.

---

## 1. Worked example: one voting in three shapes

The same voting, at the same moment, as its three consumers see it: what the administration polls,
what one participant polls, and what is published to the Live proxy for both of them at once. Every
payload below is real output of the test fixture, with three shortenings: only the one item group
that was voted on is kept (the block has three), the timestamps of the three payloads are aligned to
one moment, and the three voters are named "User 1" to "User 3". The polling endpoints answer with a
*list* of such objects - one of them is shown.

The voting is closed and its results are published, because that is when a participant may see
results and single votes at all (§2): while it runs, the counting is the administration's alone. The
three of them voted in the item group `single:amendment:3` - User 1 "yes", User 2 "no", User 3
"abstention".

### 1.1 Votes visible to everybody (`votesPublic = all`)

**The administration polls** `GET /rest/<site>/<consultation>/votings/admin`:

```json
{
  "id": 1,
  "title": "Ä2 or Ä3",
  "status": "closed_published",
  "position": 0,
  "current_time": 1788137465000,
  "answers": [
    {"api_id": "yes", "title": "Ja", "result": "accepted"},
    {"api_id": "no", "title": "Nein", "result": "rejected"},
    {"api_id": "abstention", "title": "Enthaltung", "result": null}
  ],
  "has_majority": true,
  "is_presence_call": false,
  "publicity": {"single_votes": "everybody", "results": "everybody"},
  "statistics": {"votes": 3, "voters": 3},
  "item_groups": [
    {
      "id": "single:amendment:3",
      "items": [{"type": "amendment", "id": 3}],
      "name": null,
      "results": {
        "counts": [
          {
            "answers": [
              {"answer": "yes", "votes": 1},
              {"answer": "no", "votes": 1},
              {"answer": "abstention", "votes": 1}
            ],
            "organization": null
          }
        ],
        "quorum": null
      },
      "single_votes": [
        {
          "answer": "yes",
          "weight": 1,
          "voter": {"user_id": 1, "user_group_ids": [], "user_name": "User 1"}
        },
        {
          "answer": "no",
          "weight": 1,
          "voter": {"user_id": 2, "user_group_ids": [], "user_name": "User 2"}
        },
        {
          "answer": "abstention",
          "weight": 1,
          "voter": {"user_id": 3, "user_group_ids": [39], "user_name": "User 3"}
        }
      ]
    }
  ],
  "items": [
    {
      "type": "amendment",
      "id": 3,
      "group_id": "single:amendment:3",
      "title_with_prefix": "Ä2 zu A2: O’zapft is!",
      "prefix": "Ä2",
      "initiators_html": "Testadmin",
      "url_html": "http://test.antragsgruen.test/stdparteitag/std-parteitag/motion/321-o-zapft-is/amendment/3",
      "url_json": "http://test.antragsgruen.test/stdparteitag/rest/std-parteitag/motion/321-o-zapft-is/amendment/3",
      "procedure_html": "<p>Abstimmung (Abgelehnt)</p>",
      "result": "rejected"
    }
  ],
  "me": {
    "eligible": true,
    "vote_weight": 1,
    "abstained": false,
    "votes": [],
    "can_vote_group_ids": [],
    "votes_remaining": null
  },
  "settings": {
    "votes_public": 2,
    "results_public": 1,
    "votes_names": 0,
    "answers_template": 0,
    "majority_type": 1,
    "quorum_type": 0,
    "voting_time": null,
    "assigned_motion_id": null,
    "policy": {"id": 2, "description": "Eingeloggte", "user_groups": null},
    "max_votes_by_group": null
  },
  "log": [
    {"type": "opened", "date": "2026-08-31T00:51:05+00:00"},
    {"type": "closed", "date": "2026-08-31T00:51:05+00:00"}
  ],
  "editable": {"items_can_be_added": false, "items_can_be_removed": true, "settings_can_be_changed": false},
  "assigned_motion_id": null,
  "opened_at": null,
  "voting_time": null,
  "quorum": null,
  "abstention": {"enabled": false, "count": null, "users": null},
  "policy": {"id": 2, "description": "Eingeloggte", "user_groups": null},
  "user_groups": [
    {"id": 1, "title": "Seiten-Admin", "member_count": 2},
    {"id": 2, "title": "Veranstaltungs-Admin", "member_count": 1},
    {"id": 3, "title": "Antragskommission", "member_count": 1},
    {"id": 4, "title": "Teilnehmer*in", "member_count": 0},
    {"id": 39, "title": "Sachstände bearbeiten", "member_count": 1}
  ],
  "eligibility": null
}
```

**User 1 polls** `GET /rest/<site>/<consultation>/votings/open?showAllOpen=1`:

```json
{
  "id": 1,
  "title": "Ä2 or Ä3",
  "status": "closed_published",
  "position": 0,
  "current_time": 1788137465000,
  "answers": [
    {"api_id": "yes", "title": "Ja", "result": "accepted"},
    {"api_id": "no", "title": "Nein", "result": "rejected"},
    {"api_id": "abstention", "title": "Enthaltung", "result": null}
  ],
  "has_majority": true,
  "is_presence_call": false,
  "publicity": {"single_votes": "everybody", "results": "everybody"},
  "statistics": {"votes": 3, "voters": 3},
  "item_groups": [
    {
      "id": "single:amendment:3",
      "items": [{"type": "amendment", "id": 3}],
      "name": null,
      "results": {
        "counts": [
          {
            "answers": [
              {"answer": "yes", "votes": 1},
              {"answer": "no", "votes": 1},
              {"answer": "abstention", "votes": 1}
            ],
            "organization": null
          }
        ],
        "quorum": null
      },
      "single_votes": [
        {
          "answer": "yes",
          "weight": 1,
          "voter": {"user_id": 1, "user_group_ids": [], "user_name": "User 1"}
        },
        {
          "answer": "no",
          "weight": 1,
          "voter": {"user_id": 2, "user_group_ids": [], "user_name": "User 2"}
        },
        {
          "answer": "abstention",
          "weight": 1,
          "voter": {"user_id": 3, "user_group_ids": [39], "user_name": "User 3"}
        }
      ]
    }
  ],
  "items": [
    {
      "type": "amendment",
      "id": 3,
      "group_id": "single:amendment:3",
      "title_with_prefix": "Ä2 zu A2: O’zapft is!",
      "prefix": "Ä2",
      "initiators_html": "Testadmin",
      "url_html": "http://test.antragsgruen.test/stdparteitag/std-parteitag/motion/321-o-zapft-is/amendment/3",
      "url_json": "http://test.antragsgruen.test/stdparteitag/rest/std-parteitag/motion/321-o-zapft-is/amendment/3",
      "procedure_html": "<p>Abstimmung (Abgelehnt)</p>",
      "result": "rejected"
    }
  ],
  "me": {
    "eligible": true,
    "vote_weight": 1,
    "abstained": false,
    "votes": [{"group_id": "single:amendment:3", "answer": "yes"}],
    "can_vote_group_ids": [],
    "votes_remaining": null
  },
  "assigned_motion_id": null,
  "opened_at": null,
  "voting_time": null,
  "quorum": null,
  "abstention": {"enabled": false, "count": null, "users": null},
  "policy": {"id": 2, "description": "Eingeloggte", "user_groups": null},
  "user_groups": [
    {"id": 1, "title": "Seiten-Admin", "member_count": 2},
    {"id": 2, "title": "Veranstaltungs-Admin", "member_count": 1},
    {"id": 3, "title": "Antragskommission", "member_count": 1},
    {"id": 4, "title": "Teilnehmer*in", "member_count": 0},
    {"id": 39, "title": "Sachstände bearbeiten", "member_count": 1}
  ]
}
```

**Published to the Live proxy**, routing key `voting.<installation>.<site>.<consultation>`, header
`default_language: de`:

```json
{
  "kind": "full",
  "block_id": 1,
  "languages": ["de"],
  "state_version": 1788137465000,
  "current_time": 1788137465000,
  "everyone": {
    "id": 1,
    "title": "Ä2 or Ä3",
    "status": "closed_published",
    "position": 0,
    "current_time": 1788137465000,
    "answers": [
      {"api_id": "yes", "title": {"de": "Ja"}, "result": "accepted"},
      {"api_id": "no", "title": {"de": "Nein"}, "result": "rejected"},
      {"api_id": "abstention", "title": {"de": "Enthaltung"}, "result": null}
    ],
    "has_majority": true,
    "is_presence_call": false,
    "publicity": {"single_votes": "everybody", "results": "everybody"},
    "statistics": {"votes": 3, "voters": 3},
    "item_groups": [
      {
        "id": "single:amendment:3",
        "items": [{"type": "amendment", "id": 3}],
        "name": null,
        "results": {
          "counts": [
            {
              "answers": [
                {"answer": "yes", "votes": 1},
                {"answer": "no", "votes": 1},
                {"answer": "abstention", "votes": 1}
              ],
              "organization": null
            }
          ],
          "quorum": null
        },
        "single_votes": [
          {
            "answer": "yes",
            "weight": 1,
            "voter": {"user_id": 1, "user_group_ids": [], "user_name": "User 1"}
          },
          {
            "answer": "no",
            "weight": 1,
            "voter": {"user_id": 2, "user_group_ids": [], "user_name": "User 2"}
          },
          {
            "answer": "abstention",
            "weight": 1,
            "voter": {"user_id": 3, "user_group_ids": [39], "user_name": "User 3"}
          }
        ]
      }
    ],
    "items": [
      {
        "type": "amendment",
        "id": 3,
        "group_id": "single:amendment:3",
        "title_with_prefix": {"de": "Ä2 zu A2: O’zapft is!"},
        "prefix": "Ä2",
        "initiators_html": {"de": "Testadmin"},
        "url_html": "http://test.antragsgruen.test/stdparteitag/std-parteitag/motion/321-o-zapft-is/amendment/3",
        "url_json": "http://test.antragsgruen.test/stdparteitag/rest/std-parteitag/motion/321-o-zapft-is/amendment/3",
        "procedure_html": {"de": "<p>Abstimmung (Abgelehnt)</p>"},
        "result": "rejected"
      }
    ],
    "assigned_motion_id": null,
    "opened_at": null,
    "voting_time": null,
    "quorum": null,
    "abstention": {"enabled": false, "count": null, "users": null},
    "policy": {"id": 2, "description": {"de": "Eingeloggte"}, "user_groups": null},
    "user_groups": [
      {"id": 1, "title": {"de": "Seiten-Admin"}, "member_count": 2},
      {"id": 2, "title": {"de": "Veranstaltungs-Admin"}, "member_count": 1},
      {"id": 3, "title": {"de": "Antragskommission"}, "member_count": 1},
      {"id": 4, "title": {"de": "Teilnehmer*in"}, "member_count": 0},
      {"id": 39, "title": {"de": "Sachstände bearbeiten"}, "member_count": 1}
    ]
  },
  "admin_only": {
    "settings": {
      "votes_public": 2,
      "results_public": 1,
      "votes_names": 0,
      "answers_template": 0,
      "majority_type": 1,
      "quorum_type": 0,
      "voting_time": null,
      "assigned_motion_id": null,
      "policy": {"id": 2, "description": {"de": "Eingeloggte"}, "user_groups": null},
      "max_votes_by_group": null
    },
    "log": [
      {"type": "opened", "date": "2026-08-31T00:51:05+00:00"},
      {"type": "closed", "date": "2026-08-31T00:51:05+00:00"}
    ],
    "editable": {"items_can_be_added": false, "items_can_be_removed": true, "settings_can_be_changed": false},
    "eligibility": null
  },
  "default_user_state": {
    "eligible": true,
    "vote_weight": 1,
    "abstained": false,
    "votes": [],
    "can_vote_group_ids": [],
    "votes_remaining": null
  },
  "per_user": {
    "login-1": {
      "eligible": true,
      "vote_weight": 1,
      "abstained": false,
      "votes": [{"group_id": "single:amendment:3", "answer": "yes"}],
      "can_vote_group_ids": [],
      "votes_remaining": null
    },
    "login-2": {
      "eligible": true,
      "vote_weight": 1,
      "abstained": false,
      "votes": [{"group_id": "single:amendment:3", "answer": "no"}],
      "can_vote_group_ids": [],
      "votes_remaining": null
    },
    "login-3": {
      "eligible": true,
      "vote_weight": 1,
      "abstained": false,
      "votes": [{"group_id": "single:amendment:3", "answer": "abstention"}],
      "can_vote_group_ids": [],
      "votes_remaining": null
    }
  }
}
```

Worth comparing:

* `everyone` is User 1's payload without `me` - field by field the same, the wording of the strings
  aside - and `admin_only` holds exactly what the administration has on top of it: `settings`, `log`,
  `editable`, `eligibility`. The counting and the single votes are *not* there: both audiences see
  the same ones, so they are sent once.
* `me` is not in either section. Each person's own state is in `per_user` under their JWT subject,
  and the proxy hands each subscriber their own entry; whoever has no entry - anybody who did not
  vote - is described by `default_user_state`.
* The strings that depend on the reader are objects keyed by language (`{"de": "Ja"}`), and
  `languages` names the set. The polled payloads have them resolved to the reader's language
  already (see `docs/technical/live-data.md`).

### 1.2 Votes visible to the administration only (`votesPublic = admins`)

Same voting, same three votes, `votesPublic` set to "admins" before it was opened.

**The administration polls** `GET /rest/<site>/<consultation>/votings/admin` - identical to 11.1
apart from `publicity` and `settings.votes_public`, the administration seeing the votes either way:

```json
{
  "id": 1,
  "title": "Ä2 or Ä3",
  "status": "closed_published",
  "position": 0,
  "current_time": 1788137465000,
  "answers": [
    {"api_id": "yes", "title": "Ja", "result": "accepted"},
    {"api_id": "no", "title": "Nein", "result": "rejected"},
    {"api_id": "abstention", "title": "Enthaltung", "result": null}
  ],
  "has_majority": true,
  "is_presence_call": false,
  "publicity": {"single_votes": "admins", "results": "everybody"},
  "statistics": {"votes": 3, "voters": 3},
  "item_groups": [
    {
      "id": "single:amendment:3",
      "items": [{"type": "amendment", "id": 3}],
      "name": null,
      "results": {
        "counts": [
          {
            "answers": [
              {"answer": "yes", "votes": 1},
              {"answer": "no", "votes": 1},
              {"answer": "abstention", "votes": 1}
            ],
            "organization": null
          }
        ],
        "quorum": null
      },
      "single_votes": [
        {
          "answer": "yes",
          "weight": 1,
          "voter": {"user_id": 1, "user_group_ids": [], "user_name": "User 1"}
        },
        {
          "answer": "no",
          "weight": 1,
          "voter": {"user_id": 2, "user_group_ids": [], "user_name": "User 2"}
        },
        {
          "answer": "abstention",
          "weight": 1,
          "voter": {"user_id": 3, "user_group_ids": [39], "user_name": "User 3"}
        }
      ]
    }
  ],
  "items": [
    {
      "type": "amendment",
      "id": 3,
      "group_id": "single:amendment:3",
      "title_with_prefix": "Ä2 zu A2: O’zapft is!",
      "prefix": "Ä2",
      "initiators_html": "Testadmin",
      "url_html": "http://test.antragsgruen.test/stdparteitag/std-parteitag/motion/321-o-zapft-is/amendment/3",
      "url_json": "http://test.antragsgruen.test/stdparteitag/rest/std-parteitag/motion/321-o-zapft-is/amendment/3",
      "procedure_html": "<p>Abstimmung (Abgelehnt)</p>",
      "result": "rejected"
    }
  ],
  "me": {
    "eligible": true,
    "vote_weight": 1,
    "abstained": false,
    "votes": [],
    "can_vote_group_ids": [],
    "votes_remaining": null
  },
  "settings": {
    "votes_public": 1,
    "results_public": 1,
    "votes_names": 0,
    "answers_template": 0,
    "majority_type": 1,
    "quorum_type": 0,
    "voting_time": null,
    "assigned_motion_id": null,
    "policy": {"id": 2, "description": "Eingeloggte", "user_groups": null},
    "max_votes_by_group": null
  },
  "log": [
    {"type": "opened", "date": "2026-08-31T00:51:05+00:00"},
    {"type": "closed", "date": "2026-08-31T00:51:05+00:00"}
  ],
  "editable": {"items_can_be_added": false, "items_can_be_removed": true, "settings_can_be_changed": false},
  "assigned_motion_id": null,
  "opened_at": null,
  "voting_time": null,
  "quorum": null,
  "abstention": {"enabled": false, "count": null, "users": null},
  "policy": {"id": 2, "description": "Eingeloggte", "user_groups": null},
  "user_groups": [
    {"id": 1, "title": "Seiten-Admin", "member_count": 2},
    {"id": 2, "title": "Veranstaltungs-Admin", "member_count": 1},
    {"id": 3, "title": "Antragskommission", "member_count": 1},
    {"id": 4, "title": "Teilnehmer*in", "member_count": 0},
    {"id": 39, "title": "Sachstände bearbeiten", "member_count": 1}
  ],
  "eligibility": null
}
```

**User 1 polls** `GET /rest/<site>/<consultation>/votings/open?showAllOpen=1`:

```json
{
  "id": 1,
  "title": "Ä2 or Ä3",
  "status": "closed_published",
  "position": 0,
  "current_time": 1788137465000,
  "answers": [
    {"api_id": "yes", "title": "Ja", "result": "accepted"},
    {"api_id": "no", "title": "Nein", "result": "rejected"},
    {"api_id": "abstention", "title": "Enthaltung", "result": null}
  ],
  "has_majority": true,
  "is_presence_call": false,
  "publicity": {"single_votes": "admins", "results": "everybody"},
  "statistics": {"votes": 3, "voters": 3},
  "item_groups": [
    {
      "id": "single:amendment:3",
      "items": [{"type": "amendment", "id": 3}],
      "name": null,
      "results": {
        "counts": [
          {
            "answers": [
              {"answer": "yes", "votes": 1},
              {"answer": "no", "votes": 1},
              {"answer": "abstention", "votes": 1}
            ],
            "organization": null
          }
        ],
        "quorum": null
      },
      "single_votes": null
    }
  ],
  "items": [
    {
      "type": "amendment",
      "id": 3,
      "group_id": "single:amendment:3",
      "title_with_prefix": "Ä2 zu A2: O’zapft is!",
      "prefix": "Ä2",
      "initiators_html": "Testadmin",
      "url_html": "http://test.antragsgruen.test/stdparteitag/std-parteitag/motion/321-o-zapft-is/amendment/3",
      "url_json": "http://test.antragsgruen.test/stdparteitag/rest/std-parteitag/motion/321-o-zapft-is/amendment/3",
      "procedure_html": "<p>Abstimmung (Abgelehnt)</p>",
      "result": "rejected"
    }
  ],
  "me": {
    "eligible": true,
    "vote_weight": 1,
    "abstained": false,
    "votes": [{"group_id": "single:amendment:3", "answer": "yes"}],
    "can_vote_group_ids": [],
    "votes_remaining": null
  },
  "assigned_motion_id": null,
  "opened_at": null,
  "voting_time": null,
  "quorum": null,
  "abstention": {"enabled": false, "count": null, "users": null},
  "policy": {"id": 2, "description": "Eingeloggte", "user_groups": null},
  "user_groups": [
    {"id": 1, "title": "Seiten-Admin", "member_count": 2},
    {"id": 2, "title": "Veranstaltungs-Admin", "member_count": 1},
    {"id": 3, "title": "Antragskommission", "member_count": 1},
    {"id": 4, "title": "Teilnehmer*in", "member_count": 0},
    {"id": 39, "title": "Sachstände bearbeiten", "member_count": 1}
  ]
}
```

**Published to the Live proxy**:

```json
{
  "kind": "full",
  "block_id": 1,
  "languages": ["de"],
  "state_version": 1788137465000,
  "current_time": 1788137465000,
  "everyone": {
    "id": 1,
    "title": "Ä2 or Ä3",
    "status": "closed_published",
    "position": 0,
    "current_time": 1788137465000,
    "answers": [
      {"api_id": "yes", "title": {"de": "Ja"}, "result": "accepted"},
      {"api_id": "no", "title": {"de": "Nein"}, "result": "rejected"},
      {"api_id": "abstention", "title": {"de": "Enthaltung"}, "result": null}
    ],
    "has_majority": true,
    "is_presence_call": false,
    "publicity": {"single_votes": "admins", "results": "everybody"},
    "statistics": {"votes": 3, "voters": 3},
    "item_groups": [
      {
        "id": "single:amendment:3",
        "items": [{"type": "amendment", "id": 3}],
        "name": null,
        "results": {
          "counts": [
            {
              "answers": [
                {"answer": "yes", "votes": 1},
                {"answer": "no", "votes": 1},
                {"answer": "abstention", "votes": 1}
              ],
              "organization": null
            }
          ],
          "quorum": null
        },
        "single_votes": null
      }
    ],
    "items": [
      {
        "type": "amendment",
        "id": 3,
        "group_id": "single:amendment:3",
        "title_with_prefix": {"de": "Ä2 zu A2: O’zapft is!"},
        "prefix": "Ä2",
        "initiators_html": {"de": "Testadmin"},
        "url_html": "http://test.antragsgruen.test/stdparteitag/std-parteitag/motion/321-o-zapft-is/amendment/3",
        "url_json": "http://test.antragsgruen.test/stdparteitag/rest/std-parteitag/motion/321-o-zapft-is/amendment/3",
        "procedure_html": {"de": "<p>Abstimmung (Abgelehnt)</p>"},
        "result": "rejected"
      }
    ],
    "assigned_motion_id": null,
    "opened_at": null,
    "voting_time": null,
    "quorum": null,
    "abstention": {"enabled": false, "count": null, "users": null},
    "policy": {"id": 2, "description": {"de": "Eingeloggte"}, "user_groups": null},
    "user_groups": [
      {"id": 1, "title": {"de": "Seiten-Admin"}, "member_count": 2},
      {"id": 2, "title": {"de": "Veranstaltungs-Admin"}, "member_count": 1},
      {"id": 3, "title": {"de": "Antragskommission"}, "member_count": 1},
      {"id": 4, "title": {"de": "Teilnehmer*in"}, "member_count": 0},
      {"id": 39, "title": {"de": "Sachstände bearbeiten"}, "member_count": 1}
    ]
  },
  "admin_only": {
    "item_groups": [
      {
        "id": "single:amendment:3",
        "items": [{"type": "amendment", "id": 3}],
        "name": null,
        "results": {
          "counts": [
            {
              "answers": [
                {"answer": "yes", "votes": 1},
                {"answer": "no", "votes": 1},
                {"answer": "abstention", "votes": 1}
              ],
              "organization": null
            }
          ],
          "quorum": null
        },
        "single_votes": [
          {
            "answer": "yes",
            "weight": 1,
            "voter": {"user_id": 1, "user_group_ids": [], "user_name": "User 1"}
          },
          {
            "answer": "no",
            "weight": 1,
            "voter": {"user_id": 2, "user_group_ids": [], "user_name": "User 2"}
          },
          {
            "answer": "abstention",
            "weight": 1,
            "voter": {"user_id": 3, "user_group_ids": [39], "user_name": "User 3"}
          }
        ]
      }
    ],
    "settings": {
      "votes_public": 1,
      "results_public": 1,
      "votes_names": 0,
      "answers_template": 0,
      "majority_type": 1,
      "quorum_type": 0,
      "voting_time": null,
      "assigned_motion_id": null,
      "policy": {"id": 2, "description": {"de": "Eingeloggte"}, "user_groups": null},
      "max_votes_by_group": null
    },
    "log": [
      {"type": "opened", "date": "2026-08-31T00:51:05+00:00"},
      {"type": "closed", "date": "2026-08-31T00:51:05+00:00"}
    ],
    "editable": {"items_can_be_added": false, "items_can_be_removed": true, "settings_can_be_changed": false},
    "eligibility": null
  },
  "default_user_state": {
    "eligible": true,
    "vote_weight": 1,
    "abstained": false,
    "votes": [],
    "can_vote_group_ids": [],
    "votes_remaining": null
  },
  "per_user": {
    "login-1": {
      "eligible": true,
      "vote_weight": 1,
      "abstained": false,
      "votes": [{"group_id": "single:amendment:3", "answer": "yes"}],
      "can_vote_group_ids": [],
      "votes_remaining": null
    },
    "login-2": {
      "eligible": true,
      "vote_weight": 1,
      "abstained": false,
      "votes": [{"group_id": "single:amendment:3", "answer": "no"}],
      "can_vote_group_ids": [],
      "votes_remaining": null
    },
    "login-3": {
      "eligible": true,
      "vote_weight": 1,
      "abstained": false,
      "votes": [{"group_id": "single:amendment:3", "answer": "abstention"}],
      "can_vote_group_ids": [],
      "votes_remaining": null
    }
  }
}
```

What changed:

* `single_votes` is `null` for User 1 - not an empty list, which would say "nobody voted" (§4.2).
  Their own vote is still in `me`: how public a vote is has nothing to do with whether the person who
  cast it may see it.
* `item_groups` has moved into `admin_only`, because the administration's copy of it now differs from
  everyone else's. That is the whole mechanism: the scope of a field follows from the two payloads
  differing, not from a list of field names (§3) - and the proxy, which knows nothing about
  `votesPublic`, delivers the votes to exactly those subscribers that hold `ROLE_VOTING_ADMIN`.
* Nothing else moved: the counting stayed in `everyone`, since `resultsPublic` still lets everybody
  see it.
* A voting whose votes nobody may see (`votesPublic = nobody`) has no third shape here: those votes
  are in no section at all, not even the administration's.
## 2. Visibility model

The message posted to the proxy is an envelope with three explicitly scoped sections:

| Section | Delivered to | Rule for the backend |
|---|---|---|
| `everyone` | every subscriber of the consultation | may only contain data every participant may see - and only about a voting they could ask for, see §2.1 |
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

  That claim is about `single_votes`, and it does not extend to `per_user` (§3). A `full` message
  carries everyone's own state keyed by their JWT subject — which is `login-<user id>` — including
  the answers they gave, because that is how each reader is told what they themselves voted. So the
  message that closes a secret voting is a complete record of who voted how, and it sits in RabbitMQ
  and in the Live server's memory, where under the old polling design each vote only ever travelled
  to the person who cast it. Nothing is exposed to a *reader*: the Live server delivers each subject
  only their own entry, and the scope rules of §3 are what enforce that. But for a secret voting the
  broker and the Live server are inside the trust boundary, and an operator deciding where to run
  them, and what to do about their logs and their persistence, should know it.
* `resultsPublic = RESULTS_PUBLIC_NO` → the per-item result counts go to `admin_only` only, and so
  does `abstention.count`: how many people abstained is a result like any other.
* **Turnout is not a result**: `statistics.votes` / `statistics.voters` stay in `everyone`
  regardless of `resultsPublic`. They say how many have voted, never how anyone voted - and the
  widgets show "143 of 200 have voted" while a secret vote is running.

### 2.1 Which votings the `everyone` section may describe at all

The scopes above decide which *fields* a reader is given. What state a voting is in decides whether
it may be described to a participant **at all**, because an event is published on every
administrative change, in every state, while the only votings a participant can ask for are the ones
that are open (`/votings/open`) and the ones whose results are published (the results page).

A voting that is offline, being prepared, or closed without its results being published is therefore
reduced to `{id, status, current_time}` in `everyone` - enough for a reader to keep it out of their
list, and nothing else. Without that, preparing a voting would announce its title, its items and
their initiators - motions that are only visible to the administration among them - to everyone in
the consultation. The administration is unaffected: everything the reduced section leaves out lands
in `admin_only` by the rule of §3, so `everyone ∪ admin_only` is still the whole payload.

Leaving the reader uninformed instead is not an option: a client stops polling while the Live server
is connected (`LiveData.shouldPoll()`), so the event is the only thing that can still tell it a
voting has left its list. For the same reason, deleting a voting - the one change that has no new
state to describe - publishes `{id, removed: true, current_time}`, which the collection channel
drops the member on.

`Vote.public` — the publicity a vote was cast under — is honoured **per vote**: each single vote is
placed into `everyone` or `admin_only` or dropped according to its own `public` value, not according
to the block's current setting. This generalizes today's `getFilteredVotesList()` safeguard into the
data structure itself. What that value is no longer depends on anything the client sends: it is
`min(publicity when the voting was opened, publicity now)`, computed server-side.

## 3. Message posted to the Live proxy

Routing key `voting.<installation>.<subdomain>.<consultationPath>` — one message per **voting block**
(as for speech queues), not per consultation, so that a client can merge by id.

```jsonc
{
  "kind": "full",                  // "full" | "tally", see §6
  "block_id": 42,
  "languages": ["de", "en"],       // the languages the strings within the sections were rendered in
  "state_version": 1724740000123,  // the publish time in ms: monotonic enough to order two events
  "current_time": 1724740000123,   // ms, for clock-skew correction of the countdown

  "everyone": { /* the fields of VotingBlockUser except "me", §4 */ },

  "admin_only": { /* the fields an administrator sees differently or in addition, §5 — null if none */ },

  "default_user_state": { /* VotingUserState, §4.3 */ },   // only in "full" messages
  "per_user": {                                            // only in "full" messages
    "login-42": { /* VotingUserState, §4.3 */ },
    "login-77": { /* … */ }
  }
}
```

`admin_only` is derived rather than listed: whatever the administration's payload says differently
from the participant's, or says at all, goes there. A field added to the admin payload therefore
lands in the right scope by itself, and `everyone ∪ admin_only` is by construction exactly what an
administrator would have been served over REST.

`languages` is what lets the Live server resolve the localized strings *without* knowing the payload:
the sections are opaque to it, so a localized string is recognized by its shape - an object whose
members are all strings named after one of these languages. Stating the set in the message is what
keeps that from being a guess; no other object of a voting payload is keyed by language.

`per_user` holds an entry for everyone this voting knows something about: everyone who has voted,
and - where the policy can name the people it admits (`IPolicy::getAdmittedUserIds()`: the members of
the selected user groups, or the holders of the privilege for the "administrators" policy) - everyone
entitled to. Anybody else is described by `default_user_state`, whose `eligible` follows from whether
the policy can name them at all: one that admits whoever is logged in cannot, so a reader is assumed
to be among them; one that can name them has already named them, so a reader who is not on the list
is not among them.
For a 2000-delegate consultation the map is roughly 100 KB - acceptable because `full` messages are
triggered by admin actions (open / close / reset / settings / items), not by cast votes (§6).

Every entry carries the answers that person gave, which is what makes it their own state, and the
key is their user ID. So `per_user` is the one thing in a payload that names votes irrespective of
`votesPublic`; what §2 says about a secret voting scopes to `single_votes`, and the note there says
what that means for where the broker and the Live server may run.

## 4. What clients receive: `VotingBlockUser`

This is *both* the body of the STOMP message on `user/voting` *and* the response of the polling
endpoint — byte-identical, as with `SpeechQueueUser` today.

### 4.1 Block level

```jsonc
{
  "id": 42,
  "title": "Amendments to A1",
  "status": "open",                       // offline|preparing|open|closed_published|closed_unpublished
  "position": 7,                          // sort order: descending, ties broken by descending id
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

  "statistics": {"votes": 143, "voters": 143},   // turnout, always visible

  "abstention": {"enabled": true, "count": 4},   // "count" only where results are visible, "users" only in the admin DTO

  "item_groups": [ /* §4.2 */ ],
  "items":       [ /* §4.2 */ ],

  "me": { /* §4.3 */ }
}
```

### 4.2 Items and item groups

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

    "single_votes": [                     // null unless this audience may see single votes,
      {                                   // and absent entirely from a tally, see §6
        "answer": "yes",
        "weight": 1,
        "voter": {"user_id": 12, "user_group_ids": [3], "user_name": "…"}
      }
    ]
  }
]
```

*A section the reader may not see is `null`, never an empty list* — so a client can never render
"0 votes" where it should render nothing, and a bug shows up as a missing section rather than as a
wrong result. (The DTOs make this an explicit `null` in the JSON rather than an absent key, which
says the same thing and is what a typed payload can express.)

`single_votes` and `abstention.users` are the two fields where an **absent** key means something
different from a `null` one: a tally leaves them out to say "unchanged, keep what you have", while
`null` says "you may not see these". They are also the only two that grow with the number of people
voting, which is why they are the only two a tally omits — see §6. Nothing else in the payload
distinguishes the two, and nothing else needs to.

### 4.3 `me` — `VotingUserState`

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
server-side (§2). `publicity.single_votes` in the block is what the widget tells the voter before
they cast, nothing more.

## 5. `VotingBlockAdmin` — `everyone` ∪ `admin_only`

```jsonc
{
  /* …all of §4, plus: */

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

## 6. Message frequency: `full` vs. `tally`

Publishing a `full` message on every cast vote would mean, for a 2000-delegate vote, 2000 messages
of ~100 KB each, fanned out personally to 2000 connections. So:

* **`full`** — complete block state incl. `per_user`. Triggered by admin actions: open, close,
  reopen, reset, settings change, item add/remove, sort (for the votings that moved), and changed
  group membership (for the votings that are *running* - one that is not open yet publishes its whole
  state when it is opened anyway, and a closed one keeps the list it was closed with; a full event is
  the most expensive one there is, and a routine group edit must not pay for it once per voting a
  consultation has ever prepared). A handful per voting. Deleting one sends the removal event of §2.1
  instead, there being no state left to describe.
* Because the order is only ever conveyed by a poll otherwise, `position` is part of the payload:
  a client that has stopped polling sorts the list it has assembled from single events itself.
* **`tally`** — no `per_user`, no `settings`, no `eligibility`, **and nobody named**. Carries only
  `statistics`, `item_groups[].results` and `abstention.count` (each in whichever scope the publicity
  allows). Its size does not depend on how many people have voted. Published on every cast vote;
  throttling, if it is ever needed, happens in the Live server (see below).

Two lists are left out, because they are the two parts of a voting that grow with the number of
people in the room: `item_groups[].single_votes`, and `abstention.users` — who abstained from the
voting as a whole, which is recorded against a question of its own and therefore sits outside the
item groups. Carrying either would make the n-th vote publish all n votes cast so far, so a
1000-delegate voting would push ~55 MB through the broker over its run instead of ~1 MB, and the
last vote alone would build a 109 KB message. Nobody is deprived of anything by that while a voting
is running — `canSeeAnySingleVote()` shows them to the administration and to nobody else until the
voting is decided — and the administration's channel keeps polling for them (§7). Everyone else, if
`votesPublic` lets them see the votes at all, learns the whole list at once: from the `full` message
that closing the voting produces.

Leaving them out is also what keeps a tally cheap to *build*: asking a `VotingBlock` for the votes of
any one of its items hydrates every vote row of the whole voting, so a tally asks for none of them.
How many abstained is read off the turnout, which counts it anyway.

Merge contract on the client: a `tally` message touches exactly those three fields and leaves
everything else — importantly `me` — untouched. `me` for the acting user is updated from the
response of their own vote POST; no other user's `me` changes when someone votes. The Live server
marks such a message `"partial": true`, and `LiveData.js` merges a partial member field by field
instead of replacing it (a partial event about a voting the client has never seen is dropped: it says
too little to show, and the next poll brings the whole of it). A message that is *not* partial
replaces the member instead, which is what makes §2.1 mean anything on the client: a voting reduced
to its id and its status has to arrive as a member reduced to its id and its status, not as one
field-merged over everything the reader was told while it was open.

The rule is that a key a partial message does not carry is left as it was, and it holds below the top
level as well: within a member, a list of objects whose `id` identifies them is merged entry by entry,
and a nested object member by member, rather than being replaced with something that describes the
same thing in less detail. `id` has to *identify* them: `items[]` is not merged that way, a motion and
an amendment in one voting being able to share the numeric id 3 - and it needs no merging, every
message that carries it describing each of its entries in full. That is what lets a tally name every item group — it has to, for the counting —
without discarding the single votes inside them, and lets it carry `abstention.count` without
discarding `abstention.users`. This is why a tally omits those two keys instead of setting them to
`null`: an absent key keeps what the reader has, a `null` one overwrites it, and a full message needs
to be able to say "you may not see these" (§4.2).

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
* **Building the tally used to be the expensive half, and it used to grow.** It no longer does: the
  counting comes from aggregate SQL (stage 1 below) and the single votes are not part of a tally at
  all, so the message is a constant ~1 KB and its cost no longer depends on how many people have
  voted. Measured on a 1000-vote block, building and publishing one tally went from ~125 ms and 1033
  queries to ~35 ms and 23 queries, none of which scale with the number of voters.

Two things put that in perspective. The vote POST already pays this cost today, for the response it
returns to the voter; publishing doubles it rather than introducing it. And it replaces polling:
2000 delegates polling every 3 s are **~670 requests per second**, each one doing the same work for
the whole open-voting list. Publishing at 17/s is roughly 2.5% of the load the live path removes.

What to do about it, in the order the effort pays off:

1. ~~Compute statistics and results with aggregate SQL instead of hydrating every `Vote`.~~ Done in
   stage 1: `Vote::calculateVoteResultsForApi()` sums the weights per answer in one `GROUP BY` query
   per item group, `getVoteStatistics()` reads four columns instead of building objects, and the
   personal state of a reader comes from a query for their own votes. A payload nobody may see the
   single votes in now loads no votes at all. The plugin hook follows: it is handed the item rather
   than the votes, so that a plugin counting differently can query what it needs itself.
2. ~~Keep the single votes out of the tally, so that the message stops growing with the turnout.~~
   Done: see the `tally` bullet above.
3. ~~Fetch the people behind the votes in one query rather than one per voter.~~ Done:
   `User::preloadCachedUsers()`, called from `VotingPayloadBuilder::getSingleVotes()`, from
   `buildPerUserStates()` and from `ConsultationUserGroup::getUsersCached()`, so a payload that
   describes every voter - the `full` message closing a voting produces - costs one query for all of
   them instead of one each. `buildPerUserStates()` needs its own call: it is the longest of these
   lists (everyone who has voted, *plus* everyone a policy that names its voters admits), and it is
   reached even when nothing else on the path loads a person - a secret voting names nobody, so
   `getSingleVotes()` never runs, and the group preload only caches memberships.
4. Publish after `ResourceLock::releaseAllLocks()`, and ideally after the response has been flushed,
   so that neither the lock nor the voter waits for RabbitMQ.
5. Only if the rate ever justifies it: replace the management API with a real AMQP client and a
   persistent connection. At 17/s it does not.

One robustness note, which applies to the existing speech events too: `sendToRabbitMq()` throws when
the publish fails *or* when RabbitMQ reports the message as unrouted (no queue bound). Inside a vote
POST that would turn a broker hiccup, or a Live server that has never declared its queues, into a
failed vote. A missed tally is cosmetic; the vote is not. Publishing must not be able to fail the
request that triggered it.

## 7. Channels and endpoints

| Channel | Poll URL | Auth | Default interval | Polls while live |
|---|---|---|---|---|
| `user/voting` | `GET /rest/<site>/<consultation>/votings/open?showAllOpen=1` | JWT | 3000 ms | no |
| `admin/voting` | `GET /rest/<site>/<consultation>/votings/admin` | JWT | 2000 ms | **yes** |

Polling normally stops for as long as the Live server is connected. The administration's voting
channel is the exception (`poll_while_live` in `LiveDataChannels`): its live events deliberately
describe less than a poll does, leaving out the single votes (§6), so the poll is what keeps that
list moving while a voting runs. It costs nothing worth counting - the people who may see single
votes during a voting are the handful holding `PRIVILEGE_VOTINGS`, not the room.

Both are **collection channels**: the poll response is a list of `VotingBlock*` objects, a live
message carries a single one, and the client merges it into its collection by `id`. This is the
keyed-channel idea of `LiveData.js` turned around — the client does not know the ids in advance
(a voting that is opened has to appear), so the key comes out of the message instead of out of the
widget.

Filtering that the proxy cannot do (`assigned_motion_id`, "only open ones") happens client-side;
the poll URL takes the same filter as a query parameter so that the initial fetch stays small.
`/voting-results` (closed & published) keeps its own filter and needs no live channel — it changes
once, at closing time, which produces a `full` message the collection channel picks up anyway.

Subscribing to the administration's channel takes `ROLE_VOTING_ADMIN`, which
`JwtCreator::getJwtConfigForCurrUser()` puts into the token of everyone holding `PRIVILEGE_VOTINGS`
and the proxy's `TopicPermissionChecker` requires for an `/admin/…/voting/…` destination.

