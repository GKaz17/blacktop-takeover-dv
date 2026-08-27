<?php
require_once __DIR__ . '/config/connection.php';

$requestedEvent = trim((string) ($_GET['event'] ?? ''));
$eventOptions = $conn->query(
    "SELECT t.id, t.name, t.slug, t.status, t.starts_at,
            COUNT(m.id) AS match_count,
            COUNT(CASE WHEN m.status = 'final' THEN 1 END) AS final_count
     FROM tournaments t
     LEFT JOIN matches m ON m.tournament_id = t.id
     WHERE t.status <> 'cancelled'
     GROUP BY t.id
     ORDER BY t.status = 'in_progress' DESC, t.starts_at, t.id"
)->fetch_all(MYSQLI_ASSOC);

$selectedEvent = $eventOptions[0] ?? null;
if ($requestedEvent !== '') {
    foreach ($eventOptions as $eventOption) {
        if ($eventOption['slug'] === $requestedEvent) {
            $selectedEvent = $eventOption;
            break;
        }
    }
}

$matches = [];
if ($selectedEvent) {
    $eventId = (int) $selectedEvent['id'];
    $matchQuery = $conn->prepare(
        "SELECT id, round_name, court, scheduled_at, home_score, away_score, status,
                home_team, away_team
         FROM tournament_match_feed
         WHERE tournament_id = ?
         ORDER BY scheduled_at, id"
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

    foreach (['home_team', 'away_team'] as $teamKey) {
        $teamName = $match[$teamKey];
        $standingsByTeam[$teamName] ??= ['team' => $teamName, 'played' => 0, 'points' => 0];
    }

    if ($match['status'] === 'final') {
        $results[] = $match;
        foreach (['home_team', 'away_team'] as $teamKey) {
            $teamName = $match[$teamKey];
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
$matchCentreEvent = $selectedEvent['slug'] ?? '';

require __DIR__ . '/includes/header.php';
?>
<section class="match-centre" data-match-centre data-figma-node="48:345">
    <img class="match-centre__mural" src="/blacktop-takeover/assets/images/figma/live-match-wall.svg" alt="" aria-hidden="true">
    <div class="match-centre__culture-type" aria-hidden="true">
        <!-- Approved graffiti text layers from FINAL D05, kept separate from the mural SVG so they scale cleanly. -->
        <span class="match-centre__tag match-centre__tag--versus">011 vs Soweto</span>
        <span class="match-centre__tag match-centre__tag--districts">011 × 012</span>
        <span class="match-centre__tag match-centre__tag--live">Live from the Blacktop</span>
        <span class="match-centre__tag match-centre__tag--tar">Live on the tar</span>
    </div>

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

        <?php if ($eventOptions !== []): ?>
            <nav class="match-event-switcher" aria-label="Choose tournament match feed">
                <span class="match-event-switcher__label">Event feed</span>
                <div class="match-event-switcher__options">
                    <?php foreach ($eventOptions as $eventOption): ?>
                        <?php $isSelectedEvent = $selectedEvent && (int) $eventOption['id'] === (int) $selectedEvent['id']; ?>
                        <a
                            href="/blacktop-takeover/match-centre.php?event=<?= e(rawurlencode($eventOption['slug'])) ?>"
                            class="<?= $isSelectedEvent ? 'is-active' : '' ?>"
                            <?= $isSelectedEvent ? 'aria-current="page"' : '' ?>
                        >
                            <strong><?= e($eventOption['name']) ?></strong>
                            <span><?= e((string) $eventOption['match_count']) ?> fixture<?= (int) $eventOption['match_count'] === 1 ? '' : 's' ?> / <?= e((string) $eventOption['final_count']) ?> final</span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </nav>
        <?php endif; ?>

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
                            <?php if ($matches === []): ?>
                                <tr><td colspan="4">Standings begin when the draw is published.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                    <p>D.O.G. seed watch&nbsp; · &nbsp;every result moves the road</p>
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
                                    ? $fixture['home_team'] . '  ' . $fixture['home_score'] . ' : ' . $fixture['away_score'] . '  ' . $fixture['away_team']
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
            <section class="match-results" aria-labelledby="confirmed-results-title">
                <div class="match-view-heading">
                    <div>
                        <p>Score archive</p>
                        <h2 id="confirmed-results-title">Confirmed results</h2>
                    </div>
                    <span><?= e((string) count($results)) ?> final<?= count($results) === 1 ? '' : 's' ?></span>
                </div>

                <div class="match-schedule__table-wrap">
                    <table class="match-results__table">
                        <thead><tr><th>Time</th><th>Round</th><th>Final score</th><th>Court</th><th>Status</th></tr></thead>
                        <tbody>
                            <?php foreach (array_reverse($results) as $result): ?>
                                <tr>
                                    <td><time datetime="<?= e($result['date']->format(DateTimeInterface::ATOM)) ?>"><?= e($result['date']->format('H:i')) ?></time></td>
                                    <td><?= e($result['round_name']) ?></td>
                                    <td class="match-results__score">
                                        <span><?= e($result['home_team']) ?></span>
                                        <b><?= e((string) $result['home_score']) ?> : <?= e((string) $result['away_score']) ?></b>
                                        <span><?= e($result['away_team']) ?></span>
                                    </td>
                                    <td><?= e($result['court'] ?: 'Court TBA') ?></td>
                                    <td class="match-status match-status--final">Final</td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if ($results === []): ?>
                                <tr><td colspan="5">Confirmed scores will appear here after the organiser closes a match.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>

        <div id="match-panel-standings" class="match-panel" role="tabpanel" aria-labelledby="match-tab-standings" data-match-panel="standings" hidden>
            <section class="match-table-view" aria-labelledby="full-standings-title">
                <div class="match-view-heading">
                    <div>
                        <p>D.O.G. seed watch</p>
                        <h2 id="full-standings-title">Group A table</h2>
                    </div>
                    <span><?= e((string) count($standings)) ?> teams</span>
                </div>

                <div class="match-table-view__wrap">
                    <table>
                        <thead><tr><th>Seed</th><th>Team</th><th>Played</th><th>Points</th></tr></thead>
                        <tbody>
                            <?php foreach ($standings as $row): ?>
                                <tr<?= $row['position'] === 1 ? ' class="is-leading"' : '' ?>>
                                    <td><b><?= e(str_pad((string) $row['position'], 2, '0', STR_PAD_LEFT)) ?></b></td>
                                    <td><?= e($row['team']) ?></td>
                                    <td><?= e((string) $row['played']) ?></td>
                                    <td><?= e((string) $row['points']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if ($matches === []): ?>
                                <tr><td colspan="4">The table starts when the organiser publishes the draw.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>

        <!-- Organiser score-entry controls belong to the protected organiser page. -->
    </div>
</section>

<?php require __DIR__ . '/includes/court-menu.php'; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
