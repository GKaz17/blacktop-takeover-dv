<?php
$standings = [
    ['position' => 1, 'team' => 'Jozi Reign', 'played' => 2, 'points' => 6],
    ['position' => 2, 'team' => 'Soweto Stars', 'played' => 2, 'points' => 3],
    ['position' => 3, 'team' => 'Vuka Eleven', 'played' => 2, 'points' => 3],
    ['position' => 4, 'team' => 'Eastside FC', 'played' => 2, 'points' => 0],
];

$fixtures = [
    ['time' => '10:00', 'round' => 'Group A · R1', 'fixture' => 'Jozi Ballers  2 — 1  Eastside FC', 'court' => 'Court 1', 'status' => 'Final'],
    ['time' => '11:15', 'round' => 'Group B · R1', 'fixture' => 'Pretoria Kings  vs  Braam United', 'court' => 'Court 2', 'status' => 'Live'],
    ['time' => '12:30', 'round' => 'Group A · R2', 'fixture' => 'Jozi Reign  vs  Soweto Stars', 'court' => 'Court 1', 'status' => 'Up next'],
    ['time' => '13:45', 'round' => 'Group B · R2', 'fixture' => 'Vuka Eleven  vs  Tembisa City', 'court' => 'Court 2', 'status' => 'Scheduled'],
];

$pageTitle = 'Match Centre';
$pageDescription = 'Live fixtures, results and group standings for Blacktop Takeover.';
$hideNavigation = true;
$bodyClass = 'match-centre-page';
$courtMenuActive = 'matches';

require __DIR__ . '/includes/header.php';
?>
<section class="match-centre" data-match-centre>
    <img class="match-centre__mural" src="/blacktop-takeover/assets/images/figma/live-match-wall.svg" alt="" aria-hidden="true">

    <header class="screen-header">
        <a class="screen-wordmark" href="/blacktop-takeover/home.php">Blacktop Takeover</a>
        <button class="court-menu-trigger" type="button" aria-label="Open Blacktop menu" aria-controls="court-menu" aria-expanded="false" data-court-menu-trigger>
            <img src="/blacktop-takeover/assets/images/figma/navigation-basketball-trigger.svg" alt="">
        </button>
    </header>

    <div class="match-centre__content">
        <div class="match-centre__intro">
            <h1>Match centre</h1>
            <p>COJ Summer Showdown <span>/</span> Live event operations</p>
        </div>

        <div class="match-tabs" role="tablist" aria-label="Match centre views">
            <button id="match-tab-fixtures" type="button" role="tab" aria-selected="true" aria-controls="match-panel-fixtures" data-match-tab="fixtures">Fixtures</button>
            <button id="match-tab-results" type="button" role="tab" aria-selected="false" aria-controls="match-panel-results" tabindex="-1" data-match-tab="results">Results</button>
            <button id="match-tab-standings" type="button" role="tab" aria-selected="false" aria-controls="match-panel-standings" tabindex="-1" data-match-tab="standings">Standings</button>
        </div>

        <div id="match-panel-fixtures" class="match-panel is-active" role="tabpanel" aria-labelledby="match-tab-fixtures" data-match-panel="fixtures">
            <div class="match-overview">
                <article class="next-match" aria-labelledby="next-match-title">
                    <p id="next-match-title">Next fixture <span>·</span> Group A <span>·</span> Court 1</p>
                    <div class="next-match__teams">
                        <strong>Jozi Reign</strong>
                        <time datetime="2026-08-14T12:30">12:30</time>
                        <strong>Soweto Stars</strong>
                    </div>
                    <div class="next-match__footer">
                        <span>Check-in opens 30 min before kick-off</span>
                        <b>Up next</b>
                    </div>
                </article>

                <aside class="match-standings" aria-labelledby="group-standings-title">
                    <h2 id="group-standings-title">Group A standings</h2>
                    <table>
                        <thead><tr><th>#</th><th>Team</th><th>PL</th><th>PTS</th></tr></thead>
                        <tbody>
                            <?php foreach ($standings as $row): ?>
                                <tr<?= $row['position'] === 1 ? ' class="is-leading"' : '' ?>>
                                    <td><?= e((string) $row['position']) ?></td>
                                    <td><?= e($row['team']) ?></td>
                                    <td><?= e((string) $row['played']) ?></td>
                                    <td><?= e((string) $row['points']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <p>D.O.G. seed watch · every result moves the road</p>
                </aside>
            </div>

            <section class="match-schedule" aria-labelledby="today-schedule-title">
                <h2 id="today-schedule-title">Today’s schedule</h2>
                <div class="match-schedule__table-wrap">
                    <table>
                        <thead><tr><th>Time</th><th>Round</th><th>Fixture</th><th>Court</th><th>Status</th></tr></thead>
                        <tbody>
                            <?php foreach ($fixtures as $fixture): ?>
                                <tr>
                                    <td><time><?= e($fixture['time']) ?></time></td>
                                    <td><?= e($fixture['round']) ?></td>
                                    <td><?= e($fixture['fixture']) ?></td>
                                    <td><?= e($fixture['court']) ?></td>
                                    <td class="match-status match-status--<?= e(str_replace(' ', '-', strtolower($fixture['status']))) ?>"><?= e($fixture['status']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>

        <div id="match-panel-results" class="match-panel" role="tabpanel" aria-labelledby="match-tab-results" data-match-panel="results" hidden>
            <section class="match-secondary-view">
                <p class="match-secondary-view__eyebrow">Latest confirmed result</p>
                <h2>Jozi Ballers <span>2 — 1</span> Eastside FC</h2>
                <p>Group A · Round 1 · Court 1</p>
            </section>
        </div>

        <div id="match-panel-standings" class="match-panel" role="tabpanel" aria-labelledby="match-tab-standings" data-match-panel="standings" hidden>
            <section class="match-secondary-view match-secondary-view--standings">
                <h2>Group A table</h2>
                <p>Jozi Reign lead after two fixtures with six points.</p>
            </section>
        </div>

        <!-- Next pass: load fixtures/results from MySQL and add organiser score-entry controls with validation. -->
    </div>
</section>

<?php require __DIR__ . '/includes/court-menu.php'; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
