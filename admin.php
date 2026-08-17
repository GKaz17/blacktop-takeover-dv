<?php
$dashboardMetrics = [
    ['label' => 'Total teams', 'value' => '48', 'tone' => 'orange'],
    ['label' => 'Active tournaments', 'value' => '6', 'tone' => 'yellow'],
    ['label' => 'Pending approvals', 'value' => '11', 'tone' => 'blue'],
    ['label' => 'Fixtures this week', 'value' => '14', 'tone' => 'pink'],
];

$registrationBars = [
    ['label' => 'COJ 01', 'value' => 28, 'tone' => 'orange'],
    ['label' => 'COP 01', 'value' => 74, 'tone' => 'yellow'],
    ['label' => 'COJ 02', 'value' => 54, 'tone' => 'blue'],
    ['label' => 'KON Q', 'value' => 51, 'tone' => 'orange'],
    ['label' => 'KOS Q', 'value' => 44, 'tone' => 'yellow'],
    ['label' => 'OPEN', 'value' => 53, 'tone' => 'blue'],
    ['label' => 'U18', 'value' => 24, 'tone' => 'orange'],
    ['label' => 'D.O.G.', 'value' => 82, 'tone' => 'yellow'],
    ['label' => 'WOMEN', 'value' => 32, 'tone' => 'blue'],
    ['label' => 'FINAL', 'value' => 61, 'tone' => 'orange'],
];

$pendingTeams = [
    ['name' => 'Hillbrow Heat', 'event' => 'COJ Summer Showdown', 'captain' => 'Lebo Nkosi', 'roster' => '8 / 8'],
    ['name' => 'Pitori Pressure', 'event' => 'COP Regional Qualifier', 'captain' => 'Tumi Mokoena', 'roster' => '7 / 8'],
    ['name' => 'Braam United', 'event' => 'KON + KOS Invitational', 'captain' => 'Ari Jacobs', 'roster' => '8 / 8'],
    ['name' => 'East Rand Motion', 'event' => 'COJ Summer Showdown', 'captain' => 'Neo Dlamini', 'roster' => '8 / 8'],
];

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
        <a href="/blacktop-takeover/home.php">Blacktop<br>Admin</a>
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
                        data-team-roster="<?= e($team['roster']) ?>"
                    >
                        <span><strong><?= e($team['name']) ?></strong> — <?= e($team['event']) ?></span>
                        <b>Pending</b>
                    </button>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- Next pass: query dashboard metrics from MySQL and persist approve/request-changes/reject decisions. -->
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
    <p>Decision controls will connect to the organiser approval workflow in the next database pass.</p>
</dialog>

<?php require __DIR__ . '/includes/court-menu.php'; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
