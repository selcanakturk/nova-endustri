const toggle = document.querySelector('.admin-menu-button');
const sidebar = document.querySelector('.admin-sidebar');
if (toggle && sidebar) {
    toggle.hidden = false;
    const closeMenu = () => toggle.setAttribute('aria-expanded', 'false');
    toggle.addEventListener('click', () => {
        toggle.setAttribute('aria-expanded', String(toggle.getAttribute('aria-expanded') !== 'true'));
    });
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && toggle.getAttribute('aria-expanded') === 'true') {
            closeMenu();
            toggle.focus();
        }
    });
    window.matchMedia('(min-width: 1024px)').addEventListener('change', closeMenu);
}

document.querySelectorAll('[data-image-field]').forEach((field) => {
    const input = field.querySelector('[data-image-input]');
    const preview = field.querySelector('[data-image-preview]');
    const originalSource = preview.src;
    let previewUrl;
    input.addEventListener('change', () => {
        if (previewUrl) URL.revokeObjectURL(previewUrl);
        input.setCustomValidity('');
        const file = input.files[0];
        if (!file) {
            preview.src = originalSource;
            return;
        }
        if (file.size > 4 * 1024 * 1024 || !['image/jpeg', 'image/png', 'image/webp'].includes(file.type)) {
            input.setCustomValidity('En fazla 4 MB boyutunda JPG, PNG veya WebP seçin.');
            input.reportValidity();
            preview.src = originalSource;
            return;
        }
        previewUrl = URL.createObjectURL(file);
        preview.src = previewUrl;
        preview.alt = 'Seçilen yeni görselin önizlemesi';
    });
});
