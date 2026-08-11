<?php
$pageTitle = 'Home';
$pageDescription = 'Blacktop Takeover preview home.';
$hideNavigation = true;
$bodyClass = 'takeover-home';
require __DIR__ . '/includes/header.php';
?>
<section class="home-preview">
    <img class="home-night-layer" src="/blacktop-takeover/assets/images/figma/pta-night-wash.svg" alt="" aria-hidden="true">

    <header class="home-header">
        <a class="home-wordmark" href="/blacktop-takeover/" aria-label="Blacktop Takeover landing page">
            BLACKTOP <span>TAKEOVER</span>
        </a>
        <p class="home-access" data-home-access>PREVIEW ACCESS</p>
    </header>

    <div class="home-hero">
        <div class="home-copy">
            <p class="home-eyebrow">JOZI &times; PTA STREET-SPORT SYSTEM</p>
            <h1>THE ROAD<br><em>TO D.O.G.</em></h1>
            <p class="home-route">COJ / COP <span>&rarr;</span> KON / KOS <span>&rarr;</span> D.O.G.</p>
            <a class="home-secondary-action" href="/blacktop-takeover/login.php">Switch access</a>
        </div>

        <div class="home-signal" aria-hidden="true">
            <!-- BUILDING VECTOR NOTE:
                 Replace this temporary skyline with hand-traced vectors made from properly
                 licensed, authentic Jozi/PTA high-rise photographs when those images are ready. -->
            <img src="/blacktop-takeover/assets/images/figma/jozi-landmarks.svg" alt="">
            <!-- END BUILDING VECTOR NOTE -->
            <strong>011 &times; 012</strong>
            <span>STREETS &gt; SCREENS</span>
        </div>
    </div>

</section>

<!-- HOMEPAGE PROGRESS NOTE:
     The current implementation intentionally stops after the hero and regional pathway
     preview. Add fixtures, tournament discovery and account modules in later phases.
     Keep implementation progress in comments/docs; never render percentages or build
     status messaging in the public UI. -->
<section class="home-teaser" aria-labelledby="regional-runs-title">
    <header>
        <h2 id="regional-runs-title">REGIONAL RUNS</h2>
    </header>
    <div class="run-teasers">
        <article class="run-teaser run-teaser--jozi">
            <span>011 / JOZI</span>
            <h3>COJ</h3>
            <p>REGIONAL PATHWAY</p>
        </article>
        <article class="run-teaser run-teaser--pta">
            <span>012 / PITORI</span>
            <h3>COP</h3>
            <p>REGIONAL PATHWAY</p>
        </article>
    </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
