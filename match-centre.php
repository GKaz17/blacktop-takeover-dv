<?php
require_once __DIR__ . '/config/connection.php';

$requestedEvent = trim((string) ($_GET['event'] ?? ''));
if ($requestedEvent !== '') {
    $eventQuery = $conn->prepare("SELECT id, name, slug FROM tournaments WHERE slug = ? AND status <> 'cancelled' LIMIT 1");
    $eventQuery->bind_param('s', $requestedEvent);
} else {
    $eventQuery = $conn->prepare("SELECT id, name, slug FROM tournaments WHERE status <> 'cancelled' ORDER BY status = 'in_progress' DESC, starts_at LIMIT 1");
}
$eventQuery->execute();
$selectedEvent = $eventQuery->get_result()->fetch_assoc();

$matches = [];
if ($selectedEvent) {
    $eventId = (int) $selectedEvent['id'];
    $matchQuery = $conn->prepare(
        "SELECT m.id, m.round_name, m.court, m.scheduled_at, m.home_score, m.away_score, m.status,
                home.name AS home_team, away.name AS away_team
         FROM matches m
         JOIN teams home ON home.id = m.home_team_id
         JOIN teams away ON away.id = m.away_team_id
         WHERE m.tournament_id = ?
         ORDER BY m.scheduled_at, m.id"
    );
    $matchQuery->bind_param('i', $eventId);
    $matchQuery->execute();
    $matches = $matchQuery->get_result()->fetch_all(MYSQLI_ASSOC);
}

$standingsByTeam = [];
$results = [];
$nextMatch = null;
foreach ($matches as &$match) {
    $match['date'] = new DateTimeImmutable($match['scheduled_at']);
    if ($match['status'] === 'final') {
        $results[] = $match;
        foreach (['home_team', 'away_team'] as $teamKey) {
            $teamName = $match[$teamKey];
            $standingsByTeam[$teamName] ??= ['team' => $teamName, 'played' => 0, 'points' => 0];
            $standingsByTeam[$teamName]['played']++;
        }

        if ((int) $match['home_score'] === (int) $match['away_score']) {
            $standingsByTeam[$match['home_team']]['points']++;
            $standingsByTeam[$match['away_team']]['points']++;
        } elseif ((int) $match['home_score'] > (int) $match['away_score']) {
            $standingsByTeam[$match['home_team']]['points'] += 3;
        } else {
            $standingsByTeam[$match['away_team']]['points'] += 3;
        }
    } elseif ($nextMatch === null && in_array($match['status'], ['live', 'scheduled'], true)) {
        $nextMatch = $match;
    }
}
unset($match);

$standings = array_values($standingsByTeam);
usort($standings, static fn (array $a, array $b): int => [$b['points'], $b['played'], $a['team']] <=> [$a['points'], $a['played'], $b['team']]);
foreach ($standings as $index => &$standing) {
    $standing['position'] = $index + 1;
}
unset($standing);

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
            <p><?= e($selectedEvent['name'] ?? 'No published event') ?> <span>/</span> Live event operations</p>
        </div>

        <div class="match-tabs" role="tablist" aria-label="Match centre views">
            <button id="match-tab-fixtures" type="button" role="tab" aria-selected="true" aria-controls="match-panel-fixtures" data-match-tab="fixtures">Fixtures</button>
            <button id="match-tab-results" type="button" role="tab" aria-selected="false" aria-controls="match-panel-results" tabindex="-1" data-match-tab="results">Results</button>
            <button id="match-tab-standings" type="button" role="tab" aria-selected="false" aria-controls="match-panel-standings" tabindex="-1" data-match-tab="standings">Standings</button>
        </div>

        <div id="match-panel-fixtures" class="match-panel is-active" role="tabpanel" aria-labelledby="match-tab-fixtures" data-match-panel="fixtures">
            <div class="match-overview">
                <article class="next-match" aria-labelledby="next-match-title">
                    <?php if ($nextMatch): ?>
                        <p id="next-match-title">Next fixture <span>·</span> <?= e($nextMatch['round_name']) ?> <span>·</span> <?= e($nextMatch['court'] ?: 'Court TBA') ?></p>
                        <div class="next-match__teams">
                            <strong><?= e($nextMatch['home_team']) ?></strong>
                            <time datetime="<?= e($nextMatch['date']->format(DateTimeInterface::ATOM)) ?>"><?= e($nextMatch['date']->format('H:i')) ?></time>
                            <strong><?= e($nextMatch['away_team']) ?></strong>
                        </div>
                        <div class="next-match__footer">
                            <span>Check-in opens 30 min before tip-off</span>
                            <b><?= e($nextMatch['status'] === 'live' ? 'Live' : 'Up next') ?></b>
                        </div>
                    <?php else: ?>
                        <p id="next-match-title">Next fixture</p>
                        <div class="next-match__empty">The organiser has not published a fixture yet.</div>
                    <?php endif; ?>
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
                            <?php if ($standings === []): ?>
                                <tr><td colspan="4">Standings begin after the first final score.</td></tr>
                            <?php endif; ?>
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
                            <?php foreach ($matches as $fixture): ?>
                                <?php
                                $statusLabel = match ($fixture['status']) {
                                    'final' => 'Final',
                                    'live' => 'Live',
                                    'postponed' => 'Postponed',
                                    default => $nextMatch && (int) $fixture['id'] === (int) $nextMatch['id'] ? 'Up next' : 'Scheduled',
                                };
                                $scoreline = $fixture['status'] === 'final'
                                    ? $fixture['home_team'] . '  ' . $fixture['home_score'] . ' — ' . $fixture['away_score'] . '  ' . $fixture['away_team']
                                    : $fixture['home_team'] . '  vs  ' . $fixture['away_team'];
                                ?>
                                <tr>
                                    <td><time datetime="<?= e($fixture['date']->format(DateTimeInterface::ATOM)) ?>"><?= e($fixture['date']->format('H:i')) ?></time></td>
                                    <td><?= e($fixture['round_name']) ?></td>
                                    <td><?= e($scoreline) ?></td>
                                    <td><?= e($fixture['court']) ?></td>
                                    <td class="match-status match-status--<?= e(str_replace(' ', '-', strtolower($statusLabel))) ?>"><?= e($statusLabel) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if ($matches === []): ?>
                                <tr><td colspan="5">Fixtures will appear here when an organiser schedules the draw.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>

        <div id="match-panel-results" class="match-panel" role="tabpanel" aria-labelledby="match-tab-results" data-match-panel="results" hidden>
            <section class="match-secondary-view">
                <?php if ($results): ?>
                    <?php $latestResult = end($results); ?>
                    <p class="match-secondary-view__eyebrow">Latest confirmed result</p>
                    <h2><?= e($latestResult['home_team']) ?> <span><?= e((string) $latestResult['home_score']) ?> — <?= e((string) $latestResult['away_score']) ?></span> <?= e($latestResult['away_team']) ?></h2>
                    <p><?= e($latestResult['round_name']) ?> · <?= e($latestResult['court'] ?: 'Court TBA') ?></p>
                <?php else: ?>
                    <p class="match-secondary-view__eyebrow">Results</p>
                    <h2>No final scores yet</h2>
                    <p>Confirmed results will appear here.</p>
                <?php endif; ?>
            </section>
        </div>

        <div id="match-panel-standings" class="match-panel" role="tabpanel" aria-labelledby="match-tab-standings" data-match-panel="standings" hidden>
            <section class="match-secondary-view match-secondary-view--standings">
                <h2>Group A table</h2>
                <p><?= $standings ? e($standings[0]['team'] . ' lead with ' . $standings[0]['points'] . ' points.') : 'The table starts after the first confirmed result.' ?></p>
            </section>
        </div>

        <!-- Organiser score-entry controls belong to the protected organiser page. -->
    </div>
</section>

<?php require __DIR__ . '/includes/court-menu.php'; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
