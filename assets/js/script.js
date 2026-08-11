const accessKey = 'blacktop-preview-access';
const loginForm = document.querySelector('[data-demo-login]');
const visitorSkip = document.querySelector('[data-visitor-skip]');
const homeAccess = document.querySelector('[data-home-access]');

loginForm?.addEventListener('submit', (event) => {
    event.preventDefault();
    sessionStorage.setItem(accessKey, 'player');
    window.location.assign('/blacktop-takeover/home.php');
});

visitorSkip?.addEventListener('click', () => {
    sessionStorage.setItem(accessKey, 'visitor');
});

if (homeAccess) {
    const accessState = sessionStorage.getItem(accessKey);
    homeAccess.textContent = accessState === 'player'
        ? 'PLAYER PREVIEW'
        : 'VISITOR PREVIEW';
}
