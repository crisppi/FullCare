document.addEventListener('DOMContentLoaded', function () {
    const attachAutocomplete = ({
        inputId,
        hiddenId,
        datalistId,
        endpoint,
        formatDisplay,
        idField = 'id',
        minChars = 2
    }) => {
        const input = document.getElementById(inputId);
        const hidden = hiddenId ? document.getElementById(hiddenId) : null;
        const datalist = document.getElementById(datalistId);
        if (!input || !datalist || !endpoint) {
            return;
        }

        const cache = new Map();
        let timer;

        const fetchOptions = (query) => {
            const url = `${endpoint}?q=${encodeURIComponent(query)}`;
            return fetch(url, { credentials: 'include' })
                .then((resp) => resp.ok ? resp.json() : [])
                .catch(() => []);
        };

        const renderOptions = (items) => {
            datalist.innerHTML = items.map((item) => {
                const display = formatDisplay(item);
                cache.set(display, item);
                return `<option value="${display}">`;
            }).join('');
        };

        input.addEventListener('input', () => {
            if (hidden) {
                hidden.value = '';
            }
            const value = input.value.trim();
            if (value.length < minChars) {
                datalist.innerHTML = '';
                cache.clear();
                return;
            }
            clearTimeout(timer);
            timer = setTimeout(() => {
                fetchOptions(value).then(renderOptions);
            }, 200);
        });

        input.addEventListener('change', () => {
            const selected = input.value.trim();
            if (cache.has(selected)) {
                const item = cache.get(selected);
                if (hidden) {
                    hidden.value = item[idField] ?? '';
                }
            } else if (hidden) {
                hidden.value = '';
            }
        });
    };

    attachAutocomplete({
        inputId: 'pesquisa_paciente_input',
        hiddenId: 'pesquisa_paciente_id',
        datalistId: 'pacienteSuggestions',
        endpoint: 'ajax/pacientes_search.php',
        formatDisplay: (item) => {
            const parts = [item.nome || 'Paciente sem nome'];
            if (item.matricula) parts.push(item.matricula);
            if (item.cpf) parts.push(item.cpf);
            return parts.join(' • ');
        },
        idField: 'id_paciente'
    });

    attachAutocomplete({
        inputId: 'pesquisa_seguradora_input',
        hiddenId: 'seguradora_id',
        datalistId: 'seguradoraSuggestions',
        endpoint: 'ajax/seguradoras_search.php',
        formatDisplay: (item) => item.label || '',
        idField: 'id'
    });
});
