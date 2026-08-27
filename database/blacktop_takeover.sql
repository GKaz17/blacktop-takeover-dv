-- Blacktop Takeover submission database
-- Schema, relationships, reusable view, and sanitised demonstration data.

CREATE DATABASE IF NOT EXISTS blacktop_takeover_db
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE blacktop_takeover_db;

CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(80) NOT NULL,
    last_name VARCHAR(80) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('player', 'captain', 'organiser', 'admin') NOT NULL DEFAULT 'player',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS teams (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    slug VARCHAR(120) NOT NULL UNIQUE,
    city VARCHAR(80) NOT NULL,
    captain_id INT UNSIGNED NOT NULL,
    invite_code VARCHAR(40) UNIQUE,
    logo_path VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_team_captain FOREIGN KEY (captain_id) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS team_members (
    team_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    jersey_number TINYINT UNSIGNED,
    position VARCHAR(40),
    squad_role ENUM('player', 'vice_captain') NOT NULL DEFAULT 'player',
    status ENUM('invited', 'active', 'inactive') NOT NULL DEFAULT 'invited',
    joined_at TIMESTAMP NULL,
    PRIMARY KEY (team_id, user_id),
    CONSTRAINT fk_membership_team FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE CASCADE,
    CONSTRAINT fk_membership_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS tournaments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(140) NOT NULL,
    slug VARCHAR(160) NOT NULL UNIQUE,
    eyebrow VARCHAR(100) NOT NULL,
    route_label VARCHAR(100) NOT NULL,
    city VARCHAR(80) NOT NULL,
    venue VARCHAR(140) NOT NULL,
    starts_at DATETIME NOT NULL,
    registration_deadline DATETIME NOT NULL,
    format ENUM('3v3', '5v5') NOT NULL,
    capacity SMALLINT UNSIGNED NOT NULL,
    max_roster TINYINT UNSIGNED NOT NULL DEFAULT 8,
    entry_fee_cents INT UNSIGNED NOT NULL DEFAULT 0,
    prize_cents INT UNSIGNED NOT NULL DEFAULT 0,
    status ENUM('draft', 'open', 'full', 'in_progress', 'completed', 'cancelled') NOT NULL DEFAULT 'draft',
    description TEXT,
    check_in_notes VARCHAR(180),
    structure_notes VARCHAR(180),
    prize_notes VARCHAR(220),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS tournament_entries (
    tournament_id INT UNSIGNED NOT NULL,
    team_id INT UNSIGNED NOT NULL,
    seed SMALLINT UNSIGNED,
    status ENUM('pending', 'confirmed', 'withdrawn') NOT NULL DEFAULT 'pending',
    registered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (tournament_id, team_id),
    CONSTRAINT fk_entry_tournament FOREIGN KEY (tournament_id) REFERENCES tournaments(id) ON DELETE CASCADE,
    CONSTRAINT fk_entry_team FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS matches (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tournament_id INT UNSIGNED NOT NULL,
    home_team_id INT UNSIGNED NOT NULL,
    away_team_id INT UNSIGNED NOT NULL,
    round_name VARCHAR(60) NOT NULL,
    court VARCHAR(40),
    scheduled_at DATETIME NOT NULL,
    home_score SMALLINT UNSIGNED,
    away_score SMALLINT UNSIGNED,
    status ENUM('scheduled', 'live', 'final', 'postponed') NOT NULL DEFAULT 'scheduled',
    CONSTRAINT fk_match_tournament FOREIGN KEY (tournament_id) REFERENCES tournaments(id) ON DELETE CASCADE,
    CONSTRAINT fk_match_home_team FOREIGN KEY (home_team_id) REFERENCES teams(id),
    CONSTRAINT fk_match_away_team FOREIGN KEY (away_team_id) REFERENCES teams(id),
    INDEX idx_match_schedule (scheduled_at, status)
) ENGINE=InnoDB;

-- The application reads this reusable joined view in Match Centre.
CREATE OR REPLACE VIEW tournament_match_feed AS
SELECT
    match_record.id,
    match_record.tournament_id,
    tournament.name AS tournament_name,
    tournament.slug AS tournament_slug,
    match_record.round_name,
    match_record.court,
    match_record.scheduled_at,
    match_record.home_score,
    match_record.away_score,
    match_record.status,
    home_team.name AS home_team,
    away_team.name AS away_team
FROM matches AS match_record
INNER JOIN tournaments AS tournament ON tournament.id = match_record.tournament_id
INNER JOIN teams AS home_team ON home_team.id = match_record.home_team_id
INNER JOIN teams AS away_team ON away_team.id = match_record.away_team_id;

INSERT INTO tournaments (
    name, slug, eyebrow, route_label, city, venue, starts_at, registration_deadline,
    format, capacity, max_roster, entry_fee_cents, prize_cents, status, description,
    check_in_notes, structure_notes, prize_notes
) VALUES
(
    'COJ Summer Showdown', 'coj-summer-showdown', 'COJ / Regional qualifier', 'The road to KOS',
    'Johannesburg', 'Ellis Park Courts', '2026-08-14 10:00:00', '2026-08-12 23:59:59',
    '5v5', 16, 8, 50000, 0, 'completed',
    'Sixteen squads. One regional crown. The champions advance to King of the South.',
    '09:15 - captain and full squad', 'Group stage into knockout bracket',
    'Regional title, champion kit + KOS qualification'
),
(
    'COP Regional Qualifier', 'cop-regional-qualifier', 'COP / Regional qualifier', 'The road to KON',
    'Pretoria', 'Pitori Central Courts', '2026-08-21 10:00:00', '2026-08-19 23:59:59',
    '5v5', 16, 8, 50000, 0, 'completed',
    'Pitori squads meet for a direct route into the King of the North bracket.',
    '09:15 - captain and full squad', 'Group stage into knockout bracket',
    'Regional title, champion kit + KON qualification'
),
(
    'KON + KOS Invitational', 'kon-kos-invitational', 'Open / Invitational qualifier', 'The road to D.O.G.',
    'Johannesburg', 'Braamfontein Courts', '2026-08-29 11:00:00', '2026-08-27 23:59:59',
    '5v5', 12, 8, 65000, 0, 'open',
    'Two paths stay open for squads ready to earn their place in the Gauteng final.',
    '10:15 - captain and full squad', 'Invitational knockout bracket',
    'KON or KOS seeding + D.O.G. qualification route'
)
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    eyebrow = VALUES(eyebrow),
    route_label = VALUES(route_label),
    city = VALUES(city),
    venue = VALUES(venue),
    starts_at = VALUES(starts_at),
    registration_deadline = VALUES(registration_deadline),
    format = VALUES(format),
    capacity = VALUES(capacity),
    max_roster = VALUES(max_roster),
    entry_fee_cents = VALUES(entry_fee_cents),
    prize_cents = VALUES(prize_cents),
    status = VALUES(status),
    description = VALUES(description),
    check_in_notes = VALUES(check_in_notes),
    structure_notes = VALUES(structure_notes),
    prize_notes = VALUES(prize_notes);

-- All seed accounts use BlacktopDemo26! and fictional .test addresses.
INSERT INTO users (first_name, last_name, email, password_hash, role) VALUES
('Neo', 'Mokoena', 'neo.mokoena@blacktop.test', '$2y$10$DwWTBnXQ1PSlF83mHJgyre1lMXVQF/KzctAvQlqAqNfX/GoZUQy4C', 'captain'),
('Kabelo', 'Dlamini', 'kabelo.dlamini@blacktop.test', '$2y$10$DwWTBnXQ1PSlF83mHJgyre1lMXVQF/KzctAvQlqAqNfX/GoZUQy4C', 'player'),
('Aphiwe', 'Nkosi', 'aphiwe.nkosi@blacktop.test', '$2y$10$DwWTBnXQ1PSlF83mHJgyre1lMXVQF/KzctAvQlqAqNfX/GoZUQy4C', 'player'),
('Lerato', 'Molefe', 'lerato.molefe@blacktop.test', '$2y$10$DwWTBnXQ1PSlF83mHJgyre1lMXVQF/KzctAvQlqAqNfX/GoZUQy4C', 'player'),
('Thabo', 'Ndlovu', 'thabo.ndlovu@blacktop.test', '$2y$10$DwWTBnXQ1PSlF83mHJgyre1lMXVQF/KzctAvQlqAqNfX/GoZUQy4C', 'player'),
('Zinhle', 'Daniels', 'zinhle.daniels@blacktop.test', '$2y$10$DwWTBnXQ1PSlF83mHJgyre1lMXVQF/KzctAvQlqAqNfX/GoZUQy4C', 'captain'),
('Sipho', 'Khumalo', 'sipho.khumalo@blacktop.test', '$2y$10$DwWTBnXQ1PSlF83mHJgyre1lMXVQF/KzctAvQlqAqNfX/GoZUQy4C', 'player'),
('Ayanda', 'Tshabalala', 'ayanda.tshabalala@blacktop.test', '$2y$10$DwWTBnXQ1PSlF83mHJgyre1lMXVQF/KzctAvQlqAqNfX/GoZUQy4C', 'player'),
('Karabo', 'Maseko', 'karabo.maseko@blacktop.test', '$2y$10$DwWTBnXQ1PSlF83mHJgyre1lMXVQF/KzctAvQlqAqNfX/GoZUQy4C', 'player'),
('Tumelo', 'Mthembu', 'tumelo.mthembu@blacktop.test', '$2y$10$DwWTBnXQ1PSlF83mHJgyre1lMXVQF/KzctAvQlqAqNfX/GoZUQy4C', 'player'),
('Naledi', 'Moagi', 'organiser@blacktop.test', '$2y$10$DwWTBnXQ1PSlF83mHJgyre1lMXVQF/KzctAvQlqAqNfX/GoZUQy4C', 'organiser'),
('Kagiso', 'Radebe', 'admin@blacktop.test', '$2y$10$DwWTBnXQ1PSlF83mHJgyre1lMXVQF/KzctAvQlqAqNfX/GoZUQy4C', 'admin')
ON DUPLICATE KEY UPDATE
    first_name = VALUES(first_name),
    last_name = VALUES(last_name),
    password_hash = VALUES(password_hash),
    role = VALUES(role);

INSERT INTO teams (name, slug, city, captain_id, invite_code)
SELECT 'Midrange Hoopers', 'midrange-hoopers', 'Johannesburg', id, 'MIDRANGE26'
FROM users WHERE email = 'neo.mokoena@blacktop.test'
ON DUPLICATE KEY UPDATE captain_id = VALUES(captain_id), city = VALUES(city), invite_code = VALUES(invite_code);

INSERT INTO teams (name, slug, city, captain_id, invite_code)
SELECT 'New Jamaica', 'new-jamaica', 'Pretoria', id, 'JAMAICA26'
FROM users WHERE email = 'zinhle.daniels@blacktop.test'
ON DUPLICATE KEY UPDATE captain_id = VALUES(captain_id), city = VALUES(city), invite_code = VALUES(invite_code);

INSERT INTO team_members (team_id, user_id, jersey_number, position, squad_role, status, joined_at)
SELECT team.id, account.id, seed.jersey_number, seed.position, seed.squad_role, 'active', CURRENT_TIMESTAMP
FROM (
    SELECT 'Midrange Hoopers' AS team_name, 'neo.mokoena@blacktop.test' AS email, 7 AS jersey_number, 'Point Guard' AS position, 'player' AS squad_role
    UNION ALL SELECT 'Midrange Hoopers', 'kabelo.dlamini@blacktop.test', 11, 'Shooting Guard', 'vice_captain'
    UNION ALL SELECT 'Midrange Hoopers', 'aphiwe.nkosi@blacktop.test', 23, 'Small Forward', 'player'
    UNION ALL SELECT 'Midrange Hoopers', 'lerato.molefe@blacktop.test', 32, 'Power Forward', 'player'
    UNION ALL SELECT 'Midrange Hoopers', 'thabo.ndlovu@blacktop.test', 8, 'Centre', 'player'
    UNION ALL SELECT 'New Jamaica', 'zinhle.daniels@blacktop.test', 3, 'Point Guard', 'player'
    UNION ALL SELECT 'New Jamaica', 'sipho.khumalo@blacktop.test', 10, 'Shooting Guard', 'vice_captain'
    UNION ALL SELECT 'New Jamaica', 'ayanda.tshabalala@blacktop.test', 21, 'Small Forward', 'player'
    UNION ALL SELECT 'New Jamaica', 'karabo.maseko@blacktop.test', 24, 'Power Forward', 'player'
    UNION ALL SELECT 'New Jamaica', 'tumelo.mthembu@blacktop.test', 15, 'Centre', 'player'
) AS seed
INNER JOIN teams AS team ON team.name = seed.team_name
INNER JOIN users AS account ON account.email = seed.email
ON DUPLICATE KEY UPDATE
    jersey_number = VALUES(jersey_number),
    position = VALUES(position),
    squad_role = VALUES(squad_role),
    status = VALUES(status),
    joined_at = VALUES(joined_at);

INSERT INTO tournament_entries (tournament_id, team_id, seed, status)
SELECT tournament.id, team.id, seed.seed_number, 'confirmed'
FROM (
    SELECT 'coj-summer-showdown' AS tournament_slug, 'Midrange Hoopers' AS team_name, 1 AS seed_number
    UNION ALL SELECT 'coj-summer-showdown', 'New Jamaica', 2
    UNION ALL SELECT 'cop-regional-qualifier', 'New Jamaica', 1
    UNION ALL SELECT 'cop-regional-qualifier', 'Midrange Hoopers', 2
) AS seed
INNER JOIN tournaments AS tournament ON tournament.slug = seed.tournament_slug
INNER JOIN teams AS team ON team.name = seed.team_name
ON DUPLICATE KEY UPDATE seed = VALUES(seed), status = VALUES(status);

INSERT INTO matches (
    tournament_id, home_team_id, away_team_id, round_name, court,
    scheduled_at, home_score, away_score, status
)
SELECT tournament.id, home_team.id, away_team.id, seed.round_name, seed.court,
       seed.scheduled_at, seed.home_score, seed.away_score, 'final'
FROM (
    SELECT 'coj-summer-showdown' AS tournament_slug, 'Midrange Hoopers' AS home_name,
           'New Jamaica' AS away_name, 'Group A' AS round_name, 'Court 1' AS court,
           '2026-08-14 19:30:00' AS scheduled_at, 35 AS home_score, 29 AS away_score
    UNION ALL
    SELECT 'cop-regional-qualifier', 'Midrange Hoopers', 'New Jamaica',
           'R1', 'Court 4', '2026-08-21 18:30:00', 21, 40
) AS seed
INNER JOIN tournaments AS tournament ON tournament.slug = seed.tournament_slug
INNER JOIN teams AS home_team ON home_team.name = seed.home_name
INNER JOIN teams AS away_team ON away_team.name = seed.away_name
WHERE NOT EXISTS (
    SELECT 1
    FROM matches AS existing_match
    WHERE existing_match.tournament_id = tournament.id
      AND existing_match.home_team_id = home_team.id
      AND existing_match.away_team_id = away_team.id
      AND existing_match.scheduled_at = seed.scheduled_at
);
