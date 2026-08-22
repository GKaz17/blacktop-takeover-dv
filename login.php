<?php
session_start();

require_once __DIR__ . '/config/connection.php';

if (isset($_GET['logout'])) {
    unset($_SESSION['user_id'], $_SESSION['user_name'], $_SESSION['user_email'], $_SESSION['user_role']);
    session_regenerate_id(true);
    header('Location: /blacktop-takeover/login.php');
    exit;
}

$registrationSuccess = (string) ($_SESSION['registration_success'] ?? '');
$registeredEmail = (string) ($_SESSION['registered_email'] ?? '');
unset($_SESSION['registration_success'], $_SESSION['registered_email']);

$loginError = '';
$loginEmail = $registeredEmail;

if (empty($_SESSION['login_csrf'])) {
    $_SESSION['login_csrf'] = bin2hex(random_bytes(32));
}

$loginCsrfToken = $_SESSION['login_csrf'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $loginEmail = strtolower(trim((string) ($_POST['email'] ?? '')));
    $password = (string) ($_POST['password'] ?? '');
    $submittedToken = (string) ($_POST['csrf_token'] ?? '');

    if (!hash_equals($loginCsrfToken, $submittedToken)) {
        $loginError = 'Your sign-in session expired. Refresh the page and try again.';
    } elseif (!filter_var($loginEmail, FILTER_VALIDATE_EMAIL) || $password === '') {
        $loginError = 'Enter a valid email and password.';
    } else {
        $userLookup = $conn->prepare(
            'SELECT id, first_name, last_name, email, password_hash, role FROM users WHERE email = ? LIMIT 1'
        );
        $userLookup->bind_param('s', $loginEmail);
        $userLookup->execute();
        $user = $userLookup->get_result()->fetch_assoc();
        $userLookup->close();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            $loginError = 'Email or password is incorrect.';
        } else {
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int) $user['id'];
            $_SESSION['user_name'] = trim($user['first_name'] . ' ' . $user['last_name']);
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['login_csrf'] = bin2hex(random_bytes(32));

            header('Location: /blacktop-takeover/home.php');
            exit;
        }
    }
}

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

            <?php if ($registrationSuccess !== ''): ?>
                <div class="registration-feedback registration-feedback--success" role="status">
                    <?= e($registrationSuccess) ?>
                </div>
            <?php endif; ?>

            <?php if ($loginError !== ''): ?>
                <div class="registration-feedback registration-feedback--error" role="alert">
                    <?= e($loginError) ?>
                </div>
            <?php endif; ?>

            <form method="post" action="/blacktop-takeover/login.php">
                <input type="hidden" name="csrf_token" value="<?= e($loginCsrfToken) ?>">
                <label>Email<input type="email" name="email" value="<?= e($loginEmail) ?>" autocomplete="email" required></label>
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
