# IntraBox

**Anonymous internal mail for communication** — exam project for the
*Web Programming* course, Faculty of Mathematics and Informatics, Sofia
University "St. Kliment Ohridski", summer semester 2026.

## What this is

IntraBox is a pseudo-anonymous, internal mail-like system for an
organization: employees write to each other under aliases, can create
groups and threaded discussions (reviews), and an administrator defines
**usage rules and time windows** for communication and reviews a log of
detected **abuse attempts** (attempts to disclose personal information,
caught with regular expressions).

The assignment (topic #24 of the exam topics):

> Support sending/communication with reviews; ability to define "rules"
> and usage time windows — visible to users; message grouping; ability
> to send to a group. Communication statistics — anonymous and
> non-anonymous; an algorithm to "detect" abuse (e.g. someone trying to
> disclose their name, email or other information — checked with regex
> for example).

## Tech stack

| Layer       | Technology                                  |
|-------------|---------------------------------------------|
| Backend     | **PHP 8.2+** (vanilla, no framework), PDO   |
| Database    | **PostgreSQL 16**                           |
| Frontend    | **HTML / CSS / JS** (vanilla)               |
| Containers  | **Docker + docker-compose**, nginx + PHP-FPM|
| Tests       | **PHPUnit 10** (for AbuseDetector)          |

## Layout

```
IntraBox/
├── docker/                    # Dockerfiles + nginx + init.sql
├── public/                    # web root (index.php + assets)
├── src/
│   ├── Core/                  # Database, Router, Session, Csrf, View
│   ├── Controllers/           # Auth, Inbox, Compose, Group, Rules, Admin
│   ├── Models/                # User, Message, Group, Rule, AbuseLog
│   ├── Services/              # AbuseDetector, RuleEngine, StatsService
│   └── Views/                 # PHP templates
├── tests/                     # PHPUnit
├── docs/                      # documentation (for printing)
├── docker-compose.yml
├── .env.example
└── composer.json
```

Everything outside `public/` is unreachable from the web (defense in
depth + an explicit nginx rule).

## Running

Requirements: Docker + docker compose v2.

```bash
git clone https://gitlab.hss.fmi.uni-sofia.bg/<your-team>/intrabox.git
cd intrabox
cp .env.example .env       # tweak passwords in .env
docker compose up --build  # first run builds the PHP image
```

Open `http://localhost:8080`. Sign in with the username and password
from `.env` (`ADMIN_USERNAME` / `ADMIN_PASSWORD`).

On first boot:

1. PostgreSQL runs `docker/postgres/init.sql` — creates the schema and
   one placeholder admin row.
2. On its first DB connection PHP checks the admin password and
   replaces it with a real Argon2id hash derived from `.env`. This way
   the password is controlled centrally from the `.env` file.

### Composer dependencies (optional — for PHPUnit)

```bash
docker compose run --rm php composer install
docker compose run --rm php vendor/bin/phpunit
```

## Test scenarios

1. **Sign in as admin** → `/admin/users` → create 3 users
2. **Sign in as user1** → `/compose` → write to @user2
3. **Abuse attempt:** write a message containing `[email protected]`
   or a 10-digit ID number → the system blocks it (severity 3) and
   logs it
4. **Groups:** admin creates #team → adds user1+user2+user3 → user1
   sends to #team → all three receive it
5. **Rules:** admin → `/admin/rules` → "deny user1→user2 after 18:00"
   rule → check before and after the cutoff
6. **Threading:** reply to a message → the threads are grouped
   automatically
7. **Stats:** `/admin` shows anonymous statistics and non-anonymous
   ones

## Security

- **PDO prepared statements** (no string concatenation)
- **CSRF tokens** on every POST form (synchronizer-token pattern)
- **Argon2id** password hashing
- **`htmlspecialchars`** in templates (XSS protection)
- Session cookie with `HttpOnly` + `SameSite=Strict` + regeneration on
  login
- `.php` execution outside `public/` is blocked at the nginx layer

See [docs/documentation.md](docs/documentation.md) for the full
documentation.

## Team work split

| Member         | Topic                                                                                                              |
|----------------|--------------------------------------------------------------------------------------------------------------------|
| Yoan Baychev   | Auth + Users + Admin + Inbox + Compose + Threading + AbuseDetector (Core, AuthController, AdminController, InboxController, ComposeController, sessions, CSRF, dashboard, abuse review) |
| Ivailo Kunchev | Groups + Rules + RuleEngine + StatsService (GroupController, RulesController, public rules page, admin statistics) |

Shared by both: Docker setup, schema, documentation, testing.

## License

Exam project — for academic use only.
