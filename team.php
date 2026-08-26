<?php
session_start();

require_once __DIR__ . '/config/connection.php';

if (!in_array($_SESSION['user_role'] ?? null, ['player', 'captain'], true)) {
    header('Location: /blacktop-takeover/login.php');
    exit;
}

function teamPageSlug(string $teamName): string
{
    $slug = strtolower((string) preg_replace('/[^a-z0-9]+/i', '-', $teamName));
    $slug = trim($slug, '-');

    return ($slug !== '' ? $slug : 'team') . '-' . bin2hex(random_bytes(3));
}

function teamPageInviteCode(): string
{
    return 'BT-' . strtoupper(bin2hex(random_bytes(4)));
}

$userId = (int) $_SESSION['user_id'];
$userRole = (string) $_SESSION['user_role'];
$userName = trim((string) ($_SESSION['user_name'] ?? 'Blacktop player'));
$feedback = $_SESSION['team_feedback'] ?? null;
unset($_SESSION['team_feedback']);

if (empty($_SESSION['team_csrf'])) {
    $_SESSION['team_csrf'] = bin2hex(random_bytes(32));
}

$teamAccessQuery = $conn->prepare(
    "SELECT team.id, team.captain_id, membership.squad_role, membership.status
     FROM teams team
     LEFT JOIN team_members membership ON membership.team_id = team.id AND membership.user_id = ?
     WHERE team.captain_id = ? OR (membership.user_id = ? AND membership.status = 'active')
     ORDER BY (team.captain_id = ?) DESC
     LIMIT 1"
);
$teamAccessQuery->bind_param('iiii', $userId, $userId, $userId, $userId);
$teamAccessQuery->execute();
$teamAccess = $teamAccessQuery->get_result()->fetch_assoc();
$isOwnedTeamCaptain = $teamAccess && (int) $teamAccess['captain_id'] === $userId;
$isActiveViceCaptain = $teamAccess
    && $teamAccess['squad_role'] === 'vice_captain'
    && $teamAccess['status'] === 'active';
$canManageRoster = $isOwnedTeamCaptain || $isActiveViceCaptain;
$managedTeamId = (int) ($teamAccess['id'] ?? 0);
$managedCaptainId = (int) ($teamAccess['captain_id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postedToken = (string) ($_POST['csrf_token'] ?? '');
    $response = ['type' => 'error', 'message' => 'That squad action could not be completed.'];

    if (!hash_equals($_SESSION['team_csrf'], $postedToken)) {
        $response['message'] = 'Your session expired. Please try again.';
    } else {
        $action = (string) ($_POST['action'] ?? '');

        if ($action === 'create_team' && $userRole === 'captain') {
            $teamName = trim((string) ($_POST['team_name'] ?? ''));
            $teamCity = trim((string) ($_POST['team_city'] ?? ''));
            $existingTeam = $conn->prepare('SELECT id FROM teams WHERE captain_id = ? LIMIT 1');
            $existingTeam->bind_param('i', $userId);
            $existingTeam->execute();
            $captainAlreadyHasTeam = (bool) $existingTeam->get_result()->fetch_assoc();

            if ($captainAlreadyHasTeam) {
                $response['message'] = 'Your captain account already has a squad.';
            } elseif ($teamName === '' || $teamCity === '' || strlen($teamName) > 100 || strlen($teamCity) > 80) {
                $response['message'] = 'Enter a valid team name and home city.';
            } else {
                try {
                    $conn->begin_transaction();
                    $teamSlug = teamPageSlug($teamName);
                    $inviteCode = teamPageInviteCode();
                    $createTeam = $conn->prepare('INSERT INTO teams (name, slug, city, captain_id, invite_code) VALUES (?, ?, ?, ?, ?)');
                    $createTeam->bind_param('sssis', $teamName, $teamSlug, $teamCity, $userId, $inviteCode);
                    $createTeam->execute();
                    $newTeamId = (int) $conn->insert_id;
                    $addCaptain = $conn->prepare("INSERT INTO team_members (team_id, user_id, position, status, joined_at) VALUES (?, ?, 'Captain', 'active', NOW())");
                    $addCaptain->bind_param('ii', $newTeamId, $userId);
                    $addCaptain->execute();
                    $conn->commit();
                    $response = ['type' => 'success', 'message' => 'Squad created. Share the invite code with your players.'];
                } catch (Throwable $error) {
                    $conn->rollback();
                    $response['message'] = 'That team name is already in use.';
                }
            }
        } elseif ($action === 'update_team' && $isOwnedTeamCaptain) {
            $teamName = trim((string) ($_POST['team_name'] ?? ''));
            $teamCity = trim((string) ($_POST['team_city'] ?? ''));

            if ($teamName === '' || $teamCity === '' || strlen($teamName) > 100 || strlen($teamCity) > 80) {
                $response['message'] = 'Enter a valid team name and home city.';
            } else {
                try {
                    $updateTeam = $conn->prepare('UPDATE teams SET name = ?, city = ? WHERE captain_id = ?');
                    $updateTeam->bind_param('ssi', $teamName, $teamCity, $userId);
                    $updateTeam->execute();
                    $response = ['type' => 'success', 'message' => 'Team details saved.'];
                } catch (mysqli_sql_exception $exception) {
                    $response['message'] = 'That team name is already in use.';
                }
            }
        } elseif ($action === 'regenerate_invite' && $isOwnedTeamCaptain) {
            $inviteCode = teamPageInviteCode();
            $regenerateInvite = $conn->prepare('UPDATE teams SET invite_code = ? WHERE captain_id = ?');
            $regenerateInvite->bind_param('si', $inviteCode, $userId);
            $regenerateInvite->execute();
            $response = $regenerateInvite->affected_rows > 0
                ? ['type' => 'success', 'message' => 'A fresh invite code is ready. The old code no longer works.']
                : ['type' => 'error', 'message' => 'Create your squad before generating an invite code.'];
        } elseif ($action === 'update_member' && $canManageRoster) {
            $memberId = filter_input(INPUT_POST, 'member_id', FILTER_VALIDATE_INT);
            $jerseyInput = trim((string) ($_POST['jersey_number'] ?? ''));
            $position = trim((string) ($_POST['position'] ?? ''));
            $memberStatus = (string) ($_POST['member_status'] ?? '');
            $jerseyNumber = $jerseyInput === '' ? null : filter_var($jerseyInput, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 99]]);

            if (!$memberId || ($jerseyInput !== '' && $jerseyNumber === false) || strlen($position) > 40 || !in_array($memberStatus, ['active', 'inactive'], true)) {
                $response['message'] = 'Use a jersey number from 0 to 99 and choose a valid roster state.';
            } else {
                $captainOverride = $isOwnedTeamCaptain ? 1 : 0;
                $memberUpdate = $conn->prepare(
                    "UPDATE team_members
                     SET jersey_number = ?, position = ?, status = ?,
                         squad_role = CASE WHEN ? = 'inactive' THEN 'player' ELSE squad_role END,
                         joined_at = CASE WHEN ? = 'active' AND joined_at IS NULL THEN NOW() ELSE joined_at END
                     WHERE team_id = ? AND user_id = ? AND (? = 1 OR user_id <> ?)"
                );
                $memberUpdate->bind_param('issssiiii', $jerseyNumber, $position, $memberStatus, $memberStatus, $memberStatus, $managedTeamId, $memberId, $captainOverride, $managedCaptainId);
                $memberUpdate->execute();
                $response = $memberUpdate->affected_rows > 0
                    ? ['type' => 'success', 'message' => 'Player roster details updated.']
                    : ['type' => 'error', 'message' => 'That player is not editable or the details did not change.'];
            }
        } elseif ($action === 'set_vice_captain' && $isOwnedTeamCaptain) {
            $memberId = filter_input(INPUT_POST, 'member_id', FILTER_VALIDATE_INT) ?: 0;

            try {
                $conn->begin_transaction();
                $clearViceCaptain = $conn->prepare("UPDATE team_members SET squad_role = 'player' WHERE team_id = ? AND squad_role = 'vice_captain'");
                $clearViceCaptain->bind_param('i', $managedTeamId);
                $clearViceCaptain->execute();

                if ($memberId > 0) {
                    $setViceCaptain = $conn->prepare(
                        "UPDATE team_members
                         SET squad_role = 'vice_captain'
                         WHERE team_id = ? AND user_id = ? AND user_id <> ? AND status = 'active'"
                    );
                    $setViceCaptain->bind_param('iii', $managedTeamId, $memberId, $managedCaptainId);
                    $setViceCaptain->execute();

                    if ($setViceCaptain->affected_rows < 1) {
                        throw new RuntimeException('Vice captain must be an active squad member.');
                    }
                }

                $conn->commit();
                $response = ['type' => 'success', 'message' => $memberId > 0 ? 'Vice captain assigned.' : 'Vice captain assignment cleared.'];
            } catch (Throwable $error) {
                $conn->rollback();
                $response['message'] = $error->getMessage();
            }
        } elseif ($action === 'remove_member' && $isOwnedTeamCaptain) {
            $memberId = filter_input(INPUT_POST, 'member_id', FILTER_VALIDATE_INT);

            if ($memberId) {
                $removeMember = $conn->prepare(
                    'DELETE tm FROM team_members tm
                     JOIN teams t ON t.id = tm.team_id
                     WHERE tm.user_id = ? AND t.captain_id = ? AND tm.user_id <> t.captain_id'
                );
                $removeMember->bind_param('ii', $memberId, $userId);
                $removeMember->execute();
                $response = $removeMember->affected_rows > 0
                    ? ['type' => 'success', 'message' => 'Player removed from the squad.']
                    : ['type' => 'error', 'message' => 'That player could not be removed.'];
            }
        } elseif ($action === 'join_team' && $userRole === 'player') {
            $inviteCode = strtoupper(trim((string) ($_POST['invite_code'] ?? '')));
            $currentTeam = $conn->prepare("SELECT 1 FROM team_members WHERE user_id = ? AND status IN ('invited', 'active') LIMIT 1");
            $currentTeam->bind_param('i', $userId);
            $currentTeam->execute();

            if ($currentTeam->get_result()->fetch_row()) {
                $response['message'] = 'You already belong to a squad.';
            } elseif ($inviteCode === '') {
                $response['message'] = 'Enter the invite code from your captain.';
            } else {
                $findTeam = $conn->prepare('SELECT id FROM teams WHERE invite_code = ? LIMIT 1');
                $findTeam->bind_param('s', $inviteCode);
                $findTeam->execute();
                $invitedTeam = $findTeam->get_result()->fetch_assoc();

                if (!$invitedTeam) {
                    $response['message'] = 'That invite code does not match a squad.';
                } else {
                    $teamId = (int) $invitedTeam['id'];
                    $joinTeam = $conn->prepare(
                        "INSERT INTO team_members (team_id, user_id, status, joined_at)
                         VALUES (?, ?, 'active', NOW())
                         ON DUPLICATE KEY UPDATE status = 'active', joined_at = NOW()"
                    );
                    $joinTeam->bind_param('ii', $teamId, $userId);
                    $joinTeam->execute();
                    $response = ['type' => 'success', 'message' => 'You joined the squad.'];
                }
            }
        } elseif ($action === 'leave_team' && $userRole === 'player') {
            $leaveTeam = $conn->prepare('DELETE FROM team_members WHERE user_id = ?');
            $leaveTeam->bind_param('i', $userId);
            $leaveTeam->execute();
            $response = $leaveTeam->affected_rows > 0
                ? ['type' => 'success', 'message' => 'You left the squad. Use a new invite code when you are ready to join another.']
                : ['type' => 'error', 'message' => 'No active squad membership was found.'];
        }
    }

    $_SESSION['team_feedback'] = $response;
    header('Location: /blacktop-takeover/team.php');
    exit;
}

$teamQuery = $conn->prepare(
    "SELECT t.id, t.name, t.city, t.captain_id, t.invite_code,
            captain.first_name AS captain_first_name, captain.last_name AS captain_last_name,
            membership.squad_role AS viewer_squad_role, membership.status AS viewer_status
     FROM teams t
     JOIN users captain ON captain.id = t.captain_id
     LEFT JOIN team_members membership ON membership.team_id = t.id AND membership.user_id = ?
     WHERE t.captain_id = ?
        OR (membership.user_id = ? AND membership.status IN ('invited', 'active'))
     ORDER BY (t.captain_id = ?) DESC
     LIMIT 1"
);
$teamQuery->bind_param('iiii', $userId, $userId, $userId, $userId);
$teamQuery->execute();
$team = $teamQuery->get_result()->fetch_assoc();

$roster = [];
$nextFixture = null;
$activeRosterCount = 0;

if ($team) {
    $teamId = (int) $team['id'];
    $captainId = (int) $team['captain_id'];
    $rosterQuery = $conn->prepare(
        "SELECT u.id, u.first_name, u.last_name, u.role, tm.jersey_number, tm.position, tm.squad_role, tm.status
         FROM team_members tm
         JOIN users u ON u.id = tm.user_id
         WHERE tm.team_id = ?
         ORDER BY (u.id = ?) DESC, FIELD(tm.status, 'active', 'invited', 'inactive'), u.first_name, u.last_name"
    );
    $rosterQuery->bind_param('ii', $teamId, $captainId);
    $rosterQuery->execute();
    $roster = $rosterQuery->get_result()->fetch_all(MYSQLI_ASSOC);
    $activeRosterCount = count(array_filter($roster, static fn (array $member): bool => $member['status'] === 'active'));

    $fixtureQuery = $conn->prepare(
        "SELECT m.id, m.round_name, m.court, m.scheduled_at, m.status,
                tournament.name AS tournament_name, home.name AS home_team, away.name AS away_team
         FROM matches m
         JOIN tournaments tournament ON tournament.id = m.tournament_id
         JOIN teams home ON home.id = m.home_team_id
         JOIN teams away ON away.id = m.away_team_id
         WHERE (m.home_team_id = ? OR m.away_team_id = ?)
           AND (m.status = 'live' OR (m.status = 'scheduled' AND m.scheduled_at >= NOW()))
         ORDER BY m.status = 'live' DESC, m.scheduled_at
         LIMIT 1"
    );
    $fixtureQuery->bind_param('ii', $teamId, $teamId);
    $fixtureQuery->execute();
    $nextFixture = $fixtureQuery->get_result()->fetch_assoc();
}

$teamName = $team['name'] ?? 'No squad yet';
$teamCity = $team['city'] ?? 'Build or join a team to unlock your roster';
$captainName = $team ? trim($team['captain_first_name'] . ' ' . $team['captain_last_name']) : 'Not assigned';
$rosterReady = $activeRosterCount >= 3;
$fiveOnFiveReady = $activeRosterCount >= 5;
$isTeamCaptain = $team && (int) $team['captain_id'] === $userId;
$isViceCaptain = $team
    && $team['viewer_squad_role'] === 'vice_captain'
    && $team['viewer_status'] === 'active';
$canManageRoster = $isTeamCaptain || $isViceCaptain;
$viceCaptain = $team ? array_values(array_filter($roster, static fn (array $member): bool => $member['squad_role'] === 'vice_captain')) : [];
$viceCaptainName = $viceCaptain ? trim($viceCaptain[0]['first_name'] . ' ' . $viceCaptain[0]['last_name']) : 'Not assigned';

$pageTitle = 'My Squad';
$pageDescription = 'Manage your squad roster and Blacktop tournament applications.';
$hideNavigation = true;
$bodyClass = 'team-roster-page';
$courtMenuActive = 'team';
require __DIR__ . '/includes/header.php';
?>
<div class="squad-screen" data-figma-node="48:266">
    <img class="squad-screen__mural" src="/blacktop-takeover/assets/images/figma/team-roster-mural.svg" alt="" aria-hidden="true">
    <aside class="squad-rail">
        <img src="/blacktop-takeover/assets/images/figma/captain-rail-mural.svg" alt="" aria-hidden="true">
        <a class="squad-rail__brand" href="/blacktop-takeover/home.php">Blacktop<br>Takeover</a>
        <span class="squad-rail__tag" aria-hidden="true">011</span>
        <span class="squad-rail__captain" aria-hidden="true">Captain</span>
        <div class="squad-rail__account"><strong><?= e($isViceCaptain ? 'Vice captain' : ucfirst($userRole)) ?> account</strong><span><?= e($userName) ?></span></div>
    </aside>

    <section class="squad-content">
        <header class="squad-heading">
            <div><h1>My squad</h1><p>Manage eligibility, positions and tournament applications.</p></div>
            <?php if ($isTeamCaptain): ?><button class="squad-edit-button" type="button" data-team-dialog-open>Edit team details</button><?php endif; ?>
            <button class="court-menu-trigger" type="button" aria-label="Open Blacktop menu" aria-controls="court-menu" aria-expanded="false" data-court-menu-trigger><img src="/blacktop-takeover/assets/images/figma/navigation-basketball-trigger.svg" alt=""></button>
        </header>

        <?php if ($feedback): ?><p class="team-update-notice team-update-notice--<?= e($feedback['type']) ?>" role="status"><?= e($feedback['message']) ?></p><?php endif; ?>

        <section class="team-banner" aria-labelledby="team-name">
            <div>
                <h2 id="team-name"><?= e($teamName) ?></h2>
                <p><?= e($teamCity) ?> <span>&middot;</span> Captain: <?= e($captainName) ?> <span>&middot;</span> Vice captain: <?= e($viceCaptainName) ?></p>
                <?php if ($isTeamCaptain): ?>
                    <div class="team-code-controls">
                        <small>Invite code: <strong><?= e($team['invite_code'] ?: 'Not set') ?></strong></small>
                        <form method="post" action="/blacktop-takeover/team.php">
                            <input type="hidden" name="csrf_token" value="<?= e($_SESSION['team_csrf']) ?>"><input type="hidden" name="action" value="regenerate_invite">
                            <button type="submit">Refresh code</button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
            <div class="team-banner__status"><strong><?= e((string) $activeRosterCount) ?> / 8</strong><span><?= $rosterReady ? 'Entry minimum met' : 'Building roster' ?></span><small><?= $team ? 'Road to D.O.G. &middot; stage 01 / 03' : 'Awaiting squad access' ?></small></div>
            <b><?= $rosterReady ? 'Ready' : 'Recruiting' ?></b>
        </section>

        <div class="squad-layout">
            <section class="roster-panel" id="roster">
                <h2>Squad roster</h2><p>Only active players count toward tournament eligibility.</p>
                <?php if ($team): ?>
                    <div class="roster-table-wrap">
                        <table class="roster-table<?= $canManageRoster ? ' roster-table--managed' : '' ?>">
                            <thead><tr><th>No.</th><th>Player</th><th>Role</th><th>Status</th><?php if ($canManageRoster): ?><th>Manage</th><?php endif; ?></tr></thead>
                            <tbody>
                                <?php foreach ($roster as $player): ?>
                                    <?php
                                    $playerName = trim($player['first_name'] . ' ' . $player['last_name']);
                                    $isCaptain = (int) $player['id'] === (int) $team['captain_id'];
                                    $isDeputy = $player['squad_role'] === 'vice_captain';
                                    $playerRole = $isCaptain ? 'Captain' : ($isDeputy ? 'Vice captain' : ($player['position'] ?: 'Player'));
                                    $canEditMember = $isTeamCaptain || !$isCaptain;
                                    $canRemoveMember = $isTeamCaptain && !$isCaptain;
                                    ?>
                                    <tr class="roster-row roster-row--<?= e($player['status']) ?>">
                                        <td><?= e($player['jersey_number'] !== null ? str_pad((string) $player['jersey_number'], 2, '0', STR_PAD_LEFT) : '--') ?></td>
                                        <td><?= e($playerName) ?></td><td><?= e($playerRole) ?></td><td class="roster-status roster-status--<?= e(strtolower($player['status'])) ?>"><?= e(ucfirst($player['status'])) ?></td>
                                        <?php if ($canManageRoster): ?>
                                            <td><?php if ($canEditMember): ?><button class="roster-manage-button" type="button" data-member-dialog-open data-member-id="<?= e((string) $player['id']) ?>" data-member-name="<?= e($playerName) ?>" data-member-jersey="<?= e((string) ($player['jersey_number'] ?? '')) ?>" data-member-position="<?= e((string) ($player['position'] ?? '')) ?>" data-member-status="<?= e($player['status']) ?>" data-member-removable="<?= $canRemoveMember ? 'true' : 'false' ?>">Edit</button><?php else: ?><span class="roster-captain-lock">Captain only</span><?php endif; ?></td>
                                        <?php endif; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php if ($isTeamCaptain): ?>
                        <form class="squad-vice-form" method="post" action="/blacktop-takeover/team.php">
                            <input type="hidden" name="csrf_token" value="<?= e($_SESSION['team_csrf']) ?>"><input type="hidden" name="action" value="set_vice_captain">
                            <label for="vice-captain-member">Vice captain</label>
                            <select id="vice-captain-member" name="member_id">
                                <option value="0">No vice captain assigned</option>
                                <?php foreach ($roster as $player): ?>
                                    <?php if ((int) $player['id'] !== (int) $team['captain_id'] && $player['status'] === 'active'): ?>
                                        <option value="<?= e((string) $player['id']) ?>"<?= $player['squad_role'] === 'vice_captain' ? ' selected' : '' ?>><?= e(trim($player['first_name'] . ' ' . $player['last_name'])) ?></option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit">Save deputy</button>
                        </form>
                    <?php endif; ?>
                    <?php if ($userRole === 'player'): ?>
                        <form class="squad-leave-form" method="post" action="/blacktop-takeover/team.php" onsubmit="return confirm('Leave this squad?');"><input type="hidden" name="csrf_token" value="<?= e($_SESSION['team_csrf']) ?>"><input type="hidden" name="action" value="leave_team"><button type="submit">Leave squad</button></form>
                    <?php endif; ?>
                <?php elseif ($userRole === 'player'): ?>
                    <form class="squad-join-form" method="post" action="/blacktop-takeover/team.php"><input type="hidden" name="csrf_token" value="<?= e($_SESSION['team_csrf']) ?>"><input type="hidden" name="action" value="join_team"><label for="squad-invite-code">Captain's invite code</label><div><input id="squad-invite-code" name="invite_code" maxlength="40" autocomplete="off" required><button type="submit">Join squad</button></div></form>
                <?php else: ?>
                    <form class="squad-create-form" method="post" action="/blacktop-takeover/team.php"><input type="hidden" name="csrf_token" value="<?= e($_SESSION['team_csrf']) ?>"><input type="hidden" name="action" value="create_team"><div><span>Captain recovery route</span><h3>Build your squad</h3><p>Your account has no team attached. Create one here and the captain roster entry will be restored automatically.</p></div><label>Team name<input name="team_name" maxlength="100" required></label><label>Home city<input name="team_city" maxlength="80" required></label><button type="submit">Create squad</button></form>
                <?php endif; ?>
            </section>

            <aside class="squad-cards">
                <section class="fixture-card">
                    <h2><?= $nextFixture && $nextFixture['status'] === 'live' ? 'Live fixture' : 'Next fixture' ?></h2>
                    <?php if ($nextFixture): ?>
                        <p><?= e($nextFixture['tournament_name']) ?> &middot; <?= e($nextFixture['round_name']) ?></p><strong><?= e($nextFixture['home_team']) ?></strong><b>VS</b><strong><?= e($nextFixture['away_team']) ?></strong>
                        <time datetime="<?= e((new DateTimeImmutable($nextFixture['scheduled_at']))->format('Y-m-d\TH:i')) ?>"><?= e((new DateTimeImmutable($nextFixture['scheduled_at']))->format('d M · H:i')) ?><?= $nextFixture['court'] ? ' · ' . e($nextFixture['court']) : '' ?></time>
                    <?php else: ?>
                        <p>Fixture board</p><strong>Awaiting draw</strong><span class="fixture-card__empty">Your next approved match will appear here as soon as an organiser publishes it.</span>
                    <?php endif; ?>
                </section>
                <section class="entry-ready-card"><h2>Ready to enter?</h2><p><?= $fiveOnFiveReady ? 'Your active roster meets the 3v3 and 5v5 entry floors.' : ($rosterReady ? 'Your squad can enter 3v3. Build to five active players for 5v5.' : 'Build an active roster of at least three players first.') ?></p><?php if ($rosterReady): ?><a href="/blacktop-takeover/tournaments.php">Choose an event</a><?php endif; ?></section>
            </aside>
        </div>
    </section>
</div>

<?php if ($isTeamCaptain): ?>
<dialog class="team-dialog" data-team-dialog><form method="post" action="/blacktop-takeover/team.php"><input type="hidden" name="csrf_token" value="<?= e($_SESSION['team_csrf']) ?>"><input type="hidden" name="action" value="update_team"><div class="team-dialog__heading"><h2>Edit team details</h2><button type="button" aria-label="Close team editor" data-team-dialog-close>&times;</button></div><label for="team-name-input">Team name</label><input id="team-name-input" name="team_name" value="<?= e($teamName) ?>" maxlength="100" required><label for="team-city-input">Home city</label><input id="team-city-input" name="team_city" value="<?= e($teamCity) ?>" maxlength="80" required><button class="team-dialog__save" type="submit">Save team details</button></form></dialog>
<?php endif; ?>

<?php if ($canManageRoster): ?>
<dialog class="team-dialog member-dialog" data-member-dialog aria-labelledby="member-dialog-title">
    <form method="post" action="/blacktop-takeover/team.php">
        <input type="hidden" name="csrf_token" value="<?= e($_SESSION['team_csrf']) ?>"><input type="hidden" name="action" value="update_member"><input type="hidden" name="member_id" value="" data-member-id-field>
        <div class="team-dialog__heading"><div><span>Roster control</span><h2 id="member-dialog-title" data-member-name>Player</h2></div><button type="button" aria-label="Close roster editor" data-member-dialog-close>&times;</button></div>
        <div class="member-dialog__split"><label for="member-jersey">Jersey number</label><input id="member-jersey" type="number" name="jersey_number" min="0" max="99" data-member-jersey-field><label for="member-position">Position</label><input id="member-position" name="position" maxlength="40" placeholder="Guard / Wing / Center" data-member-position-field><label for="member-status">Roster status</label><select id="member-status" name="member_status" data-member-status-field><option value="active">Active</option><option value="inactive">Inactive</option></select></div>
        <div class="member-dialog__actions"><button class="team-dialog__save" type="submit">Save player</button><button class="member-dialog__remove" type="submit" name="action" value="remove_member" formnovalidate data-member-remove>Remove from squad</button></div>
    </form>
</dialog>
<?php endif; ?>

<?php require __DIR__ . '/includes/court-menu.php'; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
