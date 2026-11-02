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
    navigation.querySelectorAll('a').forEach((link) => link.addEventListener('click', closeMenu));
    window.matchMedia('(min-width: 1024px)').addEventListener('change', closeMenu);
}

const catalog = document.querySelector('[data-catalog]');
if (catalog) {
    catalog.querySelector('.filters').hidden = false;
    const cards = [...catalog.querySelectorAll('[data-category]')];
    catalog.querySelectorAll('[data-filter]').forEach((button) => {
        button.addEventListener('click', () => {
            catalog.querySelectorAll('[data-filter]').forEach((filter) => {
                filter.setAttribute('aria-pressed', String(filter === button));
            });
            cards.forEach((card) => {
                card.hidden = button.dataset.filter !== 'all' && card.dataset.category !== button.dataset.filter;
            });
            catalog.querySelector('[data-product-count]').textContent = `${cards.filter((card) => !card.hidden).length} ürün grubu gösteriliyor.`;
        });
    });
}
