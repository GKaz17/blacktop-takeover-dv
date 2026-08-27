<?php
$courtMenuActive = $courtMenuActive ?? '';
$currentRole = $_SESSION['user_role'] ?? null;
$hasSquadAccess = in_array($currentRole, ['player', 'captain'], true);
$hasOrganiserAccess = in_array($currentRole, ['organiser', 'admin'], true);
$matchCentreHref = '/blacktop-takeover/match-centre.php';
if (!empty($matchCentreEvent)) {
    $matchCentreHref .= '?event=' . rawurlencode((string) $matchCentreEvent);
}
?>
<div class="court-menu" id="court-menu" aria-hidden="true" data-court-menu>
    <div class="court-menu__paint" aria-hidden="true"></div>
    <img class="court-menu__texture court-menu__texture--tar" src="/blacktop-takeover/assets/images/textures/grit-worn-tar.png" alt="" aria-hidden="true">
    <img class="court-menu__texture court-menu__texture--crack" src="/blacktop-takeover/assets/images/textures/grit-offcentre-crack.png" alt="" aria-hidden="true">
    <img class="court-menu__texture court-menu__texture--impact" src="/blacktop-takeover/assets/images/textures/grit-impact-ring.png" alt="" aria-hidden="true">
    <button class="court-menu__close" type="button" aria-label="Close Blacktop menu" data-court-menu-close>
        <img src="/blacktop-takeover/assets/images/figma/navigation-basketball-trigger.svg" alt="">
    </button>
    <nav class="court-menu__links" aria-label="Blacktop pages">
        <a href="/blacktop-takeover/home.php"<?= $courtMenuActive === 'discover' ? ' aria-current="page"' : '' ?>><span>01</span> Discover</a>
        <a href="/blacktop-takeover/tournaments.php"<?= $courtMenuActive === 'tournaments' ? ' aria-current="page"' : '' ?>><span>02</span> Tournaments</a>
        <?php if ($hasSquadAccess): ?>
            <a href="/blacktop-takeover/team.php"<?= $courtMenuActive === 'team' ? ' aria-current="page"' : '' ?>><span>03</span> My squad</a>
        <?php endif; ?>
        <a href="<?= e($matchCentreHref) ?>"<?= $courtMenuActive === 'matches' ? ' aria-current="page"' : '' ?>><span>04</span> Match centre</a>
        <a href="/blacktop-takeover/about.php"<?= $courtMenuActive === 'about' ? ' aria-current="page"' : '' ?>><span>05</span> About the movement</a>
        <?php if ($hasOrganiserAccess): ?>
            <a href="/blacktop-takeover/admin.php"<?= $courtMenuActive === 'organiser' ? ' aria-current="page"' : '' ?>><span>06</span> Organiser</a>
        <?php endif; ?>
        <a href="/blacktop-takeover/login.php<?= $currentRole !== null ? '?logout=1' : '' ?>"><span>07</span> <?= $currentRole !== null ? 'Sign out' : 'Switch access' ?></a>
    </nav>
    <p class="court-menu__route">COJ / COP &rarr; KON / KOS &rarr; D.O.G.</p>
</div>
