(function () {
    function applyFilter(value) {
        value = (value || '').trim().toUpperCase();
        document.querySelectorAll('.galeria-produto[data-visible]').forEach(el => {
            const visible = (el.getAttribute('data-visible') || '').trim().toUpperCase();
            el.style.display = !value || visible === value ? '' : 'none';
        });
    }

    function init() {
        const params = new URLSearchParams(window.location.search);
        let param = [...params.keys()].find(key => key.toUpperCase().endsWith('LN'));

        if (!param) {
            const selectEl = document.querySelector('select[id$="LN"]');
            if (selectEl) param = selectEl.id;
        }

        if (!param) param = 'QULN';

        const urlValue = params.get(param) || '';
        applyFilter(urlValue);

        const select = document.getElementById(param);
        if (select) {
            if (urlValue) select.value = urlValue.toUpperCase();
            select.addEventListener('change', () => applyFilter(select.value));
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => init());
    } else {
        init();
    }
})();
