<?php
session_start();

require_once __DIR__ . '/config/connection.php';

$userId = (int) ($_SESSION['user_id'] ?? 0);
$userRole = $_SESSION['user_role'] ?? null;

$tournamentQuery = $conn->prepare(
    "SELECT t.id, t.name, t.slug, t.eyebrow, t.city, t.venue, t.starts_at,
            t.registration_deadline, t.format, t.capacity, t.status,
            COUNT(DISTINCT CASE WHEN te.status IN ('pending', 'confirmed') THEN te.team_id END) AS entry_count,
            MAX(CASE WHEN captain_team.captain_id = ? THEN te.status ELSE NULL END) AS captain_entry_status
     FROM tournaments t
     LEFT JOIN tournament_entries te ON te.tournament_id = t.id
     LEFT JOIN teams captain_team ON captain_team.id = te.team_id
     WHERE t.status <> 'cancelled'
     GROUP BY t.id
     ORDER BY t.starts_at"
);
$tournamentQuery->bind_param('i', $userId);
$tournamentQuery->execute();
$tournaments = $tournamentQuery->get_result()->fetch_all(MYSQLI_ASSOC);

$openTournamentCount = 0;
$totalOpenSpots = 0;

foreach ($tournaments as $tournament) {
    if ($tournament['status'] === 'open') {
        $openTournamentCount++;
    }

    $totalOpenSpots += max(0, (int) $tournament['capacity'] - (int) $tournament['entry_count']);
}

$nextTournament = $tournaments[0] ?? null;
$nextTournamentDate = $nextTournament
    ? (new DateTimeImmutable($nextTournament['starts_at']))->format('d M')
    : 'TBC';

$pageTitle = 'Tournaments';
$pageDescription = 'Tournament dates, registration status and qualification routes for Blacktop Takeover.';
$hideNavigation = true;
$bodyClass = 'tournaments-page';
$courtMenuActive = 'tournaments';

require __DIR__ . '/includes/header.php';
?>
<section class="tournament-board">
    <img class="tournament-board__mural" src="/blacktop-takeover/assets/images/figma/tournament-details-mural.svg" alt="" aria-hidden="true">

    <div class="tournament-board__street-tags" aria-hidden="true">
        <span>Run Gauteng</span>
        <span>011 / 012</span>
    </div>

    <header class="screen-header">
        <a class="screen-wordmark" href="/blacktop-takeover/home.php">Blacktop Takeover</a>
        <button class="court-menu-trigger" type="button" aria-label="Open Blacktop menu" aria-controls="court-menu" aria-expanded="false" data-court-menu-trigger>
            <img src="/blacktop-takeover/assets/images/figma/navigation-basketball-trigger.svg" alt="">
        </button>
    </header>

    <div class="tournament-board__content">
        <div class="tournament-board__hero">
            <div class="tournament-board__heading">
                <p>Season board / Gauteng</p>
                <h1>Tournament run</h1>
                <span>Dates, capacity and your entry status in one place.</span>
            </div>

            <dl class="tournament-board__summary" aria-label="Tournament season summary">
                <div>
                    <dt>Open events</dt>
                    <dd><?= e((string) $openTournamentCount) ?></dd>
                </div>
                <div>
                    <dt>Open spots</dt>
                    <dd><?= e((string) $totalOpenSpots) ?></dd>
                </div>
                <div>
                    <dt>Next run</dt>
                    <dd><?= e(strtoupper($nextTournamentDate)) ?></dd>
                </div>
            </dl>
        </div>

        <div class="tournament-board__section-heading">
            <h2>Published events</h2>
            <p>COJ / COP <span>&rarr;</span> KON / KOS <span>&rarr;</span> D.O.G.</p>
        </div>

        <div class="tournament-board__list">
            <?php foreach ($tournaments as $index => $tournament): ?>
                <?php
                $startsAt = new DateTimeImmutable($tournament['starts_at']);
                $deadline = new DateTimeImmutable($tournament['registration_deadline']);
                $spotsLeft = max(0, (int) $tournament['capacity'] - (int) $tournament['entry_count']);
                ?>
                <article class="tournament-row tournament-row--<?= e(['orange', 'blue', 'yellow'][$index % 3]) ?>">
                    <div class="tournament-row__date">
                        <strong><?= e($startsAt->format('d')) ?></strong>
                        <span><?= e(strtoupper($startsAt->format('M'))) ?></span>
                    </div>
                    <div class="tournament-row__main">
                        <p><?= e($tournament['eyebrow']) ?></p>
                        <h3><?= e($tournament['name']) ?></h3>
                        <span><?= e($tournament['venue']) ?> / <?= e($tournament['city']) ?></span>
                    </div>
                    <dl class="tournament-row__facts">
                        <div><dt>Format</dt><dd><?= e(strtoupper($tournament['format'])) ?></dd></div>
                        <div><dt>Spots</dt><dd><?= e((string) $spotsLeft) ?></dd></div>
                        <div><dt>Deadline</dt><dd><?= e($deadline->format('d M')) ?></dd></div>
                    </dl>
                    <div class="tournament-row__action">
                        <?php if ($userRole === 'captain' && $tournament['captain_entry_status']): ?>
                            <small><?= e(ucfirst($tournament['captain_entry_status'])) ?> entry</small>
                        <?php else: ?>
                            <small><?= e(ucfirst(str_replace('_', ' ', $tournament['status']))) ?></small>
                        <?php endif; ?>
                        <a href="/blacktop-takeover/tournament-details.php?event=<?= e($tournament['slug']) ?>">View event</a>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>

        <?php if ($tournaments === []): ?>
            <p class="tournament-board__empty">No tournament dates have been published yet.</p>
        <?php endif; ?>
    </div>
</section>

<?php require __DIR__ . '/includes/court-menu.php'; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
