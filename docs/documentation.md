# IntraBox — Documentation

> **Web Programming exam project**
> Sofia University "St. Kliment Ohridski" · Faculty of Mathematics and Informatics · summer semester 2026
> Topic #24 — Anonymous internal "mail" for communication
>
> Members:
> 1. *Yoan Baychev* (faculty no. 0MI0600328)
> 2. *Ivailo Kunchev* (faculty no. 2MI0600305)
>
> Signatures: ____________ · ____________

---

## 1. Introduction and goals

IntraBox is an internal mail-like system for an organization. It lets
employees write to each other **under aliases** (pseudo-anonymous —
real names are only visible to admins), create groups, and send each
other reviews, all under access rules and time windows defined by the
administrator.

**Goals:**

- Build a web application that covers all requirements of topic #24
  from the course exam topics.
- Demonstrate knowledge of every layer of the web stack: HTML/CSS/JS,
  HTTP, server-side PHP, a relational database, containerization.
- Cover the basic security best practices (PDO, CSRF, XSS, hashing).
- Implement an abuse-detection algorithm based on regular
  expressions — one of the topic's key requirements.

## 2. Requirements analysis

| ID | Requirement (from the assignment)                              | Implementation                                                                |
|----|----------------------------------------------------------------|-------------------------------------------------------------------------------|
| F1 | Send messages with "reviews"                                   | `is_review` flag in `messages` + UI checkbox in the Compose form              |
| F2 | Define visible "rules" and time windows                        | `rules` table + `RuleEngine::canSend()` + public view `/rules`                |
| F3 | Message grouping (threading)                                   | `parent_id` self-FK + thread view (`Message::thread()`)                       |
| F4 | Sending to a group                                             | `recipient_group` column + `groups` + `group_members` + fan-out               |
| F5 | Statistics — anonymous and non-anonymous                       | `StatsService::anonymous()` and `::nonAnonymous()` + admin dashboard          |
| F6 | Abuse-detection algorithm (regex)                              | `AbuseDetector::scan()` with 7 patterns + log in `abuse_log`                  |

**Non-functional requirements (from the lecturer's post):**

| ID  | Requirement                                              | Implementation                                   |
|-----|----------------------------------------------------------|--------------------------------------------------|
| N1  | Upload to gitlab.hss.fmi.uni-sofia.bg                    | Standard git repo structure                      |
| N2  | Dockerfile + .env configuration                          | docker-compose.yml + .env / .env.example         |
| N3  | Security (student level)                                 | PDO, CSRF, Argon2id, htmlspecialchars            |
| N4  | Printed documentation                                    | This file (export to .docx before printing)      |

## 3. Architecture

### 3.1 High level

```
┌─────────────┐    HTTP    ┌──────────────┐    PDO    ┌──────────────┐
│  Browser    │ ─────────► │ nginx :80    │           │              │
│  HTML/JS    │            │     ↓        │           │              │
│             │ ◄───────── │  PHP-FPM     │ ────────► │  PostgreSQL  │
└─────────────┘            └──────────────┘           │     16       │
                                                      └──────────────┘
        ▲                          ▲
        │                          │
        └──── /assets/css/js ──────┘
```

Three Docker containers (`web`, `php`, `db`) on a single network. The
web root is `public/` — everything else (including the PHP sources in
`src/`) is outside the web server's reach.

### 3.2 Application layers

```
   ┌────────────────────────────────────────────────┐
   │  Views (PHP templates, htmlspecialchars escape) │
   ├────────────────────────────────────────────────┤
   │  Controllers (input / validation / flow)       │
   ├────────────────────────────────────────────────┤
   │  Services  (AbuseDetector, RuleEngine, Stats)  │
   ├────────────────────────────────────────────────┤
   │  Models    (PDO repositories, prepared stmts)  │
   ├────────────────────────────────────────────────┤
   │  Core      (Database, Router, Session, Csrf)   │
   └────────────────────────────────────────────────┘
```

### 3.3 Routing (front controller)

`public/index.php` calls into `App\Core\Router`. nginx forwards every
non-static request to `index.php?$query_string`. Routes use `{id}`
placeholders — for example `GET /messages/{id}` →
`InboxController::read`.

## 4. Data model

### 4.1 ER diagram (text version)

```
users ─────< group_members >───── groups
   │              │                  │
   │              │                  ├──< rules
   │              │                  │
   │              │                  │
   ├──< messages.sender_id            │
   │              │                  │
   ├──< messages.recipient_id          │
   │              └──── messages.recipient_group ─────┘
   │
   ├──< message_reads
   │
   └──< abuse_log.sender_id
```

### 4.2 Tables

- **users** — login, real name, alias, email, password hash (Argon2id), role (`user`/`admin`), active flag.
- **groups** + **group_members** — many-to-many between users and groups.
- **rules** — who can write to whom, on which weekday/hour; `is_allow` (allow/deny), `is_visible` (whether shown to the user).
- **messages** — `recipient_id` OR `recipient_group`; `parent_id` for threading; `is_review` flag.
- **message_reads** — when a given user has read a given message (for "unread" inbox + average time-to-read).
- **abuse_log** — what was detected, severity, whether reviewed by admin.

Full schema: `docker/postgres/init.sql`.

## 5. Implementation of the key algorithms

### 5.1 AbuseDetector (`src/Services/AbuseDetector.php`)

A list of regular expressions applied to "subject + body" before the
message is persisted. Every match generates an `abuse_log` row (with
`message_id` if the message was persisted, or `NULL` if it was
blocked).

| Pattern              | Regex                                                       | Severity |
|----------------------|-------------------------------------------------------------|----------|
| email                | `/[\w.+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}/u`                  | 3        |
| phone_bg             | `/(?:\+?359|0)\s*(?:\d\s*){8,9}/u`                          | 3        |
| ssn_egn              | `/\b\d{10}\b/u`                                             | 3        |
| url                  | `/\bhttps?:\/\/\S+/iu`                                      | 1        |
| name_self_disclosure | `/\b(?:казвам\s+се|аз\s+съм|my\s+name\s+is|i\s+am)\s+\S+/iu`| 2        |
| whitespace_obfuscation| `/(?:\b\p{L}\b\s+){3,}\b\p{L}\b/u`                         | 2        |
| profanity_lite       | `/\b(idiot|moron|тъпак|идиот)\b/iu`                         | 1        |

(The Bulgarian phrases in `name_self_disclosure` and the Bulgarian
profanity entries are intentional: the project targets a Bulgarian
audience and must catch attempts to disclose personal information in
Bulgarian as well as English.)

**Policy:**

- severity ≥ 3 → the message is **blocked** (attempt to disclose
  identifying information).
- severity 1–2 → the message goes through but is flagged for admin
  review.

The same regex list is duplicated in `public/assets/js/app.js` for
immediate feedback to the user while typing (no server round trip).

### 5.2 RuleEngine (`src/Services/RuleEngine.php`)

On every send a SQL query filters `rules` by:

- `weekday_mask & current_weekday_bit ≠ 0`
- `current_time BETWEEN time_from AND time_to`
- sender / target match (NULL = wildcard, or a concrete user /
  membership in a group)

Strategy: **deny wins**. If at least one rule has `is_allow=FALSE`,
sending is blocked. Otherwise the message goes through.

Visible rules (`is_visible=TRUE`) are surfaced in the Compose UI as a
hint to the user.

### 5.3 StatsService (`src/Services/StatsService.php`)

Two methods: `anonymous()` (publicly safe aggregates) and
`nonAnonymous()` (admin-only — includes real names and top abusers).
Anonymous statistics are exposed on the admin dashboard (and could be
moved to a public page).

## 6. Security

| Threat               | Mitigation                                                              |
|----------------------|-------------------------------------------------------------------------|
| SQL injection        | Prepared statements only via PDO; `ATTR_EMULATE_PREPARES = false`       |
| XSS                  | `htmlspecialchars(..., ENT_QUOTES, 'UTF-8')` via the `e()` helper       |
| CSRF                 | Synchronizer token on every POST form (`Csrf::field()` + `Csrf::check()`)|
| Password storage     | `password_hash($pw, PASSWORD_ARGON2ID)` + `password_verify`             |
| Session fixation     | `session_regenerate_id(true)` on login                                  |
| Session cookie       | `HttpOnly`, `SameSite=Strict`, `use_strict_mode=1`                      |
| File enumeration     | nginx `deny all` for `/src`, `/docker`, `/tests`, `/vendor`             |
| Plaintext in .env    | `.env` is in `.gitignore`; `.env.example` contains no real secrets      |

## 7. Testing

### 7.1 Automated

`tests/AbuseDetectorTest.php` — 7 test cases covering every regex
pattern + a clean message + the severity policy.

```bash
docker compose run --rm php composer install
docker compose run --rm php vendor/bin/phpunit
```

### 7.2 Manual (see "Test scenarios" in README.md)

## 8. Deployment

### 8.1 Locally (Docker)

```bash
cp .env.example .env
docker compose up --build
# http://localhost:8080
```

### 8.2 On gitlab.hss.fmi.uni-sofia.bg

1. Create an account at https://gitlab.hss.fmi.uni-sofia.bg/.
2. Email the lecturer with the project name (`intrabox`).
3. Once you have rights, `git push` from the local repo.
4. Through hssmanager → create a PostgreSQL database → fill the `.env`
   parameters in their environment.

## 9. Work split

| Member         | Responsibilities                                                                                                                                                |
|----------------|-----------------------------------------------------------------------------------------------------------------------------------------------------------------|
| Yoan Baychev   | `Core/*`, `AuthController`, `AdminController`, `InboxController`, `ComposeController`, `Models/User`, `Models/Message`, `Models/AbuseLog`, `Services/AbuseDetector`, threading, login flow, layout, dashboard, abuse review. |
| Ivailo Kunchev | `GroupController`, `RulesController`, `Models/Group`, `Models/Rule`, `Services/RuleEngine`, `Services/StatsService`, group/rule views, admin statistics, public rules page.                                                  |

Shared by both: Docker setup, DB schema, this documentation,
manual testing and deployment to gitlab.

## 10. Conclusion and possible extensions

- **Push notifications** via Server-Sent Events for new messages
  without a reload.
- **File attachments** (`public/uploads/` is already prepared).
- **Pigeon integration** (column K of the exam topics) — another team
  could request integration with the notification system through a
  configuration file.
- **A truly anonymous option** — an extra flag on a message under
  which not even the admin sees the real sender (only an audit hash
  is kept).
- **Localization** — i18n for additional languages besides English.

---

*This document was prepared by the team based on the lecturer's
template; the title page (with signatures) is attached separately for
printing.*
