-- IntraBox schema (PostgreSQL 16). Auto-loaded by the postgres image on first start.
-- The seeded admin password hash is a placeholder; PHP rewrites it from .env on first boot.

SET client_min_messages = WARNING;

CREATE TABLE IF NOT EXISTS users (
    id              SERIAL PRIMARY KEY,
    username        VARCHAR(64)  UNIQUE NOT NULL,
    real_name       VARCHAR(128) NOT NULL,
    display_alias   VARCHAR(64)  UNIQUE NOT NULL,
    email           VARCHAR(128) UNIQUE NOT NULL,
    password_hash   VARCHAR(255) NOT NULL,
    role            VARCHAR(16)  NOT NULL DEFAULT 'user'
                    CHECK (role IN ('user','admin')),
    is_active       BOOLEAN      NOT NULL DEFAULT TRUE,
    created_at      TIMESTAMPTZ  NOT NULL DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS groups (
    id              SERIAL PRIMARY KEY,
    name            VARCHAR(64)  UNIQUE NOT NULL,
    description     TEXT,
    created_by      INT          NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    created_at      TIMESTAMPTZ  NOT NULL DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS group_members (
    group_id        INT NOT NULL REFERENCES groups(id) ON DELETE CASCADE,
    user_id         INT NOT NULL REFERENCES users(id)  ON DELETE CASCADE,
    PRIMARY KEY (group_id, user_id)
);

-- weekday_mask: bitmask 1=Mon..64=Sun, 127=every day. NULL sender_*/target_* = anyone.
CREATE TABLE IF NOT EXISTS rules (
    id              SERIAL PRIMARY KEY,
    name            VARCHAR(128) NOT NULL,
    description     TEXT,
    sender_user_id  INT REFERENCES users(id)  ON DELETE CASCADE,
    sender_group_id INT REFERENCES groups(id) ON DELETE CASCADE,
    target_user_id  INT REFERENCES users(id)  ON DELETE CASCADE,
    target_group_id INT REFERENCES groups(id) ON DELETE CASCADE,
    weekday_mask    SMALLINT     NOT NULL DEFAULT 127,
    time_from       TIME         NOT NULL DEFAULT '00:00',
    time_to         TIME         NOT NULL DEFAULT '23:59',
    is_allow        BOOLEAN      NOT NULL DEFAULT TRUE,
    is_visible      BOOLEAN      NOT NULL DEFAULT TRUE,
    created_at      TIMESTAMPTZ  NOT NULL DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS messages (
    id              SERIAL PRIMARY KEY,
    sender_id       INT          NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    recipient_id    INT          REFERENCES users(id)  ON DELETE CASCADE,
    recipient_group INT          REFERENCES groups(id) ON DELETE CASCADE,
    subject         VARCHAR(255) NOT NULL,
    body            TEXT         NOT NULL,
    is_review       BOOLEAN      NOT NULL DEFAULT FALSE,
    is_anonymous    BOOLEAN      NOT NULL DEFAULT TRUE,
    parent_id       INT          REFERENCES messages(id) ON DELETE SET NULL,
    sent_at         TIMESTAMPTZ  NOT NULL DEFAULT NOW(),
    CHECK ((recipient_id IS NOT NULL) OR (recipient_group IS NOT NULL))
);

CREATE TABLE IF NOT EXISTS message_reads (
    message_id      INT NOT NULL REFERENCES messages(id) ON DELETE CASCADE,
    user_id         INT NOT NULL REFERENCES users(id)    ON DELETE CASCADE,
    read_at         TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    PRIMARY KEY (message_id, user_id)
);

CREATE TABLE IF NOT EXISTS abuse_log (
    id              SERIAL PRIMARY KEY,
    message_id      INT          REFERENCES messages(id) ON DELETE CASCADE,
    sender_id       INT          NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    pattern_matched VARCHAR(64)  NOT NULL,
    snippet         TEXT         NOT NULL,
    severity        SMALLINT     NOT NULL DEFAULT 1,
    reviewed        BOOLEAN      NOT NULL DEFAULT FALSE,
    created_at      TIMESTAMPTZ  NOT NULL DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_msg_recipient ON messages(recipient_id, sent_at DESC);
CREATE INDEX IF NOT EXISTS idx_msg_group     ON messages(recipient_group, sent_at DESC);
CREATE INDEX IF NOT EXISTS idx_msg_thread    ON messages(parent_id);
CREATE INDEX IF NOT EXISTS idx_msg_sender    ON messages(sender_id, sent_at DESC);
CREATE INDEX IF NOT EXISTS idx_abuse_unreviewed ON abuse_log(reviewed, created_at DESC);

INSERT INTO users (username, real_name, display_alias, email, password_hash, role)
VALUES (
    'admin',
    'System Administrator',
    'admin',
    '[email protected]',
    '$argon2id$v=19$m=65536,t=4,p=1$dGZkc2psZmRramw$placeholder_will_be_overwritten_on_boot',
    'admin'
)
ON CONFLICT (username) DO NOTHING;
