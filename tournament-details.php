<?php
session_start();

require_once __DIR__ . '/config/connection.php';
require_once __DIR__ . '/includes/competition.php';

$eventSlug = trim((string) ($_POST['event'] ?? $_GET['event'] ?? 'coj-summer-showdown'));
$eventQuery = $conn->prepare(
    "SELECT t.*,
            COUNT(DISTINCT CASE WHEN te.status IN ('pending', 'confirmed') THEN te.team_id END) AS entry_count
     FROM tournaments t
     LEFT JOIN tournament_entries te ON te.tournament_id = t.id
     WHERE t.slug = ?
     GROUP BY t.id
     LIMIT 1"
);
$eventQuery->bind_param('s', $eventSlug);
$eventQuery->execute();
$event = $eventQuery->get_result()->fetch_assoc();

if (!$event) {
    http_response_code(404);
    $pageTitle = 'Tournament not found';
    $hideNavigation = true;
    require __DIR__ . '/includes/header.php';
    ?>
    <section class="blank-state"><h1>Tournament not found</h1><a href="/blacktop-takeover/tournaments.php">Return to tournaments</a></section>
    <?php
    require __DIR__ . '/includes/footer.php';
    exit;
}

$userId = (int) ($_SESSION['user_id'] ?? 0);
$userRole = $_SESSION['user_role'] ?? null;
$eventId = (int) $event['id'];
$applicationTeam = null;

if ($userId > 0 && in_array($userRole, ['player', 'captain'], true)) {
    $teamQuery = $conn->prepare(
        "SELECT t.id, t.name, t.captain_id,
                COUNT(DISTINCT CASE WHEN tm.status = 'active' THEN tm.user_id END) AS roster_count,
                MAX(te.status) AS entry_status,
                MAX(t.captain_id = ?) AS is_captain,
                MAX(viewer.squad_role = 'vice_captain' AND viewer.status = 'active') AS is_vice_captain
         FROM teams t
         LEFT JOIN team_members viewer ON viewer.team_id = t.id AND viewer.user_id = ?
         LEFT JOIN team_members tm ON tm.team_id = t.id
         LEFT JOIN tournament_entries te ON te.team_id = t.id AND te.tournament_id = ?
         WHERE t.captain_id = ?
            OR (viewer.squad_role = 'vice_captain' AND viewer.status = 'active')
         GROUP BY t.id
         ORDER BY (t.captain_id = ?) DESC
         LIMIT 1"
    );
    $teamQuery->bind_param('iiiii', $userId, $userId, $eventId, $userId, $userId);
    $teamQuery->execute();
    $applicationTeam = $teamQuery->get_result()->fetch_assoc();
}

if (empty($_SESSION['tournament_csrf'])) {
    $_SESSION['tournament_csrf'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $feedback = ['type' => 'error', 'message' => 'Only a team captain or active vice captain can submit an application.'];
    $submittedToken = (string) ($_POST['csrf_token'] ?? '');

    if (!hash_equals($_SESSION['tournament_csrf'], $submittedToken)) {
        $feedback = ['type' => 'error', 'message' => 'Your session expired. Please try again.'];
    } elseif (!$applicationTeam) {
        $feedback = ['type' => 'error', 'message' => 'You need captain or active vice-captain access to apply.'];
    } elseif (in_array($applicationTeam['entry_status'], ['pending', 'confirmed'], true)) {
        $feedback = ['type' => 'success', 'message' => 'Your team already has an application for this event.'];
    } elseif ($event['status'] !== 'open') {
        $feedback = ['type' => 'error', 'message' => 'Registration is not open for this event.'];
    } elseif ((int) $event['entry_count'] >= (int) $event['capacity']) {
        $feedback = ['type' => 'error', 'message' => 'This tournament has reached capacity.'];
    } else {
        $teamId = (int) $applicationTeam['id'];
        $eligibility = competitionTeamEligibility($conn, $eventId, $teamId);

        if (!$eligibility || !$eligibility['eligible']) {
            $feedback = ['type' => 'error', 'message' => $eligibility['reason'] ?? 'The squad is not eligible for this tournament.'];
        } else {
            if ($applicationTeam['entry_status'] === 'withdrawn') {
                $entryUpdate = $conn->prepare("UPDATE tournament_entries SET status = 'pending', registered_at = CURRENT_TIMESTAMP WHERE tournament_id = ? AND team_id = ?");
                $entryUpdate->bind_param('ii', $eventId, $teamId);
                $entryUpdate->execute();
            } else {
                $entryInsert = $conn->prepare("INSERT INTO tournament_entries (tournament_id, team_id, status) VALUES (?, ?, 'pending')");
                $entryInsert->bind_param('ii', $eventId, $teamId);
                $entryInsert->execute();
            }
            $feedback = ['type' => 'success', 'message' => $applicationTeam['name'] . ' is queued for organiser review.'];
        }
    }

    $_SESSION['tournament_feedback'] = $feedback;
    header('Location: /blacktop-takeover/tournament-details.php?event=' . rawurlencode($eventSlug));
    exit;
}

$feedback = $_SESSION['tournament_feedback'] ?? null;
unset($_SESSION['tournament_feedback']);

$startsAt = new DateTimeImmutable($event['starts_at']);
$spotsLeft = max(0, (int) $event['capacity'] - (int) $event['entry_count']);
$fee = 'R' . number_format(((int) $event['entry_fee_cents']) / 100, 0);
$applicationSent = $applicationTeam && in_array($applicationTeam['entry_status'], ['pending', 'confirmed'], true);
$rosterCount = (int) ($applicationTeam['roster_count'] ?? 0);
$rosterMaximum = max(1, (int) $event['max_roster']);
$rosterMinimum = competitionMinimumRoster($event['format']);
$rosterProgress = min($rosterCount, $rosterMaximum);
$rosterStatus = $rosterCount >= $rosterMaximum
    ? 'complete'
    : ($rosterCount >= $rosterMinimum ? 'entry minimum met' : 'minimum ' . $rosterMinimum . ' required');
$routeGateCopy = match ($event['slug']) {
    'coj-summer-showdown' => 'KOS is the gate. D.O.G. is the throne.',
    'cop-regional-qualifier' => 'KON is the gate. D.O.G. is the throne.',
    default => 'KON + KOS are the gates. D.O.G. is the throne.',
};

$pageTitle = $event['name'];
$pageDescription = 'Tournament information and team application for ' . $event['name'] . '.';
$hideNavigation = true;
$bodyClass = 'tournament-detail-page';
$courtMenuActive = 'tournaments';
$matchCentreEvent = $eventSlug;

require __DIR__ . '/includes/header.php';
?>
<article class="event-detail" data-figma-node="48:216">
    <img class="event-detail__mural" src="/blacktop-takeover/assets/images/figma/tournament-details-mural.svg" alt="" aria-hidden="true">
    <div class="event-detail__street-tags" aria-hidden="true">
        <span class="event-detail__street-tag event-detail__street-tag--jozi">Jozi 011</span>
        <span class="event-detail__street-tag event-detail__street-tag--egoli">011 / Egoli</span>
        <span class="event-detail__street-tag event-detail__street-tag--block">Own the block</span>
    </div>

    <header class="screen-header">
        <a class="screen-wordmark" href="/blacktop-takeover/home.php">Blacktop Takeover</a>
        <button class="court-menu-trigger" type="button" aria-label="Open Blacktop menu" aria-controls="court-menu" aria-expanded="false" data-court-menu-trigger>
            <img src="/blacktop-takeover/assets/images/figma/navigation-basketball-trigger.svg" alt="">
        </button>
    </header>

    <section class="event-hero">
        <div class="event-hero__copy">
            <p class="event-eyebrow"><?= e($event['eyebrow']) ?></p>
            <h1><?= e($event['name']) ?></h1>
            <p class="event-meta"><?= e($startsAt->format('d M Y')) ?> <span>&middot;</span> <?= e($startsAt->format('H:i')) ?> <span>&middot;</span> <?= e($event['venue']) ?></p>
            <p class="event-summary"><?= e($event['description']) ?></p>
        </div>
        <div class="event-hero__road">
            <img src="/blacktop-takeover/assets/images/figma/champion-feather-crown.svg" alt="" aria-hidden="true">
            <strong><?= e($event['route_label']) ?></strong>
            <span><?= e($routeGateCopy) ?></span>
            <small>Duke of Gauteng &middot; franchise final</small>
        </div>
    </section>

    <div class="event-detail__body">
        <div class="event-detail__main">
            <section class="event-facts" aria-label="Tournament facts">
                <div><strong><?= e(str_pad((string) $spotsLeft, 2, '0', STR_PAD_LEFT)) ?></strong><span>Spots left</span></div>
                <div><strong><?= e($fee) ?></strong><span>Team fee</span></div>
                <div><strong><?= e(strtoupper($event['format'])) ?></strong><span>Format</span></div>
                <div><strong><?= e((string) $event['max_roster']) ?></strong><span>Max roster</span></div>
            </section>

            <section class="event-information">
                <h2>Event information</h2>
                <dl>
                    <div><dt>Venue</dt><dd><?= e($event['venue'] . ', ' . $event['city']) ?></dd></div>
                    <div><dt>Check-in</dt><dd><?= e($event['check_in_notes']) ?></dd></div>
                    <div><dt>Format</dt><dd><?= e($event['structure_notes']) ?></dd></div>
                    <div><dt>Prize</dt><dd><?= e($event['prize_notes']) ?></dd></div>
                </dl>
            </section>
        </div>

        <aside class="event-application" aria-labelledby="application-title">
            <h2 id="application-title">Team application</h2>
            <p>Applications are reviewed by a Blacktop organiser.</p>

            <?php if ($feedback): ?>
                <div class="application-confirmation application-confirmation--<?= e($feedback['type']) ?>" role="status">
                    <strong><?= $feedback['type'] === 'success' ? 'Application update.' : 'Application blocked.' ?></strong>
                    <span><?= e($feedback['message']) ?></span>
                </div>
            <?php endif; ?>

            <?php if ($applicationTeam): ?>
                <form method="post" action="/blacktop-takeover/tournament-details.php?event=<?= e($eventSlug) ?>">
                    <input type="hidden" name="csrf_token" value="<?= e($_SESSION['tournament_csrf']) ?>">
                    <input type="hidden" name="event" value="<?= e($eventSlug) ?>">
                    <label for="application-team">Select team</label>
                    <select id="application-team" name="team" required>
                        <option value="<?= e((string) $applicationTeam['id']) ?>"><?= e($applicationTeam['name']) ?> &middot; <?= e((string) $rosterCount) ?> players</option>
                    </select>

                    <span class="application-label">Roster status</span>
                    <progress
                        value="<?= e((string) $rosterProgress) ?>"
                        max="<?= e((string) $rosterMaximum) ?>"
                        aria-label="<?= e((string) $rosterCount) ?> of <?= e((string) $rosterMaximum) ?> roster places filled"
                    ></progress>
                    <strong class="application-roster"><?= e((string) $rosterCount) ?> / <?= e((string) $rosterMaximum) ?> players &middot; <?= e($rosterStatus) ?></strong>

                    <button type="submit"<?= $applicationSent ? ' disabled' : '' ?>>
                        <?php if ($applicationSent): ?>
                            <?= e(ucfirst($applicationTeam['entry_status']) . ' application') ?>
                        <?php elseif ($applicationTeam['entry_status'] === 'withdrawn'): ?>
                            Resubmit team application
                        <?php else: ?>
                            Submit team application
                        <?php endif; ?>
                    </button>
                </form>
            <?php elseif (in_array($userRole, ['player', 'captain'], true)): ?>
                <p class="application-access-copy">You need a team as its captain or active vice captain before it can enter.</p>
                <a class="application-access-link" href="/blacktop-takeover/team.php">Open my squad</a>
            <?php elseif ($userRole !== null): ?>
                <p class="application-access-copy">Only a team captain or active vice captain can submit the squad application.</p>
            <?php else: ?>
                <p class="application-access-copy">Sign in with team leadership access to submit a team.</p>
                <a class="application-access-link" href="/blacktop-takeover/login.php">Team sign in</a>
            <?php endif; ?>
        </aside>
    </div>
</article>

<?php require __DIR__ . '/includes/court-menu.php'; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
