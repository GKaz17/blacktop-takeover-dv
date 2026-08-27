<?php
$pageTitle = 'About the Movement';
$pageDescription = 'Blacktop Takeover is a Gauteng street-basketball movement built from Jozi and Pitori courts, culture and community.';
$hideNavigation = true;
$bodyClass = 'about-page';
$courtMenuActive = 'about';

require __DIR__ . '/includes/header.php';
?>
<article class="about-movement">
    <img class="about-texture about-texture--tar" src="/blacktop-takeover/assets/images/textures/grit-worn-tar.png" alt="" aria-hidden="true">
    <img class="about-texture about-texture--brush" src="/blacktop-takeover/assets/images/textures/grit-broad-brush.png" alt="" aria-hidden="true">
    <img class="about-texture about-texture--impact" src="/blacktop-takeover/assets/images/textures/grit-impact-ring.png" alt="" aria-hidden="true">
    <img class="about-texture about-texture--halftone" src="/blacktop-takeover/assets/images/textures/grit-halftone-waves.png" alt="" aria-hidden="true">
    <img class="about-texture about-texture--collage" src="/blacktop-takeover/assets/images/textures/grit-collage-strip.png" alt="" aria-hidden="true">

    <header class="screen-header">
        <a class="screen-wordmark" href="/blacktop-takeover/home.php">Blacktop Takeover</a>
        <button class="court-menu-trigger" type="button" aria-label="Open Blacktop menu" aria-controls="court-menu" aria-expanded="false" data-court-menu-trigger>
            <img src="/blacktop-takeover/assets/images/figma/navigation-basketball-trigger.svg" alt="">
        </button>
    </header>

    <section class="about-hero" aria-labelledby="about-title">
        <div class="about-hero__copy">
            <p class="about-kicker">011 × 012 · Made on the tar</p>
            <h1 id="about-title">More than a tournament.</h1>
            <p class="about-lead">Blacktop Takeover is a Gauteng street-basketball movement built between Jozi and Pitori. It turns outdoor courts into places to compete, gather, represent a block and be seen.</p>
            <p>The games are the heartbeat, but the movement belongs to everything around them: the people on the fence, the local sound, the painted walls, the rivalries and the squads that keep returning. This is basketball shaped here, for the people by the people.</p>
        </div>

        <blockquote class="about-statement">
            <span>Our court.</span>
            <strong>Our culture.</strong>
            <b>Our takeover.</b>
            <small>Streets &gt; screens</small>
        </blockquote>
    </section>

    <section class="about-pillars" aria-labelledby="about-pillars-title">
        <div class="about-section-heading">
            <p>What Blacktop stands for</p>
            <h2 id="about-pillars-title">Built from the ground up</h2>
        </div>

        <div class="about-pillars__grid">
            <article>
                <span>01</span>
                <h3>A movement</h3>
                <p>A season people can follow and grow with, not a once-off bracket that disappears after the final whistle.</p>
            </article>
            <article>
                <span>02</span>
                <h3>A culture</h3>
                <p>Local sound, murals, city pride and outdoor-court energy give every stop its own identity without losing the shared Blacktop language.</p>
            </article>
            <article>
                <span>03</span>
                <h3>A community</h3>
                <p>Players bring the skill, captains build the squads, organisers hold the court together and spectators give every game its weight.</p>
            </article>
        </div>
    </section>

    <section class="about-ladder" aria-labelledby="ladder-title">
        <div class="about-section-heading about-section-heading--ladder">
            <p>The Gauteng climb</p>
            <h2 id="ladder-title">Every court leads somewhere</h2>
        </div>

        <ol class="about-ladder__steps">
            <li class="about-ladder__step about-ladder__step--city">
                <span>Stage 01</span>
                <strong>COJ / COP</strong>
                <h3>Claim the city</h3>
                <p>Chief of Jozi and Chief of Pitori are the regional proving grounds where squads first earn their name.</p>
            </li>
            <li class="about-ladder__step about-ladder__step--kingdom">
                <span>Stage 02</span>
                <strong>KOS / KON</strong>
                <h3>Take the crown</h3>
                <p>COJ feeds the King of the South route. COP feeds the King of the North route. The level rises and the field gets smaller.</p>
            </li>
            <li class="about-ladder__step about-ladder__step--dog">
                <span>Final stage</span>
                <strong>D.O.G.</strong>
                <h3>Own Gauteng</h3>
                <p>Duke of Gauteng brings the strongest paths together for the province’s top street-ball title. It is the final word on who owns the tar.</p>
            </li>
        </ol>
    </section>

    <section class="about-close" aria-labelledby="about-close-title">
        <p>From a local run to the provincial crown</p>
        <h2 id="about-close-title">The road starts on your court.</h2>
        <div>
            <a href="/blacktop-takeover/tournaments.php">Find a tournament</a>
            <a href="/blacktop-takeover/match-centre.php">Follow the games</a>
        </div>
    </section>
</article>

<?php require __DIR__ . '/includes/court-menu.php'; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
