<?php
$teamName = trim((string) ($_POST['team_name'] ?? 'Jozi Reign'));
$teamCity = trim((string) ($_POST['team_city'] ?? 'Johannesburg'));
$teamName = $teamName !== '' ? mb_substr($teamName, 0, 100) : 'Jozi Reign';
$teamCity = $teamCity !== '' ? mb_substr($teamCity, 0, 80) : 'Johannesburg';
$teamUpdated = $_SERVER['REQUEST_METHOD'] === 'POST';

$roster = [
    ['number' => '07', 'name' => 'K. Maverick', 'role' => 'Starter', 'status' => 'Verified'],
    ['number' => '10', 'name' => 'Sims Mokoena', 'role' => 'Starter', 'status' => 'Verified'],
    ['number' => '11', 'name' => 'Bibo Kazadi', 'role' => 'Starter', 'status' => 'Verified'],
    ['number' => '23', 'name' => 'TK Maseko', 'role' => 'Starter', 'status' => 'Verified'],
    ['number' => '30', 'name' => 'Jay Motsepe', 'role' => 'Starter', 'status' => 'Verified'],
    ['number' => '04', 'name' => 'L. Dube', 'role' => 'Substitute', 'status' => 'Verified'],
    ['number' => '18', 'name' => 'M. Nkosi', 'role' => 'Substitute', 'status' => 'Pending'],
    ['number' => '21', 'name' => 'T. Molefe', 'role' => 'Substitute', 'status' => 'Verified'],
];

$pageTitle = 'My Squad';
$pageDescription = 'Manage the Jozi Reign roster and Blacktop tournament applications.';
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
            <strong>Captain account</strong>
            <span>Gedeon Kazadi</span>
        </div>
    </aside>

    <section class="squad-content">
        <header class="squad-heading">
            <div>
                <h1>My squad</h1>
                <p>Manage eligibility, positions and tournament applications.</p>
            </div>
            <button class="squad-edit-button" type="button" data-team-dialog-open>Edit team details</button>
            <button class="court-menu-trigger" type="button" aria-label="Open Blacktop menu" aria-controls="court-menu" aria-expanded="false" data-court-menu-trigger>
                <img src="/blacktop-takeover/assets/images/figma/navigation-basketball-trigger.svg" alt="">
            </button>
        </header>

        <?php if ($teamUpdated): ?>
            <p class="team-update-notice" role="status">Team details updated for this session.</p>
        <?php endif; ?>

        <section class="team-banner" aria-labelledby="team-name">
            <div>
                <h2 id="team-name"><?= e($teamName) ?></h2>
                <p><?= e($teamCity) ?> <span>&middot;</span> Captain: Gedeon Kazadi</p>
            </div>
            <div class="team-banner__status">
                <strong>8 / 8</strong>
                <span>Roster complete</span>
                <small>Road to D.O.G. &middot; stage 01 / 03</small>
            </div>
            <b>Qualified</b>
        </section>

        <div class="squad-layout">
            <section class="roster-panel" id="roster">
                <h2>Active roster</h2>
                <p>All players must be verified before the organiser approves an application.</p>
                <div class="roster-table-wrap">
                    <table class="roster-table">
                        <thead>
                            <tr><th>No.</th><th>Player</th><th>Role</th><th>Status</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($roster as $player): ?>
                                <tr>
                                    <td><?= e($player['number']) ?></td>
                                    <td><?= e($player['name']) ?></td>
                                    <td><?= e($player['role']) ?></td>
                                    <td class="roster-status roster-status--<?= strtolower($player['status']) ?>"><?= e($player['status']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <!-- Next pass: populate this roster from team_members and add captain invite/remove controls. -->
            </section>

            <aside class="squad-cards">
                <section class="fixture-card">
                    <h2>Next fixture</h2>
                    <p>COJ &middot; Group A</p>
                    <strong><?= e($teamName) ?></strong>
                    <b>VS</b>
                    <strong>Soweto Stars</strong>
                    <time datetime="2026-08-14T12:30">14 Aug &middot; 12:30</time>
                </section>

                <section class="entry-ready-card">
                    <h2>Ready to enter?</h2>
                    <p>Your roster meets the minimum requirements.</p>
                    <a href="/blacktop-takeover/tournament-details.php?event=coj-summer-showdown">Apply to event</a>
                </section>
            </aside>
        </div>
    </section>
</div>

<dialog class="team-dialog" data-team-dialog>
    <form method="post" action="/blacktop-takeover/team.php">
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

<?php require __DIR__ . '/includes/court-menu.php'; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
