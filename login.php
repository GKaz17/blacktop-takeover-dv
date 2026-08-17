<?php
$pageTitle = 'Enter the Takeover';
$hideNavigation = true;
$bodyClass = 'auth-page';
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
        <div class="brand-lockup"><strong>BLACKTOP</strong><span>TAKEOVER</span><small>JOZI &times; PTA STREET-SPORT SYSTEM</small></div>
        <p class="paint-mark paint-mark--011">011</p>
        <p class="paint-mark paint-mark--pitori">PITORI</p>
        <p class="paint-mark paint-mark--streets">STREETS &gt; SCREENS</p>
    </div>
    <div class="landing-entry">
        <img class="culture-fill" src="/blacktop-takeover/assets/images/figma/pta-night-wash.svg" alt="" aria-hidden="true">
        <section class="auth-panel">
            <h1>ENTER THE TAKEOVER</h1>
            <form method="post" action="#" data-demo-login>
                <label>Email<input type="email" name="email" autocomplete="email" required></label>
                <label>Password<input type="password" name="password" autocomplete="current-password" required></label>
                <button class="takeover-button" type="submit">Enter the court</button>
            </form>
            <div class="auth-actions">
                <a class="register-link" href="/blacktop-takeover/register.php">Create account</a>
                <a class="visitor-link" href="/blacktop-takeover/home.php" data-visitor-skip>Skip sign-up · continue as visitor</a>
                <a class="back-link" href="/blacktop-takeover/">Back</a>
            </div>
        </section>
    </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
