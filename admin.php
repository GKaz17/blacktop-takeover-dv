<?php
session_start();

require_once __DIR__ . '/config/connection.php';

$currentUserId = (int) ($_SESSION['user_id'] ?? 0);
$currentUserRole = (string) ($_SESSION['user_role'] ?? '');

if (!in_array($currentUserRole, ['organiser', 'admin'], true)) {
    header('Location: /blacktop-takeover/home.php');
    exit;
}

function adminPageSlug(string $name): string
{
    $slug = strtolower((string) preg_replace('/[^a-z0-9]+/i', '-', $name));
    return trim($slug, '-') . '-' . bin2hex(random_bytes(3));
}

function adminPageDateTime(string $date, string $time): ?DateTimeImmutable
{
    $dateTime = DateTimeImmutable::createFromFormat('!Y-m-d H:i', trim($date) . ' ' . trim($time));
    $errors = DateTimeImmutable::getLastErrors();

    if (!$dateTime || (is_array($errors) && ($errors['warning_count'] > 0 || $errors['error_count'] > 0))) {
        return null;
    }

    return $dateTime;
}

function adminPageMoneyToCents(string $amount): ?int
{
    if ($amount === '' || !is_numeric($amount) || (float) $amount < 0) {
        return null;
    }

    return (int) round((float) $amount * 100);
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
        } elseif ($action === 'create_tournament') {
            $name = trim((string) ($_POST['name'] ?? ''));
            $eyebrow = trim((string) ($_POST['eyebrow'] ?? ''));
            $routeLabel = trim((string) ($_POST['route_label'] ?? ''));
            $city = trim((string) ($_POST['city'] ?? ''));
            $venue = trim((string) ($_POST['venue'] ?? ''));
            $format = (string) ($_POST['format'] ?? '');
            $status = (string) ($_POST['status'] ?? 'draft');
            $capacity = filter_input(INPUT_POST, 'capacity', FILTER_VALIDATE_INT, ['options' => ['min_range' => 2, 'max_range' => 128]]);
            $maxRoster = filter_input(INPUT_POST, 'max_roster', FILTER_VALIDATE_INT, ['options' => ['min_range' => 3, 'max_range' => 20]]);
            $entryFeeCents = adminPageMoneyToCents(trim((string) ($_POST['entry_fee'] ?? '')));
            $prizeCents = adminPageMoneyToCents(trim((string) ($_POST['prize'] ?? '')));
            $startsAt = adminPageDateTime((string) ($_POST['starts_date'] ?? ''), (string) ($_POST['starts_time'] ?? ''));
            $deadline = adminPageDateTime((string) ($_POST['deadline_date'] ?? ''), (string) ($_POST['deadline_time'] ?? ''));
            $description = trim((string) ($_POST['description'] ?? ''));

            if ($name === '' || $eyebrow === '' || $routeLabel === '' || $city === '' || $venue === '' || !$startsAt || !$deadline || $deadline > $startsAt || !$capacity || !$maxRoster || $entryFeeCents === null || $prizeCents === null || !in_array($format, ['3v3', '5v5'], true) || !in_array($status, ['draft', 'open'], true)) {
                $feedback['message'] = 'Complete the event details and keep the registration deadline before tip-off.';
            } else {
                try {
                    $slug = adminPageSlug($name);
                    $startsAtSql = $startsAt->format('Y-m-d H:i:s');
                    $deadlineSql = $deadline->format('Y-m-d H:i:s');
                    $checkInNotes = 'Captain and full active roster required';
                    $structureNotes = $format === '3v3' ? 'Pool play into knockout bracket' : 'Group stage into knockout bracket';
                    $prizeNotes = $prizeCents > 0 ? 'Prize purse and qualification route' : 'Qualification route and champion recognition';
                    $insertTournament = $conn->prepare(
                        'INSERT INTO tournaments
                         (name, slug, eyebrow, route_label, city, venue, starts_at, registration_deadline, format, capacity, max_roster, entry_fee_cents, prize_cents, status, description, check_in_notes, structure_notes, prize_notes)
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
                    );
                    $insertTournament->bind_param(
                        'sssssssssiiiisssss',
                        $name,
                        $slug,
                        $eyebrow,
                        $routeLabel,
                        $city,
                        $venue,
                        $startsAtSql,
                        $deadlineSql,
                        $format,
                        $capacity,
                        $maxRoster,
                        $entryFeeCents,
                        $prizeCents,
                        $status,
                        $description,
                        $checkInNotes,
                        $structureNotes,
                        $prizeNotes
                    );
                    $insertTournament->execute();
                    $feedback = ['type' => 'success', 'message' => $status === 'open' ? 'Tournament published to the season board.' : 'Tournament saved as a draft.'];
                } catch (mysqli_sql_exception $exception) {
                    $feedback['message'] = 'The tournament could not be created. Check its name and dates.';
                }
            }
        } elseif ($action === 'update_tournament') {
            $tournamentId = filter_input(INPUT_POST, 'tournament_id', FILTER_VALIDATE_INT);
            $name = trim((string) ($_POST['name'] ?? ''));
            $city = trim((string) ($_POST['city'] ?? ''));
            $venue = trim((string) ($_POST['venue'] ?? ''));
            $format = (string) ($_POST['format'] ?? '');
            $status = (string) ($_POST['status'] ?? '');
            $capacity = filter_input(INPUT_POST, 'capacity', FILTER_VALIDATE_INT, ['options' => ['min_range' => 2, 'max_range' => 128]]);
            $maxRoster = filter_input(INPUT_POST, 'max_roster', FILTER_VALIDATE_INT, ['options' => ['min_range' => 3, 'max_range' => 20]]);
            $startsAt = adminPageDateTime((string) ($_POST['starts_date'] ?? ''), (string) ($_POST['starts_time'] ?? ''));
            $deadline = adminPageDateTime((string) ($_POST['deadline_date'] ?? ''), (string) ($_POST['deadline_time'] ?? ''));

            if (!$tournamentId || $name === '' || $city === '' || $venue === '' || !$startsAt || !$deadline || $deadline > $startsAt || !$capacity || !$maxRoster || !in_array($format, ['3v3', '5v5'], true) || !in_array($status, ['draft', 'open', 'full', 'in_progress', 'completed', 'cancelled'], true)) {
                $feedback['message'] = 'Check the tournament fields before saving the season update.';
            } else {
                $startsAtSql = $startsAt->format('Y-m-d H:i:s');
                $deadlineSql = $deadline->format('Y-m-d H:i:s');
                $updateTournament = $conn->prepare(
                    'UPDATE tournaments SET name = ?, city = ?, venue = ?, starts_at = ?, registration_deadline = ?, status = ?, capacity = ?, max_roster = ?, format = ? WHERE id = ?'
                );
                $updateTournament->bind_param('ssssssiisi', $name, $city, $venue, $startsAtSql, $deadlineSql, $status, $capacity, $maxRoster, $format, $tournamentId);
                $updateTournament->execute();
                $feedback = ['type' => 'success', 'message' => 'Tournament schedule and status updated.'];
            }
        } elseif ($action === 'create_fixture') {
            $tournamentId = filter_input(INPUT_POST, 'tournament_id', FILTER_VALIDATE_INT);
            $homeTeamId = filter_input(INPUT_POST, 'home_team_id', FILTER_VALIDATE_INT);
            $awayTeamId = filter_input(INPUT_POST, 'away_team_id', FILTER_VALIDATE_INT);
            $roundName = trim((string) ($_POST['round_name'] ?? ''));
            $court = trim((string) ($_POST['court'] ?? ''));
            $scheduledAt = adminPageDateTime((string) ($_POST['scheduled_date'] ?? ''), (string) ($_POST['scheduled_time'] ?? ''));

            if ($tournamentId && $homeTeamId && $awayTeamId && $homeTeamId !== $awayTeamId && $roundName !== '' && $scheduledAt && strlen($roundName) <= 60 && strlen($court) <= 40) {
                $eligibleTeams = $conn->prepare(
                    "SELECT COUNT(DISTINCT team_id) AS eligible_count FROM tournament_entries WHERE tournament_id = ? AND team_id IN (?, ?) AND status = 'confirmed'"
                );
                $eligibleTeams->bind_param('iii', $tournamentId, $homeTeamId, $awayTeamId);
                $eligibleTeams->execute();
                $eligibleCount = (int) $eligibleTeams->get_result()->fetch_assoc()['eligible_count'];

                if ($eligibleCount === 2) {
                    $scheduledAtSql = $scheduledAt->format('Y-m-d H:i:s');
                    $fixture = $conn->prepare("INSERT INTO matches (tournament_id, home_team_id, away_team_id, round_name, court, scheduled_at, status) VALUES (?, ?, ?, ?, ?, ?, 'scheduled')");
                    $fixture->bind_param('iiisss', $tournamentId, $homeTeamId, $awayTeamId, $roundName, $court, $scheduledAtSql);
                    $fixture->execute();
                    $feedback = ['type' => 'success', 'message' => 'Fixture published to Match Centre.'];
                } else {
                    $feedback['message'] = 'Both teams must be approved for that tournament first.';
                }
            } else {
                $feedback['message'] = 'Complete the fixture fields and choose two different teams.';
            }
        } elseif ($action === 'update_fixture') {
            $matchId = filter_input(INPUT_POST, 'match_id', FILTER_VALIDATE_INT);
            $roundName = trim((string) ($_POST['round_name'] ?? ''));
            $court = trim((string) ($_POST['court'] ?? ''));
            $matchStatus = (string) ($_POST['match_status'] ?? '');
            $homeInput = trim((string) ($_POST['home_score'] ?? ''));
            $awayInput = trim((string) ($_POST['away_score'] ?? ''));
            $homeScore = $homeInput === '' ? null : filter_var($homeInput, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 999]]);
            $awayScore = $awayInput === '' ? null : filter_var($awayInput, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 999]]);
            $scheduledAt = adminPageDateTime((string) ($_POST['scheduled_date'] ?? ''), (string) ($_POST['scheduled_time'] ?? ''));
            $scoreRequired = in_array($matchStatus, ['live', 'final'], true);

            if (!$matchId || !$scheduledAt || $roundName === '' || strlen($roundName) > 60 || strlen($court) > 40 || !in_array($matchStatus, ['scheduled', 'live', 'final', 'postponed'], true) || ($homeInput !== '' && $homeScore === false) || ($awayInput !== '' && $awayScore === false) || ($scoreRequired && ($homeScore === null || $awayScore === null))) {
                $feedback['message'] = 'Check the fixture date, status and scores before saving.';
            } else {
                $scheduledAtSql = $scheduledAt->format('Y-m-d H:i:s');
                $fixtureUpdate = $conn->prepare('UPDATE matches SET round_name = ?, court = ?, scheduled_at = ?, home_score = ?, away_score = ?, status = ? WHERE id = ?');
                $fixtureUpdate->bind_param('sssiisi', $roundName, $court, $scheduledAtSql, $homeScore, $awayScore, $matchStatus, $matchId);
                $fixtureUpdate->execute();
                $feedback = ['type' => 'success', 'message' => $matchStatus === 'final' ? 'Final result saved and standings recalculated.' : 'Fixture controls updated.'];
            }
        } elseif ($action === 'set_seed') {
            $tournamentId = filter_input(INPUT_POST, 'tournament_id', FILTER_VALIDATE_INT);
            $teamId = filter_input(INPUT_POST, 'team_id', FILTER_VALIDATE_INT);
            $seed = filter_input(INPUT_POST, 'seed', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 99]]);

            if ($tournamentId && $teamId && $seed) {
                $seedCollision = $conn->prepare("SELECT 1 FROM tournament_entries WHERE tournament_id = ? AND seed = ? AND team_id <> ? AND status = 'confirmed' LIMIT 1");
                $seedCollision->bind_param('iii', $tournamentId, $seed, $teamId);
                $seedCollision->execute();

                if ($seedCollision->get_result()->fetch_row()) {
                    $feedback['message'] = 'That seed is already locked for this tournament.';
                } else {
                    $seedUpdate = $conn->prepare("UPDATE tournament_entries SET seed = ? WHERE tournament_id = ? AND team_id = ? AND status = 'confirmed'");
                    $seedUpdate->bind_param('iii', $seed, $tournamentId, $teamId);
                    $seedUpdate->execute();
                    $feedback = ['type' => 'success', 'message' => 'Tournament seed locked.'];
                }
            }
        } elseif ($action === 'update_user_role' && $currentUserRole === 'admin') {
            $targetUserId = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
            $newRole = (string) ($_POST['role'] ?? '');

            if ($targetUserId && $targetUserId !== $currentUserId && in_array($newRole, ['player', 'captain', 'organiser', 'admin'], true)) {
                $roleUpdate = $conn->prepare('UPDATE users SET role = ? WHERE id = ?');
                $roleUpdate->bind_param('si', $newRole, $targetUserId);
                $roleUpdate->execute();
                $feedback = ['type' => 'success', 'message' => 'Account access role updated.'];
            } else {
                $feedback['message'] = 'Choose another account and a valid access role.';
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
        (SELECT COUNT(*) FROM matches WHERE scheduled_at BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 7 DAY)) AS fixtures_this_week,
        (SELECT COUNT(*) FROM tournament_entries WHERE status = 'confirmed' AND seed BETWEEN 1 AND 4) AS dog_seeds"
)->fetch_assoc();

$dashboardMetrics = [
    ['label' => 'Total teams', 'value' => (string) $metrics['total_teams'], 'tone' => 'orange'],
    ['label' => 'Active tournaments', 'value' => (string) $metrics['active_tournaments'], 'tone' => 'yellow'],
    ['label' => 'Pending approvals', 'value' => (string) $metrics['pending_approvals'], 'tone' => 'blue'],
    ['label' => 'Fixtures this week', 'value' => (string) $metrics['fixtures_this_week'], 'tone' => 'pink'],
];

$registrationResult = $conn->query(
    "SELECT t.*, COUNT(DISTINCT CASE WHEN te.status IN ('pending', 'confirmed') THEN te.team_id END) AS entry_count
     FROM tournaments t LEFT JOIN tournament_entries te ON te.tournament_id = t.id
     GROUP BY t.id ORDER BY t.starts_at"
);
$tournamentRows = $registrationResult->fetch_all(MYSQLI_ASSOC);
$registrationBars = [];
$toneCycle = ['orange', 'yellow', 'blue'];
foreach ($tournamentRows as $index => $tournament) {
    if ($tournament['status'] === 'cancelled') {
        continue;
    }
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
     JOIN teams t ON t.id = te.team_id JOIN users captain ON captain.id = t.captain_id
     JOIN tournaments tournament ON tournament.id = te.tournament_id LEFT JOIN team_members tm ON tm.team_id = t.id
     WHERE te.status = 'pending' GROUP BY te.tournament_id, te.team_id ORDER BY te.registered_at"
)->fetch_all(MYSQLI_ASSOC);

$confirmedEntries = $conn->query(
    "SELECT te.tournament_id, te.team_id, te.seed, tournament.name AS tournament_name, team.name AS team_name
     FROM tournament_entries te JOIN tournaments tournament ON tournament.id = te.tournament_id JOIN teams team ON team.id = te.team_id
     WHERE te.status = 'confirmed' ORDER BY tournament.starts_at, COALESCE(te.seed, 999), team.name"
)->fetch_all(MYSQLI_ASSOC);

$adminMatches = $conn->query(
    "SELECT m.id, m.round_name, m.court, m.home_score, m.away_score, m.status, m.scheduled_at,
            tournament.name AS tournament_name, home.name AS home_team, away.name AS away_team
     FROM matches m JOIN tournaments tournament ON tournament.id = m.tournament_id
     JOIN teams home ON home.id = m.home_team_id JOIN teams away ON away.id = m.away_team_id
     ORDER BY FIELD(m.status, 'live', 'scheduled', 'postponed', 'final'), m.scheduled_at DESC LIMIT 20"
)->fetch_all(MYSQLI_ASSOC);

$userAccounts = [];
if ($currentUserRole === 'admin') {
    $accountQuery = $conn->prepare('SELECT id, first_name, last_name, email, role FROM users WHERE id <> ? ORDER BY FIELD(role, \'admin\', \'organiser\', \'captain\', \'player\'), first_name, last_name');
    $accountQuery->bind_param('i', $currentUserId);
    $accountQuery->execute();
    $userAccounts = $accountQuery->get_result()->fetch_all(MYSQLI_ASSOC);
}

$pageTitle = 'Tournament Dashboard';
$pageDescription = 'Blacktop Takeover organiser control deck.';
$hideNavigation = true;
$bodyClass = 'admin-control-page';
$courtMenuActive = 'organiser';
require __DIR__ . '/includes/header.php';
?>
<div class="admin-deck" data-admin-deck data-figma-node="48:424">
    <img class="admin-deck__mural" src="/blacktop-takeover/assets/images/figma/admin-control-deck-mural.svg" alt="" aria-hidden="true">
    <aside class="admin-rail">
        <img src="/blacktop-takeover/assets/images/figma/admin-organiser-rail.svg" alt="" aria-hidden="true">
        <a href="/blacktop-takeover/home.php">Blacktop<br>Takeover</a><span>011 × 012</span><strong>Run the<br>court</strong><p>Run the court<br>Own the night</p>
    </aside>

    <section class="admin-deck__content">
        <header class="admin-deck__header">
            <div><h1>Tournament dashboard</h1><p>Control deck / <?= e($currentUserRole) ?> mode</p></div>
            <span>D.O.G. finals seed tracker&nbsp; / &nbsp;<?= e(str_pad((string) min(4, (int) $metrics['dog_seeds']), 2, '0', STR_PAD_LEFT)) ?> of 04 locked</span>
            <button class="court-menu-trigger" type="button" aria-label="Open Blacktop menu" aria-controls="court-menu" aria-expanded="false" data-court-menu-trigger><img src="/blacktop-takeover/assets/images/figma/navigation-basketball-trigger.svg" alt=""></button>
        </header>

        <?php if ($feedback): ?><p class="admin-feedback admin-feedback--<?= e($feedback['type']) ?>" role="status"><?= e($feedback['message']) ?></p><?php endif; ?>

        <nav class="admin-jump-nav" aria-label="Dashboard sections"><a href="#approvals">Approvals</a><a href="#tournaments">Tournaments</a><a href="#fixtures">Fixtures</a><a href="#seeding">Seeding</a><?php if ($currentUserRole === 'admin'): ?><a href="#access">Access</a><?php endif; ?></nav>

        <section class="admin-metrics" aria-label="Tournament metrics">
            <?php foreach ($dashboardMetrics as $metric): ?><article class="admin-metric admin-metric--<?= e($metric['tone']) ?>"><span><?= e($metric['label']) ?></span><strong><?= e($metric['value']) ?></strong></article><?php endforeach; ?>
        </section>

        <section class="registration-chart" aria-labelledby="registration-chart-title">
            <h2 id="registration-chart-title">Registrations by tournament</h2>
            <div class="registration-chart__plot"><?php foreach ($registrationBars as $bar): ?><div class="registration-bar registration-bar--<?= e($bar['tone']) ?>"><span class="registration-bar__fill" style="--registration-level: <?= e((string) $bar['value']) ?>%"></span><small><?= e($bar['label']) ?></small><span class="sr-only"><?= e((string) $bar['value']) ?> percent of available registration capacity</span></div><?php endforeach; ?></div>
            <?php if ($registrationBars === [] || array_sum(array_column($registrationBars, 'value')) === 0): ?><p class="registration-chart__empty">Registration activity will build here as captains submit teams.</p><?php endif; ?>
        </section>

        <section class="approval-queue" id="approvals" aria-labelledby="approval-queue-title">
            <h2 id="approval-queue-title">Pending team approvals</h2>
            <div class="approval-queue__list">
                <?php foreach ($pendingTeams as $team): ?>
                    <button type="button" data-approval-review data-team-name="<?= e($team['name']) ?>" data-team-event="<?= e($team['event']) ?>" data-team-captain="<?= e($team['captain']) ?>" data-team-roster="<?= e($team['roster_count'] . ' / ' . $team['max_roster']) ?>" data-tournament-id="<?= e((string) $team['tournament_id']) ?>" data-team-id="<?= e((string) $team['team_id']) ?>"><span><strong><?= e($team['name']) ?></strong> — <?= e($team['event']) ?></span><b>Pending</b></button>
                <?php endforeach; ?>
                <?php if ($pendingTeams === []): ?><p class="admin-empty-state">No team applications are waiting for review.</p><?php endif; ?>
            </div>
        </section>

        <section class="admin-management-grid" id="tournaments" aria-label="Tournament management">
            <article class="admin-operation-card admin-operation-card--tournament">
                <h2>Publish tournament</h2><p>Create an event from the control deck. Draft events stay hidden until opened.</p>
                <form method="post" action="/blacktop-takeover/admin.php">
                    <input type="hidden" name="csrf_token" value="<?= e($_SESSION['admin_csrf']) ?>"><input type="hidden" name="action" value="create_tournament">
                    <div class="admin-operation-card__split"><label>Event name<input name="name" maxlength="140" required></label><label>Eyebrow<input name="eyebrow" maxlength="100" placeholder="COJ / Regional qualifier" required></label></div>
                    <div class="admin-operation-card__split"><label>Qualification route<input name="route_label" maxlength="100" placeholder="The road to KON" required></label><label>Venue<input name="venue" maxlength="140" required></label></div>
                    <div class="admin-operation-card__split"><label>City<input name="city" maxlength="80" required></label><label>Format<select name="format"><option value="5v5">5V5</option><option value="3v3">3V3</option></select></label></div>
                    <div class="admin-operation-card__quad"><label>Start date<input type="date" name="starts_date" required></label><label>Tip-off<input type="time" name="starts_time" step="300" required></label><label>Deadline<input type="date" name="deadline_date" required></label><label>Close time<input type="time" name="deadline_time" step="300" required></label></div>
                    <div class="admin-operation-card__quad"><label>Capacity<input type="number" name="capacity" min="2" max="128" value="16" required></label><label>Max roster<input type="number" name="max_roster" min="3" max="20" value="8" required></label><label>Entry fee (R)<input type="number" name="entry_fee" min="0" step="0.01" value="0" required></label><label>Prize (R)<input type="number" name="prize" min="0" step="0.01" value="0" required></label></div>
                    <label>Description<textarea name="description" maxlength="1200" rows="3"></textarea></label>
                    <label>Publish state<select name="status"><option value="draft">Draft</option><option value="open">Open registration</option></select></label>
                    <button type="submit">Create tournament</button>
                </form>
            </article>

            <article class="admin-operation-card admin-operation-card--season">
                <h2>Season controls</h2><p>Move event dates, capacity and public status without editing MySQL.</p>
                <div class="admin-season-list">
                    <?php foreach ($tournamentRows as $tournament): ?>
                        <?php $starts = new DateTimeImmutable($tournament['starts_at']); $deadline = new DateTimeImmutable($tournament['registration_deadline']); ?>
                        <details>
                            <summary><span><strong><?= e($tournament['name']) ?></strong><small><?= e($starts->format('d M Y · H:i')) ?></small></span><b><?= e(str_replace('_', ' ', $tournament['status'])) ?></b></summary>
                            <form method="post" action="/blacktop-takeover/admin.php">
                                <input type="hidden" name="csrf_token" value="<?= e($_SESSION['admin_csrf']) ?>"><input type="hidden" name="action" value="update_tournament"><input type="hidden" name="tournament_id" value="<?= e((string) $tournament['id']) ?>">
                                <label>Event name<input name="name" maxlength="140" value="<?= e($tournament['name']) ?>" required></label>
                                <div class="admin-operation-card__split"><label>City<input name="city" maxlength="80" value="<?= e($tournament['city']) ?>" required></label><label>Venue<input name="venue" maxlength="140" value="<?= e($tournament['venue']) ?>" required></label></div>
                                <div class="admin-operation-card__quad"><label>Start date<input type="date" name="starts_date" value="<?= e($starts->format('Y-m-d')) ?>" required></label><label>Tip-off<input type="time" name="starts_time" value="<?= e($starts->format('H:i')) ?>" required></label><label>Deadline<input type="date" name="deadline_date" value="<?= e($deadline->format('Y-m-d')) ?>" required></label><label>Close time<input type="time" name="deadline_time" value="<?= e($deadline->format('H:i')) ?>" required></label></div>
                                <div class="admin-operation-card__quad"><label>Capacity<input type="number" name="capacity" min="2" max="128" value="<?= e((string) $tournament['capacity']) ?>" required></label><label>Max roster<input type="number" name="max_roster" min="3" max="20" value="<?= e((string) $tournament['max_roster']) ?>" required></label><label>Format<select name="format"><option value="5v5"<?= $tournament['format'] === '5v5' ? ' selected' : '' ?>>5V5</option><option value="3v3"<?= $tournament['format'] === '3v3' ? ' selected' : '' ?>>3V3</option></select></label><label>Status<select name="status"><?php foreach (['draft', 'open', 'full', 'in_progress', 'completed', 'cancelled'] as $status): ?><option value="<?= e($status) ?>"<?= $tournament['status'] === $status ? ' selected' : '' ?>><?= e(str_replace('_', ' ', ucfirst($status))) ?></option><?php endforeach; ?></select></label></div>
                                <button type="submit">Save event changes</button>
                            </form>
                        </details>
                    <?php endforeach; ?>
                </div>
            </article>
        </section>

        <section class="admin-operations" id="fixtures" aria-label="Fixture operations">
            <article class="admin-operation-card">
                <h2>Create fixture</h2><p>Only approved teams can be placed into a tournament fixture.</p>
                <form method="post" action="/blacktop-takeover/admin.php">
                    <input type="hidden" name="csrf_token" value="<?= e($_SESSION['admin_csrf']) ?>"><input type="hidden" name="action" value="create_fixture">
                    <label>Tournament<select name="tournament_id" required data-fixture-tournament><option value="">Choose tournament</option><?php foreach ($tournamentRows as $tournament): ?><?php if ($tournament['status'] !== 'cancelled'): ?><option value="<?= e((string) $tournament['id']) ?>"><?= e($tournament['name']) ?></option><?php endif; ?><?php endforeach; ?></select></label>
                    <div class="admin-operation-card__split"><label>Home team<select name="home_team_id" required data-fixture-home-team><option value="">Choose approved team</option><?php foreach ($confirmedEntries as $entry): ?><option value="<?= e((string) $entry['team_id']) ?>" data-tournament-id="<?= e((string) $entry['tournament_id']) ?>"><?= e($entry['team_name']) ?></option><?php endforeach; ?></select></label><label>Away team<select name="away_team_id" required data-fixture-away-team><option value="">Choose approved team</option><?php foreach ($confirmedEntries as $entry): ?><option value="<?= e((string) $entry['team_id']) ?>" data-tournament-id="<?= e((string) $entry['tournament_id']) ?>"><?= e($entry['team_name']) ?></option><?php endforeach; ?></select></label></div>
                    <div class="admin-operation-card__split"><label>Round<input name="round_name" maxlength="60" placeholder="Group A / R1" required></label><label>Court<input name="court" maxlength="40" placeholder="Court 1"></label></div>
                    <div class="admin-operation-card__split"><div class="admin-picker-field"><label for="fixture-date">Fixture date</label><span class="admin-picker-control"><input id="fixture-date" type="date" name="scheduled_date" required data-fixture-date><button type="button" aria-label="Open fixture calendar" aria-controls="fixture-date" data-fixture-date-open>Calendar</button></span></div><div class="admin-picker-field"><label for="fixture-time">Tip-off time</label><span class="admin-picker-control"><input id="fixture-time" type="time" name="scheduled_time" step="300" required data-fixture-time><button type="button" aria-label="Open tip-off time picker" aria-controls="fixture-time" data-fixture-time-open>Time</button></span></div></div>
                    <p class="admin-operation-warning" data-fixture-warning>Select a tournament to load its approved teams.</p><button type="submit" disabled data-fixture-submit>Publish fixture</button>
                </form>
            </article>

            <article class="admin-operation-card admin-operation-card--fixture-desk">
                <h2>Fixture control</h2><p>Reschedule, postpone, correct scores or reopen a published result.</p>
                <div class="admin-fixture-list">
                    <?php foreach ($adminMatches as $match): ?>
                        <?php $scheduled = new DateTimeImmutable($match['scheduled_at']); ?>
                        <details<?= $match['status'] === 'live' ? ' open' : '' ?>>
                            <summary><span><strong><?= e($match['home_team']) ?> <b>vs</b> <?= e($match['away_team']) ?></strong><small><?= e($match['tournament_name']) ?> · <?= e($scheduled->format('d M H:i')) ?></small></span><em><?= e($match['status']) ?></em></summary>
                            <form method="post" action="/blacktop-takeover/admin.php">
                                <input type="hidden" name="csrf_token" value="<?= e($_SESSION['admin_csrf']) ?>"><input type="hidden" name="action" value="update_fixture"><input type="hidden" name="match_id" value="<?= e((string) $match['id']) ?>">
                                <div class="admin-operation-card__split"><label>Round<input name="round_name" maxlength="60" value="<?= e($match['round_name']) ?>" required></label><label>Court<input name="court" maxlength="40" value="<?= e((string) $match['court']) ?>"></label></div>
                                <div class="admin-operation-card__quad"><label>Date<input type="date" name="scheduled_date" value="<?= e($scheduled->format('Y-m-d')) ?>" required></label><label>Time<input type="time" name="scheduled_time" value="<?= e($scheduled->format('H:i')) ?>" required></label><label><?= e($match['home_team']) ?> score<input type="number" name="home_score" min="0" max="999" value="<?= e((string) ($match['home_score'] ?? '')) ?>"></label><label><?= e($match['away_team']) ?> score<input type="number" name="away_score" min="0" max="999" value="<?= e((string) ($match['away_score'] ?? '')) ?>"></label></div>
                                <label>Match state<select name="match_status"><?php foreach (['scheduled', 'live', 'final', 'postponed'] as $status): ?><option value="<?= e($status) ?>"<?= $match['status'] === $status ? ' selected' : '' ?>><?= e(ucfirst($status)) ?></option><?php endforeach; ?></select></label>
                                <button type="submit">Save fixture</button>
                            </form>
                        </details>
                    <?php endforeach; ?>
                    <?php if ($adminMatches === []): ?><p class="admin-empty-state">No fixtures have been created yet.</p><?php endif; ?>
                </div>
            </article>
        </section>

        <section class="admin-management-grid admin-management-grid--lower" id="seeding">
            <article class="admin-operation-card admin-operation-card--seed"><h2>D.O.G. seed desk</h2><p>Lock confirmed teams into their tournament order. Seeds one to four update the finals tracker.</p><form method="post" action="/blacktop-takeover/admin.php"><input type="hidden" name="csrf_token" value="<?= e($_SESSION['admin_csrf']) ?>"><input type="hidden" name="action" value="set_seed"><label>Confirmed entry<select name="tournament_id" required data-seed-tournament><option value="">Choose tournament</option><?php foreach ($tournamentRows as $tournament): ?><option value="<?= e((string) $tournament['id']) ?>"><?= e($tournament['name']) ?></option><?php endforeach; ?></select></label><label>Team<select name="team_id" required data-seed-team><option value="">Choose confirmed team</option><?php foreach ($confirmedEntries as $entry): ?><option value="<?= e((string) $entry['team_id']) ?>" data-tournament-id="<?= e((string) $entry['tournament_id']) ?>" data-current-seed="<?= e((string) ($entry['seed'] ?? '')) ?>"><?= e($entry['team_name']) ?><?= $entry['seed'] ? ' · seed ' . e((string) $entry['seed']) : '' ?></option><?php endforeach; ?></select></label><label>Seed<input type="number" name="seed" min="1" max="99" required></label><button type="submit">Lock seed</button></form></article>

            <?php if ($currentUserRole === 'admin'): ?>
                <article class="admin-operation-card admin-operation-card--access" id="access"><h2>Admin access control</h2><p>Promote organisers or correct account roles. This control is hidden from organisers.</p><form method="post" action="/blacktop-takeover/admin.php"><input type="hidden" name="csrf_token" value="<?= e($_SESSION['admin_csrf']) ?>"><input type="hidden" name="action" value="update_user_role"><label>Account<select name="user_id" required><option value="">Choose account</option><?php foreach ($userAccounts as $account): ?><option value="<?= e((string) $account['id']) ?>"><?= e(trim($account['first_name'] . ' ' . $account['last_name'])) ?> · <?= e($account['email']) ?> · <?= e($account['role']) ?></option><?php endforeach; ?></select></label><label>Access role<select name="role"><option value="player">Player</option><option value="captain">Captain</option><option value="organiser">Organiser</option><option value="admin">Admin</option></select></label><p class="admin-operation-warning">Changing access does not create or transfer a squad. Captain team ownership stays a separate record.</p><button type="submit">Update access</button></form></article>
            <?php else: ?>
                <article class="admin-operation-card admin-operation-card--access"><h2>Organiser access</h2><p>You can run tournaments, applications, fixtures, scores and seeding. Account role changes remain admin-only.</p><strong class="admin-access-badge">Court operations enabled</strong></article>
            <?php endif; ?>
        </section>
    </section>
</div>

<dialog class="approval-dialog" data-approval-dialog aria-labelledby="approval-dialog-title">
    <div class="approval-dialog__heading"><div><span>Application review</span><h2 id="approval-dialog-title" data-approval-team>Team application</h2></div><button type="button" aria-label="Close application review" data-approval-close>&times;</button></div>
    <dl><div><dt>Tournament</dt><dd data-approval-event></dd></div><div><dt>Captain</dt><dd data-approval-captain></dd></div><div><dt>Roster</dt><dd data-approval-roster></dd></div><div><dt>Status</dt><dd><strong>Pending</strong></dd></div></dl>
    <form class="approval-dialog__actions" method="post" action="/blacktop-takeover/admin.php"><input type="hidden" name="csrf_token" value="<?= e($_SESSION['admin_csrf']) ?>"><input type="hidden" name="action" value="review_entry"><input type="hidden" name="tournament_id" value="" data-approval-tournament-id><input type="hidden" name="team_id" value="" data-approval-team-id><button type="submit" name="decision" value="approve">Approve team</button><button type="submit" name="decision" value="decline">Decline</button></form>
</dialog>

<?php require __DIR__ . '/includes/court-menu.php'; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
