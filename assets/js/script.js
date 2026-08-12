const accessKey = 'blacktop-preview-access';
const loginForm = document.querySelector('[data-demo-login]');
const visitorSkip = document.querySelector('[data-visitor-skip]');

loginForm?.addEventListener('submit', (event) => {
    event.preventDefault();
    sessionStorage.setItem(accessKey, 'player');
    window.location.assign('/blacktop-takeover/home.php');
});

visitorSkip?.addEventListener('click', () => {
    sessionStorage.setItem(accessKey, 'visitor');
});

const discovery = document.querySelector('[data-discovery]');

if (discovery) {
    const searchInput = discovery.querySelector('[data-tournament-search]');
    const filterButtons = [...discovery.querySelectorAll('[data-tournament-filter]')];
    const tournamentCards = [...discovery.querySelectorAll('[data-tournament-card]')];
    const filterStatus = discovery.querySelector('[data-filter-status]');
    let activeFilter = 'all';

    const applyTournamentFilters = () => {
        const query = searchInput.value.trim().toLowerCase();
        let visibleCount = 0;

        tournamentCards.forEach((card) => {
            const matchesSearch = card.dataset.search.includes(query);
            const matchesRegion = activeFilter === 'all'
                || (activeFilter === 'open' && card.dataset.registration === 'open')
                || card.dataset.region === activeFilter;
            const isVisible = matchesSearch && matchesRegion;

            card.hidden = !isVisible;
            visibleCount += Number(isVisible);
        });

        filterStatus.textContent = visibleCount === 1
            ? 'Showing one tournament.'
            : `Showing ${visibleCount} tournaments.`;
    };

    filterButtons.forEach((button) => {
        button.addEventListener('click', () => {
            activeFilter = button.dataset.tournamentFilter;

            filterButtons.forEach((filterButton) => {
                const isActive = filterButton === button;
                filterButton.classList.toggle('is-active', isActive);
                filterButton.setAttribute('aria-pressed', String(isActive));
            });

            applyTournamentFilters();
        });
    });

    searchInput.addEventListener('input', applyTournamentFilters);
}

const courtMenuTrigger = document.querySelector('[data-court-menu-trigger]');
const courtMenu = document.querySelector('[data-court-menu]');
const courtMenuClose = document.querySelector('[data-court-menu-close]');

if (courtMenuTrigger && courtMenu && courtMenuClose) {
    const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');

    const openCourtMenu = () => {
        courtMenu.classList.add('is-open');
        courtMenu.setAttribute('aria-hidden', 'false');
        courtMenuTrigger.setAttribute('aria-expanded', 'true');
        document.body.classList.add('has-open-court-menu');
        courtMenuClose.focus();
    };

    const closeCourtMenu = () => {
        courtMenu.classList.remove('is-open');
        courtMenu.setAttribute('aria-hidden', 'true');
        courtMenuTrigger.setAttribute('aria-expanded', 'false');
        document.body.classList.remove('has-open-court-menu');
        courtMenuTrigger.focus();
    };

    courtMenuTrigger.addEventListener('click', () => {
        if (prefersReducedMotion.matches) {
            openCourtMenu();
            return;
        }

        courtMenuTrigger.classList.remove('is-bouncing');
        requestAnimationFrame(() => courtMenuTrigger.classList.add('is-bouncing'));
        window.setTimeout(() => {
            courtMenuTrigger.classList.remove('is-bouncing');
            openCourtMenu();
        }, 360);
    });

    courtMenuClose.addEventListener('click', closeCourtMenu);

    courtMenu.addEventListener('click', (event) => {
        if (event.target === courtMenu) {
            closeCourtMenu();
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && courtMenu.classList.contains('is-open')) {
            closeCourtMenu();
        }
    });
}

const teamDialog = document.querySelector('[data-team-dialog]');
const teamDialogOpen = document.querySelector('[data-team-dialog-open]');
const teamDialogClose = document.querySelector('[data-team-dialog-close]');

if (teamDialog && teamDialogOpen && teamDialogClose) {
    teamDialogOpen.addEventListener('click', () => teamDialog.showModal());
    teamDialogClose.addEventListener('click', () => teamDialog.close());

    teamDialog.addEventListener('click', (event) => {
        if (event.target === teamDialog) {
            teamDialog.close();
        }
    });
}
