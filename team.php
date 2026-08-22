<?php
session_start();

require_once __DIR__ . '/config/connection.php';

if (!in_array($_SESSION['user_role'] ?? null, ['player', 'captain'], true)) {
    header('Location: /blacktop-takeover/login.php');
    exit;
}

$userId = (int) $_SESSION['user_id'];
$userRole = (string) $_SESSION['user_role'];
$userName = trim((string) ($_SESSION['user_name'] ?? 'Blacktop player'));
$feedback = $_SESSION['team_feedback'] ?? null;
unset($_SESSION['team_feedback']);

if (empty($_SESSION['team_csrf'])) {
    $_SESSION['team_csrf'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postedToken = (string) ($_POST['csrf_token'] ?? '');

    if (!hash_equals($_SESSION['team_csrf'], $postedToken)) {
        $_SESSION['team_feedback'] = ['type' => 'error', 'message' => 'Your session expired. Please try again.'];
        header('Location: /blacktop-takeover/team.php');
        exit;
    }

    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'update_team' && $userRole === 'captain') {
        $teamName = trim((string) ($_POST['team_name'] ?? ''));
        $teamCity = trim((string) ($_POST['team_city'] ?? ''));

        if ($teamName === '' || $teamCity === '' || mb_strlen($teamName) > 100 || mb_strlen($teamCity) > 80) {
            $_SESSION['team_feedback'] = ['type' => 'error', 'message' => 'Enter a valid team name and home city.'];
        } else {
            try {
                $updateTeam = $conn->prepare('UPDATE teams SET name = ?, city = ? WHERE captain_id = ?');
                $updateTeam->bind_param('ssi', $teamName, $teamCity, $userId);
                $updateTeam->execute();
                $_SESSION['team_feedback'] = $updateTeam->affected_rows > 0
                    ? ['type' => 'success', 'message' => 'Team details saved.']
                    : ['type' => 'success', 'message' => 'Team details are already up to date.'];
            } catch (mysqli_sql_exception $exception) {
                $_SESSION['team_feedback'] = ['type' => 'error', 'message' => 'That team name is already in use.'];
            }
        }
    } elseif ($action === 'join_team' && $userRole === 'player') {
        $inviteCode = strtoupper(trim((string) ($_POST['invite_code'] ?? '')));

        $currentTeam = $conn->prepare("SELECT 1 FROM team_members WHERE user_id = ? AND status IN ('invited', 'active') LIMIT 1");
        $currentTeam->bind_param('i', $userId);
        $currentTeam->execute();

        if ($currentTeam->get_result()->fetch_row()) {
            $_SESSION['team_feedback'] = ['type' => 'error', 'message' => 'You already belong to a squad.'];
        } elseif ($inviteCode === '') {
            $_SESSION['team_feedback'] = ['type' => 'error', 'message' => 'Enter the invite code from your captain.'];
        } else {
            $findTeam = $conn->prepare('SELECT id FROM teams WHERE invite_code = ? LIMIT 1');
            $findTeam->bind_param('s', $inviteCode);
            $findTeam->execute();
            $invitedTeam = $findTeam->get_result()->fetch_assoc();

            if (!$invitedTeam) {
                $_SESSION['team_feedback'] = ['type' => 'error', 'message' => 'That invite code does not match a squad.'];
            } else {
                $teamId = (int) $invitedTeam['id'];
                $joinTeam = $conn->prepare("INSERT INTO team_members (team_id, user_id, status, joined_at) VALUES (?, ?, 'active', NOW())");
                $joinTeam->bind_param('ii', $teamId, $userId);
                $joinTeam->execute();
                $_SESSION['team_feedback'] = ['type' => 'success', 'message' => 'You joined the squad.'];
            }
        }
    }

    header('Location: /blacktop-takeover/team.php');
    exit;
}

$teamQuery = $conn->prepare(
    "SELECT t.id, t.name, t.city, t.captain_id, t.invite_code,
            captain.first_name AS captain_first_name, captain.last_name AS captain_last_name
     FROM teams t
     JOIN users captain ON captain.id = t.captain_id
     LEFT JOIN team_members membership ON membership.team_id = t.id
     WHERE t.captain_id = ?
        OR (membership.user_id = ? AND membership.status IN ('invited', 'active'))
     ORDER BY (t.captain_id = ?) DESC
     LIMIT 1"
);
$teamQuery->bind_param('iii', $userId, $userId, $userId);
$teamQuery->execute();
$team = $teamQuery->get_result()->fetch_assoc();

$roster = [];
if ($team) {
    $teamId = (int) $team['id'];
    $captainId = (int) $team['captain_id'];
    $rosterQuery = $conn->prepare(
        "SELECT u.id, u.first_name, u.last_name, u.role, tm.jersey_number, tm.position, tm.status
         FROM team_members tm
         JOIN users u ON u.id = tm.user_id
         WHERE tm.team_id = ? AND tm.status <> 'inactive'
         ORDER BY (u.id = ?) DESC, tm.status = 'active' DESC, u.first_name, u.last_name"
    );
    $rosterQuery->bind_param('ii', $teamId, $captainId);
    $rosterQuery->execute();
    $roster = $rosterQuery->get_result()->fetch_all(MYSQLI_ASSOC);
}

$teamName = $team['name'] ?? 'No squad yet';
$teamCity = $team['city'] ?? 'Join a team to unlock your roster';
$captainName = $team
    ? trim($team['captain_first_name'] . ' ' . $team['captain_last_name'])
    : 'Not assigned';
$rosterCount = count($roster);
$rosterReady = $rosterCount >= 3;

$pageTitle = 'My Squad';
$pageDescription = 'Manage your squad roster and Blacktop tournament applications.';
$hideNavigation = true;
$bodyClass = 'team-roster-page';
$courtMenuActive = 'team';

require __DIR__ . '/includes/header.php';
?>
<div class="squad-screen">
    <img class="squad-screen__mural" src="/blacktop-takeover/assets/images/figma/team-roster-mural.svg" alt="" aria-hidden="true">

    <aside class="squad-rail">
        <img src="/blacktop-takeover/assets/images/figma/captain-rail-mural.svg" alt="" aria-hidden="true">
        <a class="squad-rail__brand" href="/blacktop-takeover/home.php">Blacktop<br>Takeover</a>
        <span class="squad-rail__tag" aria-hidden="true">011</span>
        <span class="squad-rail__captain" aria-hidden="true">Captain</span>
        <div class="squad-rail__account">
            <strong><?= e(ucfirst($userRole)) ?> account</strong>
            <span><?= e($userName) ?></span>
        </div>
    </aside>

    <section class="squad-content">
        <header class="squad-heading">
            <div>
                <h1>My squad</h1>
                <p>Manage eligibility, positions and tournament applications.</p>
            </div>
            <?php if ($team && (int) $team['captain_id'] === $userId): ?>
                <button class="squad-edit-button" type="button" data-team-dialog-open>Edit team details</button>
            <?php endif; ?>
            <button class="court-menu-trigger" type="button" aria-label="Open Blacktop menu" aria-controls="court-menu" aria-expanded="false" data-court-menu-trigger>
                <img src="/blacktop-takeover/assets/images/figma/navigation-basketball-trigger.svg" alt="">
            </button>
        </header>

        <?php if ($feedback): ?>
            <p class="team-update-notice team-update-notice--<?= e($feedback['type']) ?>" role="status"><?= e($feedback['message']) ?></p>
        <?php endif; ?>

        <section class="team-banner" aria-labelledby="team-name">
            <div>
                <h2 id="team-name"><?= e($teamName) ?></h2>
                <p><?= e($teamCity) ?> <span>&middot;</span> Captain: <?= e($captainName) ?></p>
                <?php if ($team && (int) $team['captain_id'] === $userId): ?>
                    <small>Invite code: <strong><?= e($team['invite_code'] ?: 'Not set') ?></strong></small>
                <?php endif; ?>
            </div>
            <div class="team-banner__status">
                <strong><?= e((string) $rosterCount) ?> / 8</strong>
                <span><?= $rosterReady ? 'Entry minimum met' : 'Building roster' ?></span>
                <small><?= $team ? 'Road to D.O.G. &middot; stage 01 / 03' : 'Awaiting squad access' ?></small>
            </div>
            <b><?= $rosterReady ? 'Ready' : 'Recruiting' ?></b>
        </section>

        <div class="squad-layout">
            <section class="roster-panel" id="roster">
                <h2>Active roster</h2>
                <p>All players must be verified before the organiser approves an application.</p>
                <?php if ($team): ?>
                    <div class="roster-table-wrap">
                        <table class="roster-table">
                            <thead>
                                <tr><th>No.</th><th>Player</th><th>Role</th><th>Status</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach ($roster as $player): ?>
                                    <?php
                                    $playerName = trim($player['first_name'] . ' ' . $player['last_name']);
                                    $playerRole = (int) $player['id'] === (int) $team['captain_id']
                                        ? 'Captain'
                                        : ($player['position'] ?: 'Player');
                                    ?>
                                    <tr>
                                        <td><?= e($player['jersey_number'] !== null ? str_pad((string) $player['jersey_number'], 2, '0', STR_PAD_LEFT) : '--') ?></td>
                                        <td><?= e($playerName) ?></td>
                                        <td><?= e($playerRole) ?></td>
                                        <td class="roster-status roster-status--<?= e(strtolower($player['status'])) ?>"><?= e(ucfirst($player['status'])) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php elseif ($userRole === 'player'): ?>
                    <form class="squad-join-form" method="post" action="/blacktop-takeover/team.php">
                        <input type="hidden" name="csrf_token" value="<?= e($_SESSION['team_csrf']) ?>">
                        <input type="hidden" name="action" value="join_team">
                        <label for="squad-invite-code">Captain's invite code</label>
                        <div>
                            <input id="squad-invite-code" name="invite_code" maxlength="40" autocomplete="off" required>
                            <button type="submit">Join squad</button>
                        </div>
                    </form>
                <?php else: ?>
                    <p class="squad-empty-copy">Your captain account has no team attached. Create a fresh captain account or check the team record in MySQL.</p>
                <?php endif; ?>
            </section>

            <aside class="squad-cards">
                <section class="fixture-card">
                    <h2>Next fixture</h2>
                    <p>COJ &middot; Group A</p>
                    <strong><?= e($team ? $teamName : 'TBA') ?></strong>
                    <b>VS</b>
                    <strong>Soweto Stars</strong>
                    <time datetime="2026-08-14T12:30">14 Aug &middot; 12:30</time>
                </section>

                <section class="entry-ready-card">
                    <h2>Ready to enter?</h2>
                    <p><?= $rosterReady ? 'Your roster meets the minimum requirements.' : 'Build a roster of at least three players first.' ?></p>
                    <?php if ($rosterReady): ?>
                        <a href="/blacktop-takeover/tournament-details.php?event=coj-summer-showdown">Apply to event</a>
                    <?php endif; ?>
                </section>
            </aside>
        </div>
    </section>
</div>

<?php if ($team && (int) $team['captain_id'] === $userId): ?>
<dialog class="team-dialog" data-team-dialog>
    <form method="post" action="/blacktop-takeover/team.php">
        <input type="hidden" name="csrf_token" value="<?= e($_SESSION['team_csrf']) ?>">
        <input type="hidden" name="action" value="update_team">
        <div class="team-dialog__heading">
            <h2>Edit team details</h2>
            <button type="button" aria-label="Close team editor" data-team-dialog-close>&times;</button>
        </div>
        <label for="team-name-input">Team name</label>
        <input id="team-name-input" name="team_name" value="<?= e($teamName) ?>" maxlength="100" required>
        <label for="team-city-input">Home city</label>
        <input id="team-city-input" name="team_city" value="<?= e($teamCity) ?>" maxlength="80" required>
        <button class="team-dialog__save" type="submit">Save team details</button>
    </form>
</dialog>
<?php endif; ?>

<?php require __DIR__ . '/includes/court-menu.php'; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
