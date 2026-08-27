# Database Implementation

## Schema design

The database uses six normalised InnoDB tables. Accounts are stored once in `users`, squad-specific information is separated into `team_members`, and tournament participation is separated into `tournament_entries`. This avoids repeating user, team, and event information in fixture records.

Foreign keys enforce the main relationships. Composite primary keys prevent duplicate memberships and duplicate entries, while unique indexes protect emails, team names, slugs, and invitation codes.

## CRUD evidence

- Create: registration creates users, captains create teams, teams apply to tournaments, and organisers create tournaments and fixtures.
- Read: public tournament pages, squad rosters, organiser dashboards, and Match Centre use database queries.
- Update: captains edit roster details and vice-captains, organisers update tournaments and scores, and admins update account roles.
- Delete: captains remove eligible members, organisers remove safe fixtures, and tournaments can be deleted after dependency checks.

## Advanced SQL evidence

### Joins

Tournament details, squad data, organiser approvals, fixture management, and Match Centre combine related tables through inner and left joins. The `tournament_match_feed` view joins each match to its tournament and to home and away aliases of `teams`.

### Subqueries

The organiser dashboard uses scalar subqueries to calculate team, tournament, approval, fixture, and D.O.G. seed totals in one request. Eligibility and duplicate checks also use focused subqueries before state-changing actions.

### View

`tournament_match_feed` provides a reusable named result set for fixtures and scores. `match-centre.php` filters this view by tournament ID, preventing similarly named teams in separate tournaments from sharing the wrong result feed.

## Security and integrity

- Prepared statements protect dynamic query values.
- Password hashes use PHP's current default password algorithm.
- Transactions protect multi-table registration operations.
- CSRF tokens protect authenticated form submissions.
- Role checks run before organiser and admin actions.
- Foreign keys and enum values constrain invalid states.

## Import verification

The submission export was tested in a separate empty database. It produced 12 users, 2 teams, 10 memberships, 3 tournaments, 4 tournament entries, 2 matches, and the `tournament_match_feed` view. The two seeded scores remain isolated by tournament: COJ is 35 to 29 and COP is 21 to 40.
