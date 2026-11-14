const toggle = document.querySelector('.admin-menu-button');
const sidebar = document.querySelector('.admin-sidebar');
if (toggle && sidebar) {
    toggle.hidden = false;
    const closeMenu = () => toggle.setAttribute('aria-expanded', 'false');
    toggle.addEventListener('click', () => {
        const isExpanded = toggle.getAttribute('aria-expanded') !== 'true';
        toggle.setAttribute('aria-expanded', String(isExpanded));
        if (isExpanded) sidebar.querySelector('nav a')?.focus();
    });
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && toggle.getAttribute('aria-expanded') === 'true') {
            closeMenu();
            toggle.focus();
        }
    });
    document.addEventListener('click', (event) => {
        if (!event.target.closest('.admin-sidebar, .admin-menu-button')) closeMenu();
    });
    document.addEventListener('focusin', (event) => {
        if (!event.target.closest('.admin-sidebar, .admin-menu-button')) closeMenu();
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


document.querySelectorAll('[data-markdown-editor]').forEach((editor) => {
    const textarea = editor.querySelector('textarea');
    const preview = editor.querySelector('#markdown-preview');
    const status = editor.querySelector('[data-preview-status]');
    const previewButton = editor.querySelector('[data-markdown-preview]');
    editor.querySelector('.admin-editor-toolbar').hidden = false;
    editor.querySelectorAll('[data-markdown-command]').forEach((button) => {
        button.addEventListener('click', () => {
            const start = textarea.selectionStart;
            const end = textarea.selectionEnd;
            const selected = textarea.value.slice(start, end) || 'Metniniz';
            const command = button.dataset.markdownCommand;
            const inline = { bold: ['**', '**'], italic: ['*', '*'], link: ['[', '](https://example.com)'] };
            let replacement;
            if (inline[command]) {
                replacement = inline[command][0] + selected + inline[command][1];
            } else {
                const prefix = { h2: '## ', h3: '### ', ul: '- ', quote: '> ' };
                replacement = selected.split('\n').map((line, index) => (command === 'ol' ? `${index + 1}. ` : prefix[command]) + line).join('\n');
                if (start > 0 && textarea.value[start - 1] !== '\n') replacement = '\n' + replacement;
                replacement += '\n';
            }
            textarea.setRangeText(replacement, start, end, 'select');
            textarea.focus();
            textarea.dispatchEvent(new Event('input', { bubbles: true }));
            preview.hidden = true;
            previewButton.setAttribute('aria-expanded', 'false');
        });
    });
    previewButton.addEventListener('click', async () => {
        previewButton.disabled = true;
        status.textContent = 'Önizleme hazırlanıyor…';
        try {
            const response = await fetch(editor.dataset.previewUrl, {
                method: 'POST', headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': editor.closest('form').querySelector('[name="_token"]').value },
                body: JSON.stringify({ content: textarea.value }),
            });
            if (!response.ok) throw new Error('preview');
            const data = await response.json();
            preview.innerHTML = data.html;
            preview.hidden = false;
            previewButton.setAttribute('aria-expanded', 'true');
            status.textContent = 'Önizleme güncellendi. İçerik henüz kaydedilmedi.';
        } catch {
            status.textContent = 'Önizleme hazırlanamadı. İçeriği ve oturumunuzu kontrol edin.';
        } finally {
            previewButton.disabled = false;
        }
    });
    textarea.addEventListener('input', () => {
        if (!preview.hidden) status.textContent = 'İçerik değişti. Güncel görünüm için yeniden önizleyin.';
    });
});
