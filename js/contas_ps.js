(() => {
    'use strict';
    const form = document.getElementById('ps-account-form');
    if (!form) return;
    const mode = document.getElementById('ps-mode');
    const packageInput = document.getElementById('ps-package');
    const rows = Array.from(form.querySelectorAll('[data-block]'));
    const readonly = form.querySelector('fieldset').disabled;
    const parse = value => {
        value = value.trim();
        if (value === '') return 0;
        if (!/^\d{1,9}(?:[.,]\d{1,2})?$/.test(value)) return NaN;
        const [whole, fraction = ''] = value.replace(',', '.').split('.');
        return Number(whole) * 100 + Number(fraction.padEnd(2, '0'));
    };
    const money = value => Number.isFinite(value) ? (value / 100).toLocaleString('pt-BR', {style:'currency',currency:'BRL'}) : 'Confira os valores';
    function totals() {
        let charged = 0, denied = 0;
        rows.forEach(row => {
            if (row.hidden) return;
            const chargeInput = row.querySelector('[data-charged]');
            const deniedInput = row.querySelector('[data-denied]');
            const charge = parse(chargeInput.value), glosa = parse(deniedInput.value);
            charged += charge; denied += glosa;
            chargeInput.setCustomValidity(Number.isFinite(charge) ? '' : 'Informe um valor válido, sem separador de milhar.');
            deniedInput.setCustomValidity(!Number.isFinite(glosa) ? 'Informe um valor válido.' : glosa > charge ? 'A glosa não pode superar a cobrança do bloco.' : '');
            row.querySelector('[data-reason]').required = glosa > 0;
            row.querySelector('[data-approved]').textContent = money(charge - glosa);
        });
        document.getElementById('ps-charged').textContent = money(charged);
        document.getElementById('ps-denied').textContent = money(denied);
        document.getElementById('ps-approved').textContent = money(charged - denied);
    }
    function packageMode() {
        const isPackage = mode.value === 'pacote';
        document.getElementById('ps-package-label').hidden = !isPackage;
        packageInput.required = isPackage;
        const row = rows.find(row => row.dataset.block === 'pacote');
        row.hidden = !isPackage;
        row.querySelectorAll('input,select,textarea').forEach(input => { input.disabled = !isPackage || readonly; });
        totals();
    }
    form.addEventListener('input', totals);
    mode.addEventListener('change', packageMode);
    packageMode();
})();
