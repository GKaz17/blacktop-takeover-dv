<?php
// Informational only: the provincial final never accepts public application POSTs.
if (!in_array($_SERVER['REQUEST_METHOD'] ?? 'GET', ['GET', 'HEAD'], true)) {
    http_response_code(405);
    header('Allow: GET, HEAD');
    header('Content-Type: text/plain; charset=utf-8');
    exit('D.O.G. registration is locked. Entry is reserved for the KON and KOS finalists.');
}

$pageTitle = 'D.O.G. / Duke of Gauteng';
$pageDescription = 'The qualification-only provincial final for KON and KOS finalists. D.O.G. public registration is locked.';
$hideNavigation = true;
$bodyClass = 'tournament-detail-page dog-final-page';
$courtMenuActive = 'tournaments';
$matchCentreEvent = 'kon-kos-invitational';
require __DIR__ . '/includes/header.php';
?>
<article class="event-detail dog-final">
    <img class="event-detail__mural" src="/blacktop-takeover/assets/images/figma/tournament-details-mural.svg" alt="" aria-hidden="true">

    <header class="screen-header">
        <a class="screen-wordmark street-wordmark" href="/blacktop-takeover/home.php"><?php require __DIR__ . '/includes/wordmark.php'; ?></a>
        <button class="court-menu-trigger" type="button" aria-label="Open Blacktop menu" aria-controls="court-menu" aria-expanded="false" data-court-menu-trigger>
            <img src="/blacktop-takeover/assets/images/ui/street-basketball.svg" alt="">
        </button>
    </header>

    <section class="event-hero dog-final__hero" aria-labelledby="dog-final-title">
        <div class="event-hero__copy">
            <p class="event-eyebrow">D.O.G. / The provincial final</p>
            <h1 id="dog-final-title">Duke of Gauteng</h1>
            <p class="event-meta">Jozi &times; Pitori <span>&middot;</span> One provincial title</p>
            <p class="event-summary">The KON and KOS finalists meet at D.O.G. for the right to call themselves Gauteng's best street-ball squad. The final stage is earned on the court, not through open registration.</p>
        </div>
        <div class="dog-final__emblem">
            <?php require __DIR__ . '/includes/dog-poster.php'; ?>
        </div>
    </section>

    <div class="event-detail__body">
        <div class="event-detail__main">
            <section class="event-facts" aria-label="D.O.G. entry facts">
                <div><strong>Locked</strong><span>Public registration</span></div>
                <div><strong>Earned</strong><span>Qualification only</span></div>
                <div><strong>KON/KOS</strong><span>Finalists advance</span></div>
                <div><strong>D.O.G.</strong><span>Provincial title</span></div>
            </section>

            <section class="event-information">
                <h2>The final is earned.</h2>
                <dl>
                    <div><dt>Who qualifies</dt><dd>The finalists from King of the North (KON) and King of the South (KOS).</dd></div>
                    <div><dt>Entry</dt><dd>Qualification only. There is no public sign-up or direct team application for D.O.G.</dd></div>
                    <div><dt>Confirmation</dt><dd>Organisers confirm the qualifying squads after the KON and KOS finals.</dd></div>
                    <div><dt>Date &amp; venue</dt><dd>To be announced. Confirmed details will be published before the provincial final.</dd></div>
                    <div><dt>The prize</dt><dd>The Duke of Gauteng title and recognition as the province's top street-ball squad.</dd></div>
                </dl>
            </section>

            <section class="dog-final__qualification" aria-labelledby="dog-paths-title">
                <h2 id="dog-paths-title">Two roads. One final stage.</h2>
                <div class="dog-final__paths">
                    <article>
                        <span>Northern route</span>
                        <h3>KON finalists</h3>
                        <p>The King of the North finalists earn their place at D.O.G. through the northern competition route.</p>
                    </article>
                    <article>
                        <span>Southern route</span>
                        <h3>KOS finalists</h3>
                        <p>The King of the South finalists earn their place at D.O.G. through the southern competition route.</p>
                    </article>
                </div>
            </section>
        </div>

        <aside class="event-application dog-final__access" aria-labelledby="dog-entry-title">
            <span class="dog-final__lock-state">
                <svg width="18" height="20" viewBox="0 0 18 20" fill="none" aria-hidden="true"><path d="M5 8V5a4 4 0 0 1 8 0v3M3 8h12v10H3z" stroke="currentColor" stroke-width="2"/><path d="M9 12v3" stroke="currentColor" stroke-width="2"/></svg>
                Qualification only
            </span>
            <h2 id="dog-entry-title">Registration locked</h2>
            <p>D.O.G. is reserved exclusively for the KON and KOS finalists. It is not an open-registration tournament.</p>
            <p>Build your squad, follow the qualifying routes and earn the right to play for the provincial crown.</p>
            <a class="dog-final__follow" href="/blacktop-takeover/match-centre.php?event=kon-kos-invitational">Follow KON + KOS</a>
            <a class="dog-final__events" href="/blacktop-takeover/tournaments.php">Explore qualifying events</a>
        </aside>
    </div>
</article>
<?php require __DIR__ . '/includes/court-menu.php'; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
