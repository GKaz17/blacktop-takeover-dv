<?php
session_start();

require_once __DIR__ . '/config/connection.php';

if (!in_array($_SESSION['user_role'] ?? null, ['organiser', 'admin'], true)) {
    header('Location: /blacktop-takeover/home.php');
    exit;
}

if (empty($_SESSION['admin_csrf'])) {
    $_SESSION['admin_csrf'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $feedback = ['type' => 'error', 'message' => 'The organiser action could not be completed.'];
    $submittedToken = (string) ($_POST['csrf_token'] ?? '');

    if (!hash_equals($_SESSION['admin_csrf'], $submittedToken)) {
        $feedback['message'] = 'Your organiser session expired. Please try again.';
    } else {
        $action = (string) ($_POST['action'] ?? '');

        if ($action === 'review_entry') {
            $tournamentId = filter_input(INPUT_POST, 'tournament_id', FILTER_VALIDATE_INT);
            $teamId = filter_input(INPUT_POST, 'team_id', FILTER_VALIDATE_INT);
            $decision = (string) ($_POST['decision'] ?? '');
            $newStatus = $decision === 'approve' ? 'confirmed' : ($decision === 'decline' ? 'withdrawn' : null);

            if ($tournamentId && $teamId && $newStatus) {
                $review = $conn->prepare("UPDATE tournament_entries SET status = ? WHERE tournament_id = ? AND team_id = ? AND status = 'pending'");
                $review->bind_param('sii', $newStatus, $tournamentId, $teamId);
                $review->execute();
                $feedback = $review->affected_rows > 0
                    ? ['type' => 'success', 'message' => $newStatus === 'confirmed' ? 'Team application approved.' : 'Team application declined.']
                    : ['type' => 'error', 'message' => 'That application is no longer pending.'];
            }
        } elseif ($action === 'create_fixture') {
            $tournamentId = filter_input(INPUT_POST, 'tournament_id', FILTER_VALIDATE_INT);
            $homeTeamId = filter_input(INPUT_POST, 'home_team_id', FILTER_VALIDATE_INT);
            $awayTeamId = filter_input(INPUT_POST, 'away_team_id', FILTER_VALIDATE_INT);
            $roundName = trim((string) ($_POST['round_name'] ?? ''));
            $court = trim((string) ($_POST['court'] ?? ''));
            $scheduledDate = trim((string) ($_POST['scheduled_date'] ?? ''));
            $scheduledTime = trim((string) ($_POST['scheduled_time'] ?? ''));
            $scheduledAt = DateTimeImmutable::createFromFormat('!Y-m-d H:i', $scheduledDate . ' ' . $scheduledTime);

            if ($tournamentId && $homeTeamId && $awayTeamId && $homeTeamId !== $awayTeamId && $roundName !== '' && $scheduledAt) {
                $eligibleTeams = $conn->prepare(
                    "SELECT COUNT(DISTINCT team_id) AS eligible_count
                     FROM tournament_entries
                     WHERE tournament_id = ? AND team_id IN (?, ?) AND status = 'confirmed'"
                );
                $eligibleTeams->bind_param('iii', $tournamentId, $homeTeamId, $awayTeamId);
                $eligibleTeams->execute();
                $eligibleCount = (int) $eligibleTeams->get_result()->fetch_assoc()['eligible_count'];

                if ($eligibleCount === 2) {
                    $scheduledAtSql = $scheduledAt->format('Y-m-d H:i:s');
                    $fixture = $conn->prepare(
                        "INSERT INTO matches (tournament_id, home_team_id, away_team_id, round_name, court, scheduled_at, status)
                         VALUES (?, ?, ?, ?, ?, ?, 'scheduled')"
                    );
                    $fixture->bind_param('iiisss', $tournamentId, $homeTeamId, $awayTeamId, $roundName, $court, $scheduledAtSql);
                    $fixture->execute();
                    $feedback = ['type' => 'success', 'message' => 'Fixture published to Match Centre.'];
                } else {
                    $feedback['message'] = 'Both teams must be approved for that tournament first.';
                }
            } else {
                $feedback['message'] = 'Complete the fixture fields and choose two different teams.';
            }
        } elseif ($action === 'update_score') {
            $matchId = filter_input(INPUT_POST, 'match_id', FILTER_VALIDATE_INT);
            $homeScore = filter_input(INPUT_POST, 'home_score', FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 999]]);
            $awayScore = filter_input(INPUT_POST, 'away_score', FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 999]]);
            $matchStatus = (string) ($_POST['match_status'] ?? '');

            if ($matchId && $homeScore !== false && $awayScore !== false && in_array($matchStatus, ['live', 'final'], true)) {
                $scoreUpdate = $conn->prepare('UPDATE matches SET home_score = ?, away_score = ?, status = ? WHERE id = ?');
                $scoreUpdate->bind_param('iisi', $homeScore, $awayScore, $matchStatus, $matchId);
                $scoreUpdate->execute();
                $feedback = ['type' => 'success', 'message' => $matchStatus === 'final' ? 'Final score published.' : 'Live score updated.'];
            } else {
                $feedback['message'] = 'Enter valid scores and select live or final.';
            }
        }
    }

    $_SESSION['admin_feedback'] = $feedback;
    header('Location: /blacktop-takeover/admin.php');
    exit;
}

$feedback = $_SESSION['admin_feedback'] ?? null;
unset($_SESSION['admin_feedback']);

$metrics = $conn->query(
    "SELECT
        (SELECT COUNT(*) FROM teams) AS total_teams,
        (SELECT COUNT(*) FROM tournaments WHERE status IN ('open', 'in_progress')) AS active_tournaments,
        (SELECT COUNT(*) FROM tournament_entries WHERE status = 'pending') AS pending_approvals,
        (SELECT COUNT(*) FROM matches WHERE scheduled_at BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 7 DAY)) AS fixtures_this_week"
)->fetch_assoc();

$dashboardMetrics = [
    ['label' => 'Total teams', 'value' => (string) $metrics['total_teams'], 'tone' => 'orange'],
    ['label' => 'Active tournaments', 'value' => (string) $metrics['active_tournaments'], 'tone' => 'yellow'],
    ['label' => 'Pending approvals', 'value' => (string) $metrics['pending_approvals'], 'tone' => 'blue'],
    ['label' => 'Fixtures this week', 'value' => (string) $metrics['fixtures_this_week'], 'tone' => 'pink'],
];

$registrationResult = $conn->query(
    "SELECT t.id, t.name, t.capacity,
            COUNT(DISTINCT CASE WHEN te.status IN ('pending', 'confirmed') THEN te.team_id END) AS entry_count
     FROM tournaments t
     LEFT JOIN tournament_entries te ON te.tournament_id = t.id
     WHERE t.status <> 'cancelled'
     GROUP BY t.id
     ORDER BY t.starts_at"
);
$tournamentRows = $registrationResult->fetch_all(MYSQLI_ASSOC);
$registrationBars = [];
$toneCycle = ['orange', 'yellow', 'blue'];
foreach ($tournamentRows as $index => $tournament) {
    $registrationBars[] = [
        'label' => mb_substr($tournament['name'], 0, 12),
        'value' => (int) round(((int) $tournament['entry_count'] / max(1, (int) $tournament['capacity'])) * 100),
        'tone' => $toneCycle[$index % count($toneCycle)],
    ];
}

$pendingTeams = $conn->query(
    "SELECT te.tournament_id, te.team_id, t.name, tournament.name AS event,
            CONCAT(captain.first_name, ' ', captain.last_name) AS captain,
            COUNT(DISTINCT CASE WHEN tm.status = 'active' THEN tm.user_id END) AS roster_count,
            tournament.max_roster
     FROM tournament_entries te
     JOIN teams t ON t.id = te.team_id
     JOIN users captain ON captain.id = t.captain_id
     JOIN tournaments tournament ON tournament.id = te.tournament_id
     LEFT JOIN team_members tm ON tm.team_id = t.id
     WHERE te.status = 'pending'
     GROUP BY te.tournament_id, te.team_id
     ORDER BY te.registered_at"
)->fetch_all(MYSQLI_ASSOC);

$confirmedEntries = $conn->query(
    "SELECT te.tournament_id, te.team_id, tournament.name AS tournament_name, team.name AS team_name
     FROM tournament_entries te
     JOIN tournaments tournament ON tournament.id = te.tournament_id
     JOIN teams team ON team.id = te.team_id
     WHERE te.status = 'confirmed'
     ORDER BY tournament.starts_at, team.name"
)->fetch_all(MYSQLI_ASSOC);

$adminMatches = $conn->query(
    "SELECT m.id, tournament.name AS tournament_name, home.name AS home_team, away.name AS away_team,
            m.home_score, m.away_score, m.status, m.scheduled_at
     FROM matches m
     JOIN tournaments tournament ON tournament.id = m.tournament_id
     JOIN teams home ON home.id = m.home_team_id
     JOIN teams away ON away.id = m.away_team_id
     WHERE m.status IN ('scheduled', 'live')
     ORDER BY m.scheduled_at"
)->fetch_all(MYSQLI_ASSOC);

$pageTitle = 'Tournament Dashboard';
$pageDescription = 'Blacktop Takeover organiser control deck.';
$hideNavigation = true;
$bodyClass = 'admin-control-page';
$courtMenuActive = 'organiser';

require __DIR__ . '/includes/header.php';
?>
<div class="admin-deck" data-admin-deck>
    <img class="admin-deck__mural" src="/blacktop-takeover/assets/images/figma/admin-control-deck-mural.svg" alt="" aria-hidden="true">

    <aside class="admin-rail">
        <img src="/blacktop-takeover/assets/images/figma/admin-organiser-rail.svg" alt="" aria-hidden="true">
        <a href="/blacktop-takeover/home.php">Blacktop<br>Takeover</a>
        <span>011 × 012</span>
        <strong>Run the<br>court</strong>
        <p>Run the court<br>Own the night</p>
    </aside>

    <section class="admin-deck__content">
        <header class="admin-deck__header">
            <div>
                <h1>Tournament dashboard</h1>
                <p>Control deck / organiser mode</p>
            </div>
            <span>D.O.G. finals seed tracker&nbsp; / &nbsp;00 of 04 locked</span>
            <button class="court-menu-trigger" type="button" aria-label="Open Blacktop menu" aria-controls="court-menu" aria-expanded="false" data-court-menu-trigger>
                <img src="/blacktop-takeover/assets/images/figma/navigation-basketball-trigger.svg" alt="">
            </button>
        </header>

        <?php if ($feedback): ?>
            <p class="admin-feedback admin-feedback--<?= e($feedback['type']) ?>" role="status"><?= e($feedback['message']) ?></p>
        <?php endif; ?>

        <section class="admin-metrics" aria-label="Tournament metrics">
            <?php foreach ($dashboardMetrics as $metric): ?>
                <article class="admin-metric admin-metric--<?= e($metric['tone']) ?>">
                    <span><?= e($metric['label']) ?></span>
                    <strong><?= e($metric['value']) ?></strong>
                </article>
            <?php endforeach; ?>
        </section>

        <section class="registration-chart" aria-labelledby="registration-chart-title">
            <h2 id="registration-chart-title">Registrations by tournament</h2>
            <div class="registration-chart__plot">
                <?php foreach ($registrationBars as $bar): ?>
                    <div class="registration-bar registration-bar--<?= e($bar['tone']) ?>">
                        <span class="registration-bar__fill" style="--registration-level: <?= e((string) $bar['value']) ?>%"></span>
                        <small><?= e($bar['label']) ?></small>
                        <span class="sr-only"><?= e((string) $bar['value']) ?> percent of available registration capacity</span>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php if (array_sum(array_column($registrationBars, 'value')) === 0): ?>
                <p class="registration-chart__empty">Registration activity will build here as captains submit teams.</p>
            <?php endif; ?>
        </section>

        <section class="approval-queue" aria-labelledby="approval-queue-title">
            <h2 id="approval-queue-title">Pending team approvals</h2>
            <div class="approval-queue__list">
                <?php foreach ($pendingTeams as $team): ?>
                    <button
                        type="button"
                        data-approval-review
                        data-team-name="<?= e($team['name']) ?>"
                        data-team-event="<?= e($team['event']) ?>"
                        data-team-captain="<?= e($team['captain']) ?>"
                        data-team-roster="<?= e($team['roster_count'] . ' / ' . $team['max_roster']) ?>"
                        data-tournament-id="<?= e((string) $team['tournament_id']) ?>"
                        data-team-id="<?= e((string) $team['team_id']) ?>"
                    >
                        <span><strong><?= e($team['name']) ?></strong> — <?= e($team['event']) ?></span>
                        <b>Pending</b>
                    </button>
                <?php endforeach; ?>
                <?php if ($pendingTeams === []): ?>
                    <p class="admin-empty-state">No team applications are waiting for review.</p>
                <?php endif; ?>
            </div>
        </section>

        <section class="admin-operations" aria-label="Fixture operations">
            <article class="admin-operation-card">
                <h2>Create fixture</h2>
                <p>Only approved teams can be placed into a tournament fixture.</p>
                <form method="post" action="/blacktop-takeover/admin.php">
                    <input type="hidden" name="csrf_token" value="<?= e($_SESSION['admin_csrf']) ?>">
                    <input type="hidden" name="action" value="create_fixture">
                    <label>Tournament
                        <select name="tournament_id" required data-fixture-tournament>
                            <option value="">Choose tournament</option>
                            <?php foreach ($tournamentRows as $tournament): ?>
                                <option value="<?= e((string) $tournament['id']) ?>"><?= e($tournament['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <div class="admin-operation-card__split">
                        <label>Home team
                            <select name="home_team_id" required data-fixture-home-team>
                                <option value="">Choose approved team</option>
                                <?php foreach ($confirmedEntries as $entry): ?>
                                    <option value="<?= e((string) $entry['team_id']) ?>" data-tournament-id="<?= e((string) $entry['tournament_id']) ?>"><?= e($entry['team_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label>Away team
                            <select name="away_team_id" required data-fixture-away-team>
                                <option value="">Choose approved team</option>
                                <?php foreach ($confirmedEntries as $entry): ?>
                                    <option value="<?= e((string) $entry['team_id']) ?>" data-tournament-id="<?= e((string) $entry['tournament_id']) ?>"><?= e($entry['team_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                    </div>
                    <div class="admin-operation-card__split">
                        <label>Round<input name="round_name" maxlength="60" placeholder="Group A / R1" required></label>
                        <label>Court<input name="court" maxlength="40" placeholder="Court 1"></label>
                    </div>
                    <div class="admin-operation-card__split">
                        <div class="admin-picker-field">
                            <label for="fixture-date">Fixture date</label>
                            <span class="admin-picker-control">
                                <input id="fixture-date" type="date" name="scheduled_date" required data-fixture-date>
                                <button type="button" aria-label="Open fixture calendar" aria-controls="fixture-date" data-fixture-date-open>Calendar</button>
                            </span>
                        </div>
                        <div class="admin-picker-field">
                            <label for="fixture-time">Tip-off time</label>
                            <span class="admin-picker-control">
                                <input id="fixture-time" type="time" name="scheduled_time" step="300" required data-fixture-time>
                                <button type="button" aria-label="Open tip-off time picker" aria-controls="fixture-time" data-fixture-time-open>Time</button>
                            </span>
                        </div>
                    </div>
                    <p class="admin-operation-warning" data-fixture-warning>Select a tournament to load its approved teams.</p>
                    <button type="submit" disabled data-fixture-submit>Publish fixture</button>
                </form>
            </article>

            <article class="admin-operation-card">
                <h2>Live score desk</h2>
                <p>Update a published fixture, then mark it final to move the standings.</p>
                <div class="admin-score-list">
                    <?php foreach ($adminMatches as $match): ?>
                        <form method="post" action="/blacktop-takeover/admin.php">
                            <input type="hidden" name="csrf_token" value="<?= e($_SESSION['admin_csrf']) ?>">
                            <input type="hidden" name="action" value="update_score">
                            <input type="hidden" name="match_id" value="<?= e((string) $match['id']) ?>">
                            <span><?= e($match['home_team']) ?> <b>vs</b> <?= e($match['away_team']) ?></span>
                            <small><?= e($match['tournament_name']) ?> / <?= e((new DateTimeImmutable($match['scheduled_at']))->format('d M H:i')) ?></small>
                            <div>
                                <input type="number" name="home_score" min="0" max="999" value="<?= e((string) ($match['home_score'] ?? 0)) ?>" aria-label="<?= e($match['home_team']) ?> score" required>
                                <input type="number" name="away_score" min="0" max="999" value="<?= e((string) ($match['away_score'] ?? 0)) ?>" aria-label="<?= e($match['away_team']) ?> score" required>
                                <select name="match_status" aria-label="Match status"><option value="live"<?= $match['status'] === 'live' ? ' selected' : '' ?>>Live</option><option value="final">Final</option></select>
                                <button type="submit">Update</button>
                            </div>
                        </form>
                    <?php endforeach; ?>
                    <?php if ($adminMatches === []): ?>
                        <p class="admin-empty-state">No scheduled or live fixtures need a score.</p>
                    <?php endif; ?>
                </div>
            </article>
        </section>
    </section>
</div>

<dialog class="approval-dialog" data-approval-dialog aria-labelledby="approval-dialog-title">
    <div class="approval-dialog__heading">
        <div>
            <span>Application review</span>
            <h2 id="approval-dialog-title" data-approval-team>Team application</h2>
        </div>
        <button type="button" aria-label="Close application review" data-approval-close>&times;</button>
    </div>
    <dl>
        <div><dt>Tournament</dt><dd data-approval-event></dd></div>
        <div><dt>Captain</dt><dd data-approval-captain></dd></div>
        <div><dt>Roster</dt><dd data-approval-roster></dd></div>
        <div><dt>Status</dt><dd><strong>Pending</strong></dd></div>
    </dl>
    <form class="approval-dialog__actions" method="post" action="/blacktop-takeover/admin.php">
        <input type="hidden" name="csrf_token" value="<?= e($_SESSION['admin_csrf']) ?>">
        <input type="hidden" name="action" value="review_entry">
        <input type="hidden" name="tournament_id" value="" data-approval-tournament-id>
        <input type="hidden" name="team_id" value="" data-approval-team-id>
        <button type="submit" name="decision" value="approve">Approve team</button>
        <button type="submit" name="decision" value="decline">Decline</button>
    </form>
</dialog>

<?php require __DIR__ . '/includes/court-menu.php'; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
