<?php

session_start();

require_once __DIR__ . '/config/connection.php';

function registrationTextLength(string $value): int
{
    return function_exists('mb_strlen') ? mb_strlen($value) : strlen($value);
}

function registrationTeamSlug(string $teamName): string
{
    $slug = strtolower((string) preg_replace('/[^a-z0-9]+/i', '-', $teamName));
    $slug = trim($slug, '-');

    return ($slug !== '' ? $slug : 'team') . '-' . bin2hex(random_bytes(3));
}

$errors = [];
$form = [
    'role' => 'player',
    'first_name' => '',
    'last_name' => '',
    'email' => '',
    'invite_code' => '',
    'team_name' => '',
    'team_city' => '',
];

if (empty($_SESSION['registration_csrf'])) {
    $_SESSION['registration_csrf'] = bin2hex(random_bytes(32));
}

$csrfToken = $_SESSION['registration_csrf'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach (array_keys($form) as $field) {
        $form[$field] = trim((string) ($_POST[$field] ?? ''));
    }

    $password = (string) ($_POST['password'] ?? '');
    $submittedToken = (string) ($_POST['csrf_token'] ?? '');
    $form['email'] = strtolower($form['email']);
    $form['invite_code'] = strtoupper($form['invite_code']);

    if (!hash_equals($csrfToken, $submittedToken)) {
        $errors[] = 'Your form session expired. Refresh the page and try again.';
    }

    if (!in_array($form['role'], ['player', 'captain'], true)) {
        $errors[] = 'Choose either player or captain.';
    }

    if ($form['first_name'] === '' || registrationTextLength($form['first_name']) > 80) {
        $errors[] = 'Enter a first name no longer than 80 characters.';
    }

    if ($form['last_name'] === '' || registrationTextLength($form['last_name']) > 80) {
        $errors[] = 'Enter a last name no longer than 80 characters.';
    }

    if (!filter_var($form['email'], FILTER_VALIDATE_EMAIL) || strlen($form['email']) > 190) {
        $errors[] = 'Enter a valid email address.';
    }

    if (strlen($password) < 8) {
        $errors[] = 'Use a password with at least eight characters.';
    }

    if ($form['role'] === 'captain') {
        if ($form['team_name'] === '' || registrationTextLength($form['team_name']) > 100) {
            $errors[] = 'Enter a team name no longer than 100 characters.';
        }

        if ($form['team_city'] === '' || registrationTextLength($form['team_city']) > 80) {
            $errors[] = 'Enter a home city no longer than 80 characters.';
        }
    }

    $invitedTeamId = null;

    if ($errors === []) {
        $emailCheck = $conn->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        $emailCheck->bind_param('s', $form['email']);
        $emailCheck->execute();

        if ($emailCheck->get_result()->fetch_assoc()) {
            $errors[] = 'An account already exists for that email address.';
        }
        $emailCheck->close();
    }

    if ($errors === [] && $form['role'] === 'captain') {
        $teamCheck = $conn->prepare('SELECT id FROM teams WHERE name = ? LIMIT 1');
        $teamCheck->bind_param('s', $form['team_name']);
        $teamCheck->execute();

        if ($teamCheck->get_result()->fetch_assoc()) {
            $errors[] = 'That team name is already registered.';
        }
        $teamCheck->close();
    }

    if ($errors === [] && $form['role'] === 'player' && $form['invite_code'] !== '') {
        $inviteCheck = $conn->prepare('SELECT id FROM teams WHERE invite_code = ? LIMIT 1');
        $inviteCheck->bind_param('s', $form['invite_code']);
        $inviteCheck->execute();
        $invitedTeam = $inviteCheck->get_result()->fetch_assoc();
        $inviteCheck->close();

        if (!$invitedTeam) {
            $errors[] = 'That team invitation code could not be found.';
        } else {
            $invitedTeamId = (int) $invitedTeam['id'];
        }
    }

    if ($errors === []) {
        try {
            $conn->begin_transaction();

            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            $userInsert = $conn->prepare(
                'INSERT INTO users (first_name, last_name, email, password_hash, role) VALUES (?, ?, ?, ?, ?)'
            );
            $userInsert->bind_param(
                'sssss',
                $form['first_name'],
                $form['last_name'],
                $form['email'],
                $passwordHash,
                $form['role']
            );
            $userInsert->execute();
            $userId = $conn->insert_id;
            $userInsert->close();

            if ($form['role'] === 'captain') {
                $teamSlug = registrationTeamSlug($form['team_name']);
                $inviteCode = 'BT-' . strtoupper(bin2hex(random_bytes(4)));
                $teamInsert = $conn->prepare(
                    'INSERT INTO teams (name, slug, city, captain_id, invite_code) VALUES (?, ?, ?, ?, ?)'
                );
                $teamInsert->bind_param(
                    'sssis',
                    $form['team_name'],
                    $teamSlug,
                    $form['team_city'],
                    $userId,
                    $inviteCode
                );
                $teamInsert->execute();
                $teamId = $conn->insert_id;
                $teamInsert->close();

                $captainPosition = 'Captain';
                $activeStatus = 'active';
                $memberInsert = $conn->prepare(
                    'INSERT INTO team_members (team_id, user_id, position, status, joined_at) VALUES (?, ?, ?, ?, NOW())'
                );
                $memberInsert->bind_param('iiss', $teamId, $userId, $captainPosition, $activeStatus);
                $memberInsert->execute();
                $memberInsert->close();
            } elseif ($invitedTeamId !== null) {
                $activeStatus = 'active';
                $memberInsert = $conn->prepare(
                    'INSERT INTO team_members (team_id, user_id, status, joined_at) VALUES (?, ?, ?, NOW())'
                );
                $memberInsert->bind_param('iis', $invitedTeamId, $userId, $activeStatus);
                $memberInsert->execute();
                $memberInsert->close();
            }

            $conn->commit();

            $_SESSION['registration_success'] = 'Account created. Sign in to enter the court.';
            $_SESSION['registered_email'] = $form['email'];
            $_SESSION['registration_csrf'] = bin2hex(random_bytes(32));
            header('Location: /blacktop-takeover/login.php?registered=1');
            exit;
        } catch (Throwable $error) {
            $conn->rollback();
            error_log('Registration failed: ' . $error->getMessage());
            $errors[] = 'We could not create the account. Please try again.';
        }
    }
}

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
        <p class="paint-mark paint-mark--streets">STREETS &gt; SCREENS</p>
    </div>

    <div class="landing-entry registration-entry">
        <img class="culture-fill" src="/blacktop-takeover/assets/images/figma/pta-night-wash.svg" alt="" aria-hidden="true">
        <section class="registration-panel">
            <p class="registration-kicker">Create your court access</p>
            <h1>Choose your role</h1>

            <?php if ($errors !== []): ?>
                <div class="registration-feedback registration-feedback--error" role="alert">
                    <strong>Check your details</strong>
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?= e($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="post" action="/blacktop-takeover/register.php" data-registration-form>
                <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                <fieldset class="role-selector">
                    <legend>How are you joining?</legend>
                    <div class="role-options">
                        <label class="role-option">
                            <input type="radio" name="role" value="player"<?= $form['role'] === 'player' ? ' checked' : '' ?> data-registration-role>
                            <span><strong>Player</strong><small>Join a squad through an invitation.</small></span>
                        </label>
                        <label class="role-option">
                            <input type="radio" name="role" value="captain"<?= $form['role'] === 'captain' ? ' checked' : '' ?> data-registration-role>
                            <span><strong>Captain</strong><small>Create a squad and manage its roster.</small></span>
                        </label>
                    </div>
                </fieldset>

                <div class="registration-fields registration-fields--split">
                    <label>First name<input type="text" name="first_name" value="<?= e($form['first_name']) ?>" autocomplete="given-name" maxlength="80" required></label>
                    <label>Last name<input type="text" name="last_name" value="<?= e($form['last_name']) ?>" autocomplete="family-name" maxlength="80" required></label>
                </div>

                <label>Email<input type="email" name="email" value="<?= e($form['email']) ?>" autocomplete="email" maxlength="190" required></label>
                <label>Password<input type="password" name="password" autocomplete="new-password" minlength="8" required></label>

                <div class="registration-role-fields is-active" data-role-fields="player">
                    <label>Team invitation code <small>Optional — you can join a team later</small>
                        <input type="text" name="invite_code" value="<?= e($form['invite_code']) ?>" maxlength="40" autocomplete="off">
                    </label>
                </div>

                <div class="registration-role-fields" data-role-fields="captain" hidden>
                    <div class="registration-fields registration-fields--split">
                        <label>Team name<input type="text" name="team_name" value="<?= e($form['team_name']) ?>" maxlength="100" data-captain-required></label>
                        <label>Home city<input type="text" name="team_city" value="<?= e($form['team_city']) ?>" maxlength="80" data-captain-required></label>
                    </div>
                </div>

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
