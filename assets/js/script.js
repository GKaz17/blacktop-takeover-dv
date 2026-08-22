const accessKey = 'blacktop-preview-access';
const visitorSkip = document.querySelector('[data-visitor-skip]');

visitorSkip?.addEventListener('click', () => {
    sessionStorage.setItem(accessKey, 'visitor');
});

const registrationForm = document.querySelector('[data-registration-form]');

if (registrationForm) {
    const roleInputs = [...registrationForm.querySelectorAll('[data-registration-role]')];
    const roleFieldsets = [...registrationForm.querySelectorAll('[data-role-fields]')];
    const captainRequiredFields = [...registrationForm.querySelectorAll('[data-captain-required]')];

    const updateRegistrationRole = () => {
        const selectedRole = roleInputs.find((input) => input.checked)?.value ?? 'player';

        roleFieldsets.forEach((fieldset) => {
            const isSelected = fieldset.dataset.roleFields === selectedRole;
            fieldset.hidden = !isSelected;
            fieldset.classList.toggle('is-active', isSelected);
        });

        captainRequiredFields.forEach((field) => {
            field.required = selectedRole === 'captain';
        });
    };

    roleInputs.forEach((input) => input.addEventListener('change', updateRegistrationRole));
    updateRegistrationRole();
}

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

const matchTabs = [...document.querySelectorAll('[data-match-tab]')];
const matchPanels = [...document.querySelectorAll('[data-match-panel]')];

if (matchTabs.length && matchPanels.length) {
    const selectMatchView = (selectedTab) => {
        const selectedView = selectedTab.dataset.matchTab;

        matchTabs.forEach((tab) => {
            const isSelected = tab === selectedTab;
            tab.setAttribute('aria-selected', String(isSelected));
            tab.tabIndex = isSelected ? 0 : -1;
        });

        matchPanels.forEach((panel) => {
            const isSelected = panel.dataset.matchPanel === selectedView;
            panel.hidden = !isSelected;
            panel.classList.toggle('is-active', isSelected);
        });
    };

    matchTabs.forEach((tab, index) => {
        tab.addEventListener('click', () => selectMatchView(tab));
        tab.addEventListener('keydown', (event) => {
            if (!['ArrowLeft', 'ArrowRight'].includes(event.key)) return;

            event.preventDefault();
            const direction = event.key === 'ArrowRight' ? 1 : -1;
            const nextIndex = (index + direction + matchTabs.length) % matchTabs.length;
            matchTabs[nextIndex].focus();
            selectMatchView(matchTabs[nextIndex]);
        });
    });
}

const approvalDialog = document.querySelector('[data-approval-dialog]');
const approvalReviewButtons = [...document.querySelectorAll('[data-approval-review]')];
const approvalDialogClose = document.querySelector('[data-approval-close]');

if (approvalDialog && approvalReviewButtons.length && approvalDialogClose) {
    const approvalTeam = approvalDialog.querySelector('[data-approval-team]');
    const approvalEvent = approvalDialog.querySelector('[data-approval-event]');
    const approvalCaptain = approvalDialog.querySelector('[data-approval-captain]');
    const approvalRoster = approvalDialog.querySelector('[data-approval-roster]');
    const approvalTournamentId = approvalDialog.querySelector('[data-approval-tournament-id]');
    const approvalTeamId = approvalDialog.querySelector('[data-approval-team-id]');

    approvalReviewButtons.forEach((button) => {
        button.addEventListener('click', () => {
            approvalTeam.textContent = button.dataset.teamName;
            approvalEvent.textContent = button.dataset.teamEvent;
            approvalCaptain.textContent = button.dataset.teamCaptain;
            approvalRoster.textContent = button.dataset.teamRoster;
            approvalTournamentId.value = button.dataset.tournamentId;
            approvalTeamId.value = button.dataset.teamId;
            approvalDialog.showModal();
        });
    });

    approvalDialogClose.addEventListener('click', () => approvalDialog.close());
    approvalDialog.addEventListener('click', (event) => {
        if (event.target === approvalDialog) approvalDialog.close();
    });
}

const fixtureDateInput = document.querySelector('[data-fixture-date]');
const fixtureDateOpen = document.querySelector('[data-fixture-date-open]');
const fixtureTimeInput = document.querySelector('[data-fixture-time]');
const fixtureTimeOpen = document.querySelector('[data-fixture-time-open]');

const connectNativePicker = (input, button) => {
    if (!input || !button) return;

    button.addEventListener('click', () => {
        input.focus();

        if (typeof input.showPicker === 'function') {
            input.showPicker();
        }
    });
};

connectNativePicker(fixtureDateInput, fixtureDateOpen);
connectNativePicker(fixtureTimeInput, fixtureTimeOpen);

const fixtureTournament = document.querySelector('[data-fixture-tournament]');
const fixtureHomeTeam = document.querySelector('[data-fixture-home-team]');
const fixtureAwayTeam = document.querySelector('[data-fixture-away-team]');
const fixtureWarning = document.querySelector('[data-fixture-warning]');
const fixtureSubmit = document.querySelector('[data-fixture-submit]');

if (fixtureTournament && fixtureHomeTeam && fixtureAwayTeam && fixtureWarning && fixtureSubmit) {
    const teamOptions = (select) => [...select.options].filter((option) => option.value !== '');

    const refreshFixtureTeams = () => {
        const tournamentId = fixtureTournament.value;

        [fixtureHomeTeam, fixtureAwayTeam].forEach((select) => {
            teamOptions(select).forEach((option) => {
                const belongsToTournament = tournamentId !== '' && option.dataset.tournamentId === tournamentId;
                option.disabled = !belongsToTournament;
                option.hidden = !belongsToTournament;
            });

            if (select.selectedOptions[0]?.disabled) {
                select.value = '';
            }
        });

        if (fixtureHomeTeam.value !== '' && fixtureHomeTeam.value === fixtureAwayTeam.value) {
            fixtureAwayTeam.value = '';
        }

        teamOptions(fixtureAwayTeam).forEach((option) => {
            if (option.dataset.tournamentId === tournamentId) {
                option.disabled = option.value === fixtureHomeTeam.value;
            }
        });

        teamOptions(fixtureHomeTeam).forEach((option) => {
            if (option.dataset.tournamentId === tournamentId) {
                option.disabled = option.value === fixtureAwayTeam.value;
            }
        });

        const eligibleCount = teamOptions(fixtureHomeTeam)
            .filter((option) => option.dataset.tournamentId === tournamentId)
            .length;

        if (tournamentId === '') {
            fixtureWarning.textContent = 'Select a tournament to load its approved teams.';
        } else if (eligibleCount < 2) {
            fixtureWarning.textContent = `This tournament currently has ${eligibleCount} approved team${eligibleCount === 1 ? '' : 's'}. Approve at least two before creating a fixture.`;
        } else {
            fixtureWarning.textContent = 'Choose two different approved teams, then set the fixture date and time.';
        }

        fixtureSubmit.disabled = eligibleCount < 2;
    };

    fixtureTournament.addEventListener('change', refreshFixtureTeams);
    fixtureHomeTeam.addEventListener('change', refreshFixtureTeams);
    fixtureAwayTeam.addEventListener('change', refreshFixtureTeams);
    refreshFixtureTeams();
}
