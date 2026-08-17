<?php
$pageTitle = 'Join the Takeover';
$pageDescription = 'Create a Blacktop Takeover player or captain account.';
$hideNavigation = true;
$bodyClass = 'registration-page';
require __DIR__ . '/includes/header.php';
?>
<section class="landing-shell registration-shell">
    <div class="culture-panel" aria-hidden="true">
        <img class="culture-fill" src="/blacktop-takeover/assets/images/figma/login-mural.svg" alt="">
        <!-- BUILDING VECTOR NOTE:
             Replace this temporary landmark layer with vectors traced from licensed,
             authentic Jozi/PTA high-rise photographs when the references are ready. -->
        <img class="skyline" src="/blacktop-takeover/assets/images/figma/jozi-landmarks.svg" alt="">
        <div class="brand-lockup registration-brand">
            <strong>JOIN THE</strong>
            <span>TAKEOVER</span>
            <small>PLAYER OR CAPTAIN · CHOOSE YOUR ROUTE</small>
        </div>
        <p class="paint-mark paint-mark--011">011</p>
        <p class="paint-mark paint-mark--pitori">012</p>
        <p class="paint-mark paint-mark--streets">YOUR TEAM STARTS HERE</p>
    </div>

    <div class="landing-entry registration-entry">
        <img class="culture-fill" src="/blacktop-takeover/assets/images/figma/pta-night-wash.svg" alt="" aria-hidden="true">
        <section class="registration-panel">
            <p class="registration-kicker">Create your court access</p>
            <h1>Choose your role</h1>

            <form method="post" action="/blacktop-takeover/register.php" data-registration-form>
                <fieldset class="role-selector">
                    <legend>How are you joining?</legend>
                    <div class="role-options">
                        <label class="role-option">
                            <input type="radio" name="role" value="player" checked data-registration-role>
                            <span><strong>Player</strong><small>Join a squad through an invitation.</small></span>
                        </label>
                        <label class="role-option">
                            <input type="radio" name="role" value="captain" data-registration-role>
                            <span><strong>Captain</strong><small>Create a squad and manage its roster.</small></span>
                        </label>
                    </div>
                </fieldset>

                <div class="registration-fields registration-fields--split">
                    <label>First name<input type="text" name="first_name" autocomplete="given-name" maxlength="80" required></label>
                    <label>Last name<input type="text" name="last_name" autocomplete="family-name" maxlength="80" required></label>
                </div>

                <label>Email<input type="email" name="email" autocomplete="email" maxlength="190" required></label>
                <label>Password<input type="password" name="password" autocomplete="new-password" minlength="8" required></label>

                <div class="registration-role-fields is-active" data-role-fields="player">
                    <label>Team invitation code <small>Optional — you can join a team later</small>
                        <input type="text" name="invite_code" maxlength="40" autocomplete="off">
                    </label>
                </div>

                <div class="registration-role-fields" data-role-fields="captain" hidden>
                    <div class="registration-fields registration-fields--split">
                        <label>Team name<input type="text" name="team_name" maxlength="100" data-captain-required></label>
                        <label>Home city<input type="text" name="team_city" maxlength="80" data-captain-required></label>
                    </div>
                </div>

                <!-- MySQL handoff: validate a CSRF token, hash the password, insert the
                     user, then create the captain team or attach the player invitation. -->
                <button class="takeover-button" type="submit">Create account</button>
            </form>

            <div class="registration-actions">
                <a href="/blacktop-takeover/login.php">Already have access? Sign in</a>
                <a href="/blacktop-takeover/home.php">Continue as visitor</a>
            </div>
        </section>
    </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
