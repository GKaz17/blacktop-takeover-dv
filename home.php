<?php
require_once __DIR__ . '/config/connection.php';

$pageTitle = 'Tournament Discovery';
$pageDescription = 'Discover upcoming Blacktop Takeover tournaments across Jozi and Pitori.';
$hideNavigation = true;
$bodyClass = 'takeover-home';
$courtMenuActive = 'discover';

$tournamentArt = [
    'coj-summer-showdown' => [
        'slug' => 'coj-summer-showdown',
        'region' => 'coj',
        'keywords' => 'Johannesburg 011',
        'location_label' => 'Jozi',
        'image' => '/blacktop-takeover/assets/images/figma/coj-summer-showdown.png',
        'accent' => 'orange',
        'tilt' => 'left',
    ],
    'cop-regional-qualifier' => [
        'slug' => 'cop-regional-qualifier',
        'region' => 'cop',
        'keywords' => 'Pretoria Pitori 012',
        'location_label' => 'PTA',
        'image' => '/blacktop-takeover/assets/images/figma/cop-regional-qualifier.png',
        'accent' => 'blue',
        'tilt' => 'right',
    ],
    'kon-kos-invitational' => [
        'slug' => 'kon-kos-invitational',
        'region' => 'open',
        'keywords' => 'Johannesburg Jozi Pretoria PTA 011 012',
        'display_title' => 'KON + KOS Invitational Qualifiers',
        'display_meta' => 'Braamfontein ↔ Pitori · Two paths open',
        'images' => [
            '/blacktop-takeover/assets/images/official/kos-basketball-poster.png',
            '/blacktop-takeover/assets/images/official/kon-basketball-poster.png',
        ],
        'art_class' => 'official-basketball',
        'accent' => 'gold',
        'tilt' => 'none',
    ],
];

$tournaments = [];
$tournamentResult = $conn->query(
    "SELECT name, slug, city, venue, starts_at, status
     FROM tournaments
     WHERE status <> 'cancelled'
     ORDER BY starts_at
     LIMIT 3"
);
foreach ($tournamentResult->fetch_all(MYSQLI_ASSOC) as $event) {
    if (!isset($tournamentArt[$event['slug']])) {
        continue;
    }

    $startsAt = new DateTimeImmutable($event['starts_at']);
    $art = $tournamentArt[$event['slug']];
    $art['title'] = $art['display_title'] ?? $event['name'];
    $art['meta'] = $art['display_meta']
        ?? $startsAt->format('M d') . ' · ' . ($art['location_label'] ?? $event['city']);
    $art['keywords'] .= ' ' . $event['venue'] . ' ' . $event['city'];
    $art['action'] = $event['status'] === 'open' ? ($event['slug'] === 'kon-kos-invitational' ? 'Qualify now' : 'Open') : ucfirst(str_replace('_', ' ', $event['status']));
    $art['registration'] = $event['status'];
    $tournaments[] = $art;
}

require __DIR__ . '/includes/header.php';
?>
<section class="discovery-page" data-discovery data-figma-node="48:124">
    <img
        class="discovery-culture-layer"
        src="/blacktop-takeover/assets/images/figma/discovery-poster-wall.svg"
        alt=""
        aria-hidden="true"
    >

    <header class="discovery-header">
        <a class="discovery-wordmark" href="/blacktop-takeover/" aria-label="Blacktop Takeover landing page">
            Blacktop Takeover
        </a>
        <button
            class="court-menu-trigger"
            type="button"
            aria-label="Open Blacktop menu"
            aria-controls="court-menu"
            aria-expanded="false"
            data-court-menu-trigger
        >
            <img src="/blacktop-takeover/assets/images/figma/navigation-basketball-trigger.svg" alt="">
        </button>
    </header>

    <div class="discovery-toolbar">
        <label class="sr-only" for="tournament-search">Search tournaments</label>
        <input
            id="tournament-search"
            class="tournament-search"
            type="search"
            placeholder="Search tournaments..."
            autocomplete="off"
            data-tournament-search
        >
        <div class="tournament-filters" role="group" aria-label="Filter tournaments">
            <button class="tournament-filter is-active" type="button" data-tournament-filter="all" aria-pressed="true">All</button>
            <button class="tournament-filter" type="button" data-tournament-filter="coj" aria-pressed="false">COJ</button>
            <button class="tournament-filter" type="button" data-tournament-filter="cop" aria-pressed="false">COP</button>
            <button class="tournament-filter tournament-filter--wide" type="button" data-tournament-filter="open" aria-pressed="false">Open reg</button>
        </div>
    </div>

    <h1 class="discovery-title">The court is open</h1>
    <p class="discovery-kicker">Upcoming tournaments</p>

    <div class="tournament-grid" data-tournament-grid>
        <?php foreach ($tournaments as $tournament): ?>
            <article
                class="tournament-card tournament-card--<?= e($tournament['accent']) ?> tournament-card--tilt-<?= e($tournament['tilt']) ?>"
                data-tournament-card
                data-region="<?= e($tournament['region']) ?>"
                data-registration="<?= e($tournament['registration']) ?>"
                data-search="<?= e(strtolower($tournament['title'] . ' ' . $tournament['meta'] . ' ' . $tournament['keywords'])) ?>"
            >
                <a class="tournament-card__link" href="/blacktop-takeover/tournament-details.php?event=<?= e($tournament['slug']) ?>">
                    <span class="tournament-card__art<?= isset($tournament['art_class']) ? ' tournament-card__art--' . e($tournament['art_class']) : '' ?>">
                        <?php if (isset($tournament['images'])): ?>
                            <?php foreach ($tournament['images'] as $image): ?>
                                <span class="tournament-card__art-panel">
                                    <img src="<?= e($image) ?>" alt="" aria-hidden="true">
                                </span>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <img src="<?= e($tournament['image']) ?>" alt="" aria-hidden="true">
                        <?php endif; ?>
                    </span>
                    <span class="tournament-card__stripe" aria-hidden="true"></span>
                    <span class="tournament-card__copy">
                        <strong><?= e($tournament['title']) ?></strong>
                        <small><?= e($tournament['meta']) ?></small>
                        <span class="tournament-card__action"><?= e($tournament['action']) ?></span>
                    </span>
                </a>
            </article>
        <?php endforeach; ?>
    </div>

    <p class="sr-only" aria-live="polite" data-filter-status>Showing <?= e((string) count($tournaments)) ?> tournaments.</p>

    <div class="street-tags" aria-hidden="true">
        <span class="street-tag street-tag--jozi">Jozi 011</span>
        <span class="street-tag street-tag--pitori">Pitori 012</span>
        <span class="street-tag street-tag--braam">Braam</span>
    </div>

    <aside class="dog-progression" aria-label="Blacktop championship progression">
        <div>
            <strong>COJ / COP <span>&rarr;</span> KON / KOS <span>&rarr;</span> D.O.G.</strong>
            <small>Duke of Gauteng &middot; The coveted final</small>
        </div>
        <span class="dog-medal" aria-hidden="true">
            <img src="/blacktop-takeover/assets/images/figma/dog-medal.svg" alt="">
            <b>DOG</b>
        </span>
    </aside>
</section>

<?php require __DIR__ . '/includes/court-menu.php'; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
