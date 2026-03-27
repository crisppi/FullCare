(function(window, document) {
    'use strict';

    var chronicMatchers = [{
            key: 'hipertensao',
            label: 'Hipertensão arterial',
            patterns: [/\bhipertens[aã]o\b/i, /\bhas\b/i, /\bpress[aã]o alta\b/i]
        },
        {
            key: 'diabetes',
            label: 'Diabetes mellitus',
            patterns: [/\bdiabetes\b/i, /\bdm\b/i, /\bdm1\b/i, /\bdm2\b/i]
        },
        {
            key: 'dpoc',
            label: 'DPOC',
            patterns: [/\bdpoc\b/i, /\bdoen[cç]a pulmonar obstrutiva cr[oô]nica\b/i]
        },
        {
            key: 'asma',
            label: 'Asma',
            patterns: [/\basma\b/i, /\basm[aá]tico\b/i]
        },
        {
            key: 'obesidade',
            label: 'Obesidade',
            patterns: [/\bobesidade\b/i, /\bobeso\b/i, /\bimc\s*(?:>|maior que)\s*30\b/i]
        },
        {
            key: 'insuficiencia_cardiaca',
            label: 'Insuficiência cardíaca',
            patterns: [/\binsufici[eê]ncia card[ií]aca\b/i, /\bicc\b/i, /\bfrac[aã]o de eje[cç][aã]o reduzida\b/i]
        },
        {
            key: 'doenca_renal_cronica',
            label: 'Doença renal crônica',
            patterns: [/\bdoen[cç]a renal cr[oô]nica\b/i, /\bdrc\b/i, /\binsufici[eê]ncia renal cr[oô]nica\b/i]
        },
        {
            key: 'coronariopatia',
            label: 'Coronariopatia',
            patterns: [/\bcoronariopatia\b/i, /\bdoen[cç]a arterial coronariana\b/i, /\bdac\b/i]
        },
        {
            key: 'avc_previo',
            label: 'AVC prévio',
            patterns: [/\bavc\b/i, /\bacidente vascular cerebral\b/i]
        }
    ];

    function uniqueLabels(matches) {
        var seen = {};
        return matches.filter(function(item) {
            if (seen[item.label]) {
                return false;
            }
            seen[item.label] = true;
            return true;
        }).map(function(item) {
            return item.label;
        });
    }

    function detectConditions(text) {
        var source = String(text || '');
        if (!source.trim()) {
            return [];
        }
        return chronicMatchers.filter(function(entry) {
            return entry.patterns.some(function(pattern) {
                return pattern.test(source);
            });
        });
    }

    function initInternacaoCronicosAlert() {
        var relatorioField = document.getElementById('rel_int') || document.getElementById('rel_visita_vis');
        var alertBox = document.getElementById('cronicos-relatorio-alert');
        if (!relatorioField || !alertBox) {
            return;
        }

        var matchedList = alertBox.querySelector('[data-role="matched-list"]');
        var managementSelect = document.getElementById('select_gestao');
        var form = relatorioField.closest('form');
        var autoFlag = false;

        function updateAlert() {
            var matches = uniqueLabels(detectConditions(relatorioField.value));
            if (!matches.length) {
                alertBox.style.display = 'none';
                alertBox.setAttribute('hidden', 'hidden');
                if (matchedList) {
                    matchedList.textContent = '';
                }
                autoFlag = false;
                return;
            }

            if (matchedList) {
                matchedList.textContent = matches.join(', ');
            }

            if (managementSelect && managementSelect.value === '') {
                managementSelect.value = 's';
                autoFlag = true;
                if (window.jQuery) {
                    window.jQuery(managementSelect).trigger('change');
                } else {
                    managementSelect.dispatchEvent(new Event('change', {
                        bubbles: true
                    }));
                }
            }

            var note = alertBox.querySelector('[data-role="auto-note"]');
            if (note) {
                if (managementSelect && managementSelect.value === 'n') {
                    note.textContent = 'Os termos foram detectados, mas Gestão Assistencial está marcada como "Não".';
                } else if (autoFlag) {
                    note.textContent = 'Gestão Assistencial foi marcada automaticamente para apoiar o seguimento de crônicos.';
                } else {
                    note.textContent = 'Revise o caso e confirme o encaminhamento para Gestão de Crônicos.';
                }
            }

            alertBox.style.display = 'block';
            alertBox.removeAttribute('hidden');
        }

        relatorioField.addEventListener('input', updateAlert);
        relatorioField.addEventListener('change', updateAlert);
        if (managementSelect) {
            managementSelect.addEventListener('change', updateAlert);
        }
        if (form) {
            form.addEventListener('submit', function() {
                alertBox.style.display = 'none';
                alertBox.setAttribute('hidden', 'hidden');
            });
        }
        updateAlert();
    }

    document.addEventListener('DOMContentLoaded', initInternacaoCronicosAlert);
})(window, document);
