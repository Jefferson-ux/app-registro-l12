(function () {
    if (window.themeLoaderInstalled) {
        return;
    }

    window.themeLoaderInstalled = true;

    const VISIBLE_MS = 600;
    const root = document.documentElement;

    function init() {
        const overlay = document.createElement('div');
        overlay.className = 'theme-loader';
        overlay.setAttribute('aria-hidden', 'true');
        const block = function (cls) {
            return '<div class="tl-block ' + cls + '"></div>';
        };

        overlay.innerHTML =
            '<div class="tl-layout">' +
                '<div class="tl-sidebar">' +
                    block('tl-logo') +
                    block('tl-item') +
                    block('tl-item tl-item--short') +
                    block('tl-item') +
                    block('tl-item tl-item--short') +
                    block('tl-item') +
                '</div>' +
                '<div class="tl-content">' +
                    block('tl-topbar') +
                    '<div class="tl-row">' +
                        block('tl-card') +
                        block('tl-card') +
                        block('tl-card') +
                    '</div>' +
                    block('tl-table') +
                '</div>' +
            '</div>';
        document.body.appendChild(overlay);

        let wasDark = root.classList.contains('dark');
        let timer = null;

        new MutationObserver(function () {
            const isDark = root.classList.contains('dark');

            if (isDark === wasDark) {
                return;
            }

            wasDark = isDark;
            overlay.classList.add('is-visible');

            clearTimeout(timer);
            timer = setTimeout(function () {
                overlay.classList.remove('is-visible');
            }, VISIBLE_MS);
        }).observe(root, { attributes: true, attributeFilter: ['class'] });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();