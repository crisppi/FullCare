(() => {
    'use strict';
    const decision = document.getElementById('hcx-decision');
    const fields = document.getElementById('hcx-plan-fields');
    if (!decision || !fields) return;
    function update() {
        const denied = decision.value === 'negada';
        fields.hidden = denied;
        fields.disabled = denied;
    }
    decision.addEventListener('change', update);
    update();
})();
