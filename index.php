<?php
$pageTitle = 'Blacktop Takeover';
$pageDescription = 'Blacktop Takeover - Jozi and Pitori street sport.';
$hideNavigation = true;
$bodyClass = 'landing-page';
require __DIR__ . '/includes/header.php';
?>
<section class="landing-shell" data-figma-node="48:3">
    <div class="culture-panel" aria-hidden="true">
        <img class="culture-fill" src="/blacktop-takeover/assets/images/figma/login-mural.svg" alt="">
        <!-- BUILDING VECTOR NOTE:
             This skyline is a temporary stylised landmark layer. Replace it with vectors
             traced from properly licensed, authentic Jozi/PTA high-rise photographs when
             those references are ready; keep the resulting artwork decorative in the UI. -->
        <img class="skyline" src="/blacktop-takeover/assets/images/figma/jozi-landmarks.svg" alt="">
        <!-- END BUILDING VECTOR NOTE -->
        <div class="brand-lockup">
            <strong>BLACKTOP</strong>
            <span>TAKEOVER</span>
            <small>JOZI &times; PTA STREET-SPORT SYSTEM</small>
        </div>
        <p class="paint-mark paint-mark--011">011</p>
        <p class="paint-mark paint-mark--pitori">PITORI</p>
        <p class="paint-mark paint-mark--streets">STREETS &gt; SCREENS</p>
    </div>
    <div class="landing-entry">
        <img class="culture-fill" src="/blacktop-takeover/assets/images/figma/pta-night-wash.svg" alt="" aria-hidden="true">
        <div class="landing-cta">
            <p>BLACKTOP TAKEOVER</p>
            <h1>ENTER THE<br>TAKEOVER</h1>
            <a class="takeover-button" href="/blacktop-takeover/login.php">Enter the court</a>
        </div>
    </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
