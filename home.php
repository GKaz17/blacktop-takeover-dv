<?php
$pageTitle = 'Tournament Discovery';
$pageDescription = 'Discover upcoming Blacktop Takeover tournaments across Jozi and Pitori.';
$hideNavigation = true;
$bodyClass = 'takeover-home';
$courtMenuActive = 'discover';

$tournaments = [
    [
        'slug' => 'coj-summer-showdown',
        'region' => 'coj',
        'title' => 'COJ Summer Showdown',
        'meta' => 'Aug 14 / Jozi',
        'keywords' => 'Johannesburg 011',
        'action' => 'Open',
        'image' => '/blacktop-takeover/assets/images/figma/coj-summer-showdown.png',
        'accent' => 'orange',
        'tilt' => 'left',
    ],
    [
        'slug' => 'cop-regional-qualifier',
        'region' => 'cop',
        'title' => 'COP Regional Qualifier',
        'meta' => 'Aug 21 / PTA',
        'keywords' => 'Pretoria Pitori 012',
        'action' => 'Open',
        'image' => '/blacktop-takeover/assets/images/figma/cop-regional-qualifier.png',
        'accent' => 'blue',
        'tilt' => 'right',
    ],
    [
        'slug' => 'kon-kos-invitational',
        'region' => 'open',
        'title' => 'KON + KOS Invitational Qualifiers',
        'meta' => 'Braamfontein / Pitori - Two paths open',
        'keywords' => 'Johannesburg Jozi Pretoria PTA 011 012',
        'action' => 'Qualify now',
        'images' => [
            '/blacktop-takeover/assets/images/official/kos-basketball-poster.png',
            '/blacktop-takeover/assets/images/official/kon-basketball-poster.png',
        ],
        'art_class' => 'official-basketball',
        'accent' => 'gold',
        'tilt' => 'none',
    ],
];

require __DIR__ . '/includes/header.php';
?>
<section class="discovery-page" data-discovery>
    <img
        class="discovery-culture-layer"
        src="/blacktop-takeover/assets/images/figma/discovery-poster-wall.svg"
        alt=""
        aria-hidden="true"
    >

    <header class="discovery-header">
        <a class="discovery-wordmark" href="/blacktop-takeover/" aria-label="Blacktop Takeover landing page">
            Blacktop
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
                data-registration="open"
                data-search="<?= e(strtolower($tournament['title'] . ' ' . $tournament['meta'] . ' ' . $tournament['keywords'])) ?>"
            >
                <a class="tournament-card__link" href="/blacktop-takeover/tournament-details.php?event=<?= e($tournament['slug']) ?>">
                    <span class="tournament-card__art<?= isset($tournament['art_class']) ? ' tournament-card__art--' . e($tournament['art_class']) : '' ?>">
                        <?php if (isset($tournament['images'])): ?>
                            <?php foreach ($tournament['images'] as $image): ?>
                                <img src="<?= e($image) ?>" alt="" aria-hidden="true">
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

    <p class="sr-only" aria-live="polite" data-filter-status>Showing all three tournaments.</p>

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
