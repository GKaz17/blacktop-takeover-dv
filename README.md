# Blacktop Takeover

Blacktop Takeover is a server-rendered PHP and MySQL application for organising Gauteng street basketball. It gives players and captains a route into competitive tournaments while giving organisers one place to review squads, publish fixtures, record results, and manage the climb from COJ and COP through KON or KOS to D.O.G.

The project solves a practical coordination problem: community tournaments often depend on scattered messages, manual team lists, and disconnected score updates. Blacktop Takeover joins registration, squad management, tournament entry, event administration, and public results in one database-driven system.

## Main functionality

- Player and captain registration with secure password hashing.
- Login sessions and role-based navigation.
- Captain squad creation, invitations, roster editing, activity status, jersey details, and vice-captain selection.
- Tournament discovery and database-driven tournament detail pages.
- Team entry applications with organiser approval or rejection.
- Organiser CRUD for tournaments and fixtures.
- Admin-only account role management.
- Result entry, tournament-specific standings, and Match Centre feeds.
- Responsive PHP templates using semantic HTML, custom CSS, and vanilla JavaScript.

## Technology

- PHP 8 with reusable includes and server-side sessions
- MySQL / MariaDB with foreign keys, joins, subqueries, indexes, and a reusable SQL view
- Semantic HTML5, custom CSS, and vanilla JavaScript
- XAMPP for the local Apache and MySQL environment
- No React, Tailwind, Node, or Express runtime is required

## Local setup

1. Place the project folder inside `C:\xampp\htdocs`.
2. Start Apache and MySQL from the XAMPP Control Panel.
3. Open phpMyAdmin and import `database/blacktop_takeover.sql`.
4. Visit `http://localhost/blacktop-takeover/`.

The default connection targets a standard XAMPP installation. The values can be overridden with the `DB_HOST`, `DB_USER`, `DB_PASSWORD`, and `DB_NAME` environment variables.

## Sanitised demonstration accounts

The SQL export includes fictional accounts and populated tournament data. Every demonstration account uses the password `BlacktopDemo26!`.

| Role | Email |
| --- | --- |
| Admin | `admin@blacktop.test` |
| Organiser | `organiser@blacktop.test` |
| Captain, Midrange Hoopers | `neo.mokoena@blacktop.test` |
| Captain, New Jamaica | `zinhle.daniels@blacktop.test` |
| Player | `kabelo.dlamini@blacktop.test` |

The `.test` addresses are reserved fictional data and do not identify real users.

## Database implementation

The export creates six related tables and the `tournament_match_feed` SQL view. The view joins tournaments, matches, and both competing team records so Match Centre can read one consistent result set. Dashboard summaries use scalar subqueries, while the remaining application queries use prepared statements and parameter binding.

The populated export includes:

- 12 fictional accounts across all four roles
- 2 complete five-player squads
- 3 tournament routes
- 4 confirmed tournament entries
- 2 completed fixtures with independent COJ and COP results

See the [ER diagram](docs/blacktop-takeover-erd.md) and [database implementation notes](docs/database-implementation.md) for the relationships and SQL evidence.

## Project structure

| Path | Purpose |
| --- | --- |
| `assets/` | Approved imagery, textures, CSS, and vanilla JavaScript |
| `config/` | Database connection configuration |
| `database/` | Importable schema, view, and sanitised seed data |
| `docs/` | ER diagram, design rationale, and database documentation |
| `includes/` | Shared header, footer, navigation, menu, and competition helpers |
| `admin.php` | Organiser and admin tournament operations |
| `team.php` | Player and captain squad operations |
| `match-centre.php` | Tournament fixtures, results, and standings |

## Design and proposal

The visual direction treats Blacktop Takeover as a Gauteng streetball movement rather than a generic sports dashboard. The interface uses the approved dark court palette, orange, yellow, blue, green and pink accents, condensed display typography, skyline artwork, and layered tar, paint, halftone, and broken-glass textures.

The implemented reference is documented in [design and project proposal](docs/design-and-project-proposal.md). Supporting visual assets are retained under `assets/images`.

## Demonstration video

Public Google Drive URL: [Watch the Blacktop Takeover demonstration](https://drive.google.com/file/d/1RMcyg6h8CEVPHkOWKdMEi2zp-Qt5baTy/view?usp=sharing)

Before submission, open the link in an incognito window and confirm that it works without requesting access.

## Security decisions

- Passwords are stored with `password_hash()` and checked with `password_verify()`.
- User-controlled SQL values are sent through prepared statements.
- State-changing forms use session-backed CSRF tokens.
- Organiser and admin routes verify session roles on the server.
- Output is escaped before rendering in HTML.
- The submitted database uses fictional accounts instead of personal data.
