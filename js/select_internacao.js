function bindInternacaoAdditionalTables() {
    const sections = [
        { selectId: 'select_tuss', containerId: 'container-tuss' },
        { selectId: 'select_prorrog', containerId: 'container-prorrog' },
        { selectId: 'select_gestao', containerId: 'container-gestao' },
        { selectId: 'select_uti', containerId: 'container-uti' },
        { selectId: 'select_negoc', containerId: 'container-negoc' }
    ];

    function styleSelect(select, active) {
        if (!select) return;
        const visibleButton = document.querySelector('.tabelas-selects .bootstrap-select > button.dropdown-toggle[data-id="' + select.id + '"]');
        const controls = [select, visibleButton].filter(Boolean);
        controls.forEach((control) => {
            ['border', 'background', 'background-color', 'color', 'font-weight', 'box-shadow'].forEach((property) => control.style.removeProperty(property));
            control.classList.toggle('additional-launcher-active', active);
        });

        if (visibleButton) {
            visibleButton.querySelectorAll('.filter-option, .filter-option-inner, .filter-option-inner-inner').forEach((node) => {
                node.style.removeProperty('color');
                node.style.removeProperty('font-weight');
            });
        }
    }

    function styleAllSelects() {
        sections.forEach((section) => {
            const select = document.getElementById(section.selectId);
            styleSelect(select, select && select.value === 's');
        });
    }
    window.fullcareRestyleAdditionalLaunchers = styleAllSelects;

    function setSection(section, show) {
        const container = document.getElementById(section.containerId);
        if (container) container.style.display = show ? 'block' : 'none';
    }

    function showOnly(activeSelectId) {
        sections.forEach((section) => {
            const select = document.getElementById(section.selectId);
            const show = section.selectId === activeSelectId && select && select.value === 's';
            styleSelect(select, show);
            setSection(section, show);
        });
    }

    sections.forEach((section) => {
        const select = document.getElementById(section.selectId);
        if (!select) return;
        select.addEventListener('change', () => showOnly(section.selectId));
        select.addEventListener('click', styleAllSelects);
    });

    const initiallyOpen = sections.find((section) => {
        const select = document.getElementById(section.selectId);
        return select && select.value === 's';
    });
    if (initiallyOpen) {
        showOnly(initiallyOpen.selectId);
    } else {
        sections.forEach((section) => setSection(section, false));
    }
    styleAllSelects();
    window.setTimeout(styleAllSelects, 100);
    window.setTimeout(styleAllSelects, 500);
}

document.addEventListener('DOMContentLoaded', bindInternacaoAdditionalTables);
