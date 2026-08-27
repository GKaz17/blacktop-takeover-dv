# Blacktop Takeover ER Diagram

The diagram below is rendered directly by GitHub. Primary keys, foreign keys, and associative entities match `database/blacktop_takeover.sql`.

```mermaid
erDiagram
    USERS {
        int id PK
        varchar first_name
        varchar last_name
        varchar email UK
        varchar password_hash
        enum role
        timestamp created_at
    }

    TEAMS {
        int id PK
        varchar name UK
        varchar slug UK
        varchar city
        int captain_id FK
        varchar invite_code UK
        varchar logo_path
        timestamp created_at
    }

    TEAM_MEMBERS {
        int team_id PK, FK
        int user_id PK, FK
        tinyint jersey_number
        varchar position
        enum squad_role
        enum status
        timestamp joined_at
    }

    TOURNAMENTS {
        int id PK
        varchar name
        varchar slug UK
        varchar city
        varchar venue
        datetime starts_at
        datetime registration_deadline
        enum format
        smallint capacity
        tinyint max_roster
        enum status
    }

    TOURNAMENT_ENTRIES {
        int tournament_id PK, FK
        int team_id PK, FK
        smallint seed
        enum status
        timestamp registered_at
    }

    MATCHES {
        int id PK
        int tournament_id FK
        int home_team_id FK
        int away_team_id FK
        varchar round_name
        varchar court
        datetime scheduled_at
        smallint home_score
        smallint away_score
        enum status
    }

    USERS ||--o{ TEAMS : captains
    USERS ||--o{ TEAM_MEMBERS : joins
    TEAMS ||--o{ TEAM_MEMBERS : contains
    TOURNAMENTS ||--o{ TOURNAMENT_ENTRIES : receives
    TEAMS ||--o{ TOURNAMENT_ENTRIES : enters
    TOURNAMENTS ||--o{ MATCHES : schedules
    TEAMS ||--o{ MATCHES : home_team
    TEAMS ||--o{ MATCHES : away_team
```

## Relationship notes

- `team_members` resolves the many-to-many relationship between users and teams and stores roster-specific information.
- `tournament_entries` resolves the many-to-many relationship between teams and tournaments and stores approval status and seeding.
- Each match belongs to one tournament and references the competing teams twice through `home_team_id` and `away_team_id`.
- Deleting a team removes its membership and tournament-entry records through cascading foreign keys.
- Deleting a tournament removes its entries and fixtures, while completed fixtures remain protected by application rules until intentionally removed.

## Derived SQL view

`tournament_match_feed` is a derived read model rather than a separate entity. It joins `matches`, `tournaments`, and two aliases of `teams` to provide named fixtures and results to Match Centre.
