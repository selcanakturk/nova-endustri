const menuButton = document.querySelector('.menu-toggle');
const navigation = document.querySelector('#navigation');
if (menuButton && navigation) {
    menuButton.hidden = false;
    const closeMenu = () => {
        menuButton.setAttribute('aria-expanded', 'false');
        navigation.classList.remove('is-open');
    };
    menuButton.addEventListener('click', () => {
        const isExpanded = menuButton.getAttribute('aria-expanded') === 'true';
        menuButton.setAttribute('aria-expanded', String(!isExpanded));
        navigation.classList.toggle('is-open', !isExpanded);
    });
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && menuButton.getAttribute('aria-expanded') === 'true') {
            closeMenu();
            menuButton.focus();
        }
    });
    document.addEventListener('click', (event) => {
        if (!event.target.closest('.site-header')) {
            closeMenu();
        }
    });
    document.addEventListener('focusin', (event) => {
        if (!event.target.closest('.site-header')) closeMenu();
    });
    navigation.querySelectorAll('a').forEach((link) => link.addEventListener('click', closeMenu));
    window.matchMedia('(min-width: 1024px)').addEventListener('change', closeMenu);
}
