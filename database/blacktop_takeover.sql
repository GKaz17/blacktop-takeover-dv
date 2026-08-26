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

INSERT INTO tournaments (
    name, slug, eyebrow, route_label, city, venue, starts_at, registration_deadline,
    format, capacity, max_roster, entry_fee_cents, prize_cents, status, description,
    check_in_notes, structure_notes, prize_notes
) VALUES
(
    'COJ Summer Showdown', 'coj-summer-showdown', 'COJ / Regional qualifier', 'The road to KON',
    'Johannesburg', 'Ellis Park Courts', '2026-08-14 10:00:00', '2026-08-12 23:59:59',
    '5v5', 16, 8, 50000, 0, 'open',
    'Sixteen squads. One regional crown. The champions advance to King of the North.',
    '09:15 - captain and full squad', 'Group stage into knockout bracket',
    'Regional title, champion kit + KON qualification'
),
(
    'COP Regional Qualifier', 'cop-regional-qualifier', 'COP / Regional qualifier', 'The road to KOS',
    'Pretoria', 'Pitori Central Courts', '2026-08-21 10:00:00', '2026-08-19 23:59:59',
    '5v5', 16, 8, 50000, 0, 'open',
    'Pitori squads meet for a direct route into the King of the South bracket.',
    '09:15 - captain and full squad', 'Group stage into knockout bracket',
    'Regional title, champion kit + KOS qualification'
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
    status = VALUES(status),
    description = VALUES(description),
    check_in_notes = VALUES(check_in_notes),
    structure_notes = VALUES(structure_notes),
    prize_notes = VALUES(prize_notes);
