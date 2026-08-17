<?php
$courtMenuActive = $courtMenuActive ?? '';
?>
<div class="court-menu" id="court-menu" aria-hidden="true" data-court-menu>
    <div class="court-menu__paint" aria-hidden="true"></div>
    <button class="court-menu__close" type="button" aria-label="Close Blacktop menu" data-court-menu-close>
        <img src="/blacktop-takeover/assets/images/figma/navigation-basketball-trigger.svg" alt="">
    </button>
    <nav class="court-menu__links" aria-label="Blacktop pages">
        <a href="/blacktop-takeover/home.php"<?= $courtMenuActive === 'discover' ? ' aria-current="page"' : '' ?>><span>01</span> Discover</a>
        <a href="/blacktop-takeover/tournaments.php"<?= $courtMenuActive === 'tournaments' ? ' aria-current="page"' : '' ?>><span>02</span> Tournaments</a>
        <a href="/blacktop-takeover/team.php"<?= $courtMenuActive === 'team' ? ' aria-current="page"' : '' ?>><span>03</span> My squad</a>
        <a href="/blacktop-takeover/match-centre.php"<?= $courtMenuActive === 'matches' ? ' aria-current="page"' : '' ?>><span>04</span> Match centre</a>
        <a href="/blacktop-takeover/admin.php"<?= $courtMenuActive === 'organiser' ? ' aria-current="page"' : '' ?>><span>05</span> Organiser</a>
        <a href="/blacktop-takeover/login.php"><span>06</span> Switch access</a>
    </nav>
    <p class="court-menu__route">COJ / COP &rarr; KON / KOS &rarr; D.O.G.</p>
</div>
