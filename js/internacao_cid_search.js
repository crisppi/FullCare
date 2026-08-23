(function () {
    'use strict';

    function buildCidSearch(select) {
        if (!select || select.dataset.cidSearchReady === '1') return;
        select.dataset.cidSearchReady = '1';
        var wrapper = document.createElement('div');
        wrapper.className = 'internacao-cid-search';
        var input = document.createElement('input');
        input.type = 'search';
        input.className = 'form-control internacao-cid-search__input';
        input.autocomplete = 'off';
        input.placeholder = select.id === 'fk_patologia2' ? 'Pesquise o antecedente' : 'Pesquise o CID';
        input.setAttribute('aria-label', input.placeholder);
        var results = document.createElement('div');
        results.className = 'internacao-cid-search__results';
        results.setAttribute('role', 'listbox');
        results.hidden = true;
        var selected = select.options[select.selectedIndex];
        if (select.value && selected) input.value = selected.textContent.trim();
        select.classList.add('internacao-cid-search__select');
        select.parentNode.insertBefore(wrapper, select);
        wrapper.appendChild(input);
        wrapper.appendChild(results);
        wrapper.appendChild(select);
        var timer = 0;
        var controller = null;

        function closeResults() {
            results.hidden = true;
            results.replaceChildren();
        }
        function choose(item) {
            select.replaceChildren(new Option('', ''), new Option(item.text, String(item.id), true, true));
            select.value = String(item.id);
            input.value = item.text;
            select.dispatchEvent(new Event('change', { bubbles: true }));
            closeResults();
        }
        function showResults(items) {
            results.replaceChildren();
            items.forEach(function (item) {
                var button = document.createElement('button');
                button.type = 'button';
                button.className = 'internacao-cid-search__option';
                button.textContent = item.text;
                button.setAttribute('role', 'option');
                button.addEventListener('mousedown', function (event) {
                    event.preventDefault();
                    choose(item);
                });
                results.appendChild(button);
            });
            results.hidden = items.length === 0;
        }
        input.addEventListener('input', function () {
            window.clearTimeout(timer);
            var term = input.value.trim();
            if (term.length < 2) {
                closeResults();
                return;
            }
            timer = window.setTimeout(function () {
                if (controller) controller.abort();
                controller = new AbortController();
                var url = (window.BASE_URL || './') + 'ajax_cid_search.php?q=' + encodeURIComponent(term);
                fetch(url, { credentials: 'same-origin', signal: controller.signal })
                    .then(function (response) {
                        if (!response.ok) throw new Error('Falha na busca de CID');
                        return response.json();
                    })
                    .then(function (payload) {
                        showResults(Array.isArray(payload.results) ? payload.results : []);
                    })
                    .catch(function (error) {
                        if (error.name !== 'AbortError') closeResults();
                    });
            }, 250);
        });
        input.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') closeResults();
        });
        input.addEventListener('blur', function () {
            window.setTimeout(closeResults, 120);
        });
        select.addEventListener('change', function () {
            if (!select.value) input.value = '';
        });
    }

    function initCidSearch() {
        document.querySelectorAll('.internacao-cid-select').forEach(buildCidSearch);
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initCidSearch, { once: true });
    } else {
        initCidSearch();
    }
})();
