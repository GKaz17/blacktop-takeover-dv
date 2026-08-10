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
