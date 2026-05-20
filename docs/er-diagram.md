# IntraBox — ER diagram

```
                    ┌──────────────────────┐
                    │       users          │
                    │──────────────────────│
                    │ id (PK)              │
                    │ username             │
                    │ real_name            │  ← admin only
                    │ display_alias        │
                    │ email                │
                    │ password_hash        │
                    │ role                 │
                    │ is_active            │
                    │ created_at           │
                    └──────┬───────────────┘
                           │
              ┌────────────┼─────────────────────────────────────┐
              │            │                                     │
              │            │            ┌──────────────────┐    │
              │            │            │     groups        │    │
              │            │            │──────────────────│    │
              │            │            │ id (PK)           │    │
              │            │            │ name              │    │
              │            │            │ description       │    │
              │            │            │ created_by → user │    │
              │            │            │ created_at        │    │
              │            │            └────────┬──────────┘    │
              │            │                     │               │
              │            │     ┌───────────────┴────────┐      │
              │            │     │   group_members        │      │
              │            │     │────────────────────────│      │
              │            │     │ group_id (FK)          │      │
              │            │     │ user_id  (FK)          │      │
              │            │     │ PRIMARY KEY composite   │      │
              │            │     └────────────────────────┘      │
              │            │                                     │
              │            ▼                                     ▼
        ┌─────┴─────────────────────────────────────────────────────┐
        │                       messages                              │
        │─────────────────────────────────────────────────────────────│
        │ id (PK)                                                     │
        │ sender_id      → users.id                                   │
        │ recipient_id   → users.id    (NULLABLE)                     │
        │ recipient_group→ groups.id   (NULLABLE)                     │
        │ subject                                                     │
        │ body                                                        │
        │ is_review                                                   │
        │ is_anonymous                                                │
        │ parent_id      → messages.id (self, threading)              │
        │ sent_at                                                     │
        │ CHECK (recipient_id IS NOT NULL OR recipient_group IS NOT NULL)│
        └─────┬───────────────────────────────────────────┬──────────┘
              │                                           │
              │           ┌───────────────────────────────┘
              │           │
              │           ▼
              │     ┌─────────────────────────┐
              │     │   message_reads          │
              │     │──────────────────────────│
              │     │ message_id (FK, PK part) │
              │     │ user_id    (FK, PK part) │
              │     │ read_at                  │
              │     └─────────────────────────┘
              │
              ▼
        ┌─────────────────────────────────────┐
        │         abuse_log                    │
        │──────────────────────────────────────│
        │ id (PK)                              │
        │ message_id  → messages.id (NULLABLE) │  ← NULL when blocked
        │ sender_id   → users.id                │
        │ pattern_matched                      │
        │ snippet                              │
        │ severity                             │
        │ reviewed                             │
        │ created_at                           │
        └──────────────────────────────────────┘


        ┌─────────────────────────────────────┐
        │             rules                    │
        │──────────────────────────────────────│
        │ id (PK)                              │
        │ name, description                    │
        │ sender_user_id  → users.id  (NULL)   │
        │ sender_group_id → groups.id (NULL)   │
        │ target_user_id  → users.id  (NULL)   │
        │ target_group_id → groups.id (NULL)   │
        │ weekday_mask  (1=Mon..64=Sun)        │
        │ time_from, time_to                   │
        │ is_allow  (TRUE=allow, FALSE=deny)   │
        │ is_visible (visible to user)         │
        │ created_at                           │
        └──────────────────────────────────────┘
```

## Cardinalities

- 1 user ↔ N messages (sender)
- 1 user ↔ 0..N messages (recipient — single)
- 1 group ↔ 0..N messages (recipient — fan-out)
- N users ↔ N groups (via group_members)
- 1 message ↔ 0..N replies (parent_id self-FK)
- 1 message ↔ 0..N reads (one per recipient)
- 1 user ↔ N abuse_log entries (as sender)

## Indexes

```sql
idx_msg_recipient    ON messages(recipient_id, sent_at DESC)
idx_msg_group        ON messages(recipient_group, sent_at DESC)
idx_msg_thread       ON messages(parent_id)
idx_msg_sender       ON messages(sender_id, sent_at DESC)
idx_abuse_unreviewed ON abuse_log(reviewed, created_at DESC)
```
