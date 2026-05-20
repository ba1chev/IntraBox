==============================================================
IntraBox — Anonymous internal mail for communication
Exam project for Web Programming, FMI, Sofia University, summer 2026
==============================================================

Topic:    #24 — "Anonymous internal 'mail' for communication"
Version:  draft1 (rename to 'final' before defense)
Date:     2026-05-17

AUTHORS
-------
Member 1: <First Last>, faculty no. <NNNNNN>
Member 2: <First Last>, faculty no. <NNNNNN>
Member 3: <First Last>, faculty no. <NNNNNN>

ARCHIVE CONTENTS
----------------
- src/                — PHP source (Core, Controllers, Models, Services, Views)
- public/             — web root (index.php + CSS/JS)
- docker/             — Dockerfile, nginx config, postgres init.sql
- tests/              — PHPUnit tests for AbuseDetector
- docs/
    documentation.md  — full documentation
    er-diagram.md     — ER diagram (text version)
- docker-compose.yml
- .env.example        — template for environment variables
- composer.json
- README.md           — Markdown version for GitLab
- README.txt          — this file

RUNNING THE PROJECT
-------------------
Requirements:
    - Docker Engine 24+
    - Docker Compose v2

Steps:
    1. cp .env.example .env
       (optionally change the passwords inside)
    2. docker compose up --build
    3. Open in a browser: http://localhost:8080
    4. Sign in with the username and password from .env
       (ADMIN_USERNAME / ADMIN_PASSWORD; default: admin / admin1234)

On first boot PostgreSQL runs docker/postgres/init.sql automatically;
on its first request PHP rewrites the admin password so it matches
the .env value (Argon2id hash).

INSTALLATION NOTES
------------------
- Ports: 8080 (web) and 5433 (postgres) must be free.
  If they are taken — change them in docker-compose.yml.
- For the PHPUnit tests run composer install first:
    docker compose run --rm php composer install
    docker compose run --rm php vendor/bin/phpunit

WHAT WE STILL EXPECT TO FINISH FOR THE FINAL VERSION
----------------------------------------------------
- (TODO if you want) push notifications via Server-Sent Events
- (TODO if you want) integration with pigeon (column K of the topics)
- finalising the screenshots in the documentation
- filling the title page with signatures

ADDITIONAL RESOURCES
--------------------
- GitLab repo: https://gitlab.hss.fmi.uni-sofia.bg/<your-team>/intrabox
- Lecturer's documentation template: used for docs/documentation.md
