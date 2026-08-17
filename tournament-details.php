<?php
$events = [
    'coj-summer-showdown' => [
        'eyebrow' => 'COJ / Regional qualifier',
        'title' => 'COJ Summer Showdown',
        'date' => '14 Aug 2026',
        'time' => '10:00',
        'venue' => 'Ellis Park Courts',
        'city' => 'Johannesburg',
        'description' => 'Sixteen squads. One regional crown. The champions advance to King of the North.',
        'road' => 'The road to KON',
        'spots' => '08',
        'fee' => 'R500',
        'format' => '5V5',
        'roster' => '8',
        'check_in' => '09:15 — captain and full squad',
        'structure' => 'Group stage into knockout bracket',
        'prize' => 'Regional title, champion kit + KON qualification',
    ],
    'cop-regional-qualifier' => [
        'eyebrow' => 'COP / Regional qualifier',
        'title' => 'COP Regional Qualifier',
        'date' => '21 Aug 2026',
        'time' => '10:00',
        'venue' => 'Pitori Central Courts',
        'city' => 'Pretoria',
        'description' => 'Pitori squads meet for a direct route into the King of the South bracket.',
        'road' => 'The road to KOS',
        'spots' => '06',
        'fee' => 'R500',
        'format' => '5V5',
        'roster' => '8',
        'check_in' => '09:15 — captain and full squad',
        'structure' => 'Group stage into knockout bracket',
        'prize' => 'Regional title, champion kit + KOS qualification',
    ],
    'kon-kos-invitational' => [
        'eyebrow' => 'Open / Invitational qualifier',
        'title' => 'KON + KOS Invitational',
        'date' => '29 Aug 2026',
        'time' => '11:00',
        'venue' => 'Braamfontein Courts',
        'city' => 'Johannesburg',
        'description' => 'Two paths stay open for squads ready to earn their place in the Gauteng final.',
        'road' => 'The road to D.O.G.',
        'spots' => '04',
        'fee' => 'R650',
        'format' => '5V5',
        'roster' => '8',
        'check_in' => '10:15 — captain and full squad',
        'structure' => 'Invitational knockout bracket',
        'prize' => 'KON or KOS seeding + D.O.G. qualification route',
    ],
];

$eventSlug = (string) ($_POST['event'] ?? $_GET['event'] ?? 'coj-summer-showdown');
if (!isset($events[$eventSlug])) {
    $eventSlug = 'coj-summer-showdown';
}

$event = $events[$eventSlug];
$applicationSent = $_SERVER['REQUEST_METHOD'] === 'POST'
    && ($_POST['team'] ?? '') === 'jozi-reign';

$pageTitle = $event['title'];
$pageDescription = 'Tournament information and team application for ' . $event['title'] . '.';
$hideNavigation = true;
$bodyClass = 'tournament-detail-page';
$courtMenuActive = 'tournaments';

require __DIR__ . '/includes/header.php';
?>
<article class="event-detail">
    <img class="event-detail__mural" src="/blacktop-takeover/assets/images/figma/tournament-details-mural.svg" alt="" aria-hidden="true">

    <header class="screen-header">
        <a class="screen-wordmark" href="/blacktop-takeover/home.php">Blacktop Takeover</a>
        <button class="court-menu-trigger" type="button" aria-label="Open Blacktop menu" aria-controls="court-menu" aria-expanded="false" data-court-menu-trigger>
            <img src="/blacktop-takeover/assets/images/figma/navigation-basketball-trigger.svg" alt="">
        </button>
    </header>

    <section class="event-hero">
        <div class="event-hero__copy">
            <p class="event-eyebrow"><?= e($event['eyebrow']) ?></p>
            <h1><?= e($event['title']) ?></h1>
            <p class="event-meta"><?= e($event['date']) ?> <span>&middot;</span> <?= e($event['time']) ?> <span>&middot;</span> <?= e($event['venue']) ?></p>
            <p class="event-summary"><?= e($event['description']) ?></p>
        </div>
        <div class="event-hero__road">
            <img src="/blacktop-takeover/assets/images/figma/champion-feather-crown.svg" alt="" aria-hidden="true">
            <strong><?= e($event['road']) ?></strong>
            <span>KON is the gate. D.O.G. is the throne.</span>
            <small>Duke of Gauteng &middot; franchise final</small>
        </div>
    </section>

    <div class="event-detail__body">
        <div class="event-detail__main">
            <section class="event-facts" aria-label="Tournament facts">
                <div><strong><?= e($event['spots']) ?></strong><span>Spots left</span></div>
                <div><strong><?= e($event['fee']) ?></strong><span>Team fee</span></div>
                <div><strong><?= e($event['format']) ?></strong><span>Format</span></div>
                <div><strong><?= e($event['roster']) ?></strong><span>Max roster</span></div>
            </section>

            <section class="event-information">
                <h2>Event information</h2>
                <dl>
                    <div><dt>Venue</dt><dd><?= e($event['venue'] . ', ' . $event['city']) ?></dd></div>
                    <div><dt>Check-in</dt><dd><?= e($event['check_in']) ?></dd></div>
                    <div><dt>Format</dt><dd><?= e($event['structure']) ?></dd></div>
                    <div><dt>Prize</dt><dd><?= e($event['prize']) ?></dd></div>
                </dl>
            </section>
        </div>

        <aside class="event-application" aria-labelledby="application-title">
            <h2 id="application-title">Team application</h2>
            <p>Applications are reviewed by a Blacktop organiser.</p>

            <?php if ($applicationSent): ?>
                <div class="application-confirmation" role="status">
                    <strong>Application received.</strong>
                    <span>Jozi Reign is queued for organiser review.</span>
                </div>
            <?php endif; ?>

            <form method="post" action="/blacktop-takeover/tournament-details.php?event=<?= e($eventSlug) ?>">
                <input type="hidden" name="event" value="<?= e($eventSlug) ?>">
                <label for="application-team">Select team</label>
                <select id="application-team" name="team" required>
                    <option value="jozi-reign">Jozi Reign &middot; 8 players</option>
                </select>

                <span class="application-label">Roster status</span>
                <progress value="8" max="8">8 of 8 players</progress>
                <strong class="application-roster">8 / 8 players &middot; complete</strong>

                <button type="submit"<?= $applicationSent ? ' disabled' : '' ?>>
                    <?= $applicationSent ? 'Application submitted' : 'Submit team application' ?>
                </button>
            </form>
            <!-- Next pass: bind the team selector and tournament_entries insert to the authenticated captain record. -->
        </aside>
    </div>
</article>

<?php require __DIR__ . '/includes/court-menu.php'; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
