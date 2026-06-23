(function () {
    function ready(fn) {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', fn);
        } else {
            fn();
        }
    }

    function byId(id) {
        return document.getElementById(id);
    }

    function esc(value) {
        return String(value || '').replace(/[&<>"']/g, function (ch) {
            return {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            }[ch];
        });
    }

    function cleanText(value) {
        return String(value || '')
            .replace(/\r/g, '\n')
            .replace(/[ \t]+/g, ' ')
            .replace(/\n{3,}/g, '\n\n')
            .trim();
    }

    function readField(ids) {
        for (var i = 0; i < ids.length; i += 1) {
            var field = byId(ids[i]);
            if (field && cleanText(field.value)) {
                return cleanText(field.value);
            }
        }
        return '';
    }

    function openPanel() {
        var body = byId('prorrog-ia-body');
        var toggle = byId('btn-toggle-prorrog-ia');
        if (body) body.hidden = false;
        if (toggle) {
            toggle.setAttribute('aria-expanded', 'true');
            var icon = toggle.querySelector('i');
            if (icon) {
                icon.classList.remove('bi-chevron-down');
                icon.classList.add('bi-chevron-up');
            }
        }
    }

    function setStatus(message, type) {
        var status = byId('prorrog-ia-status');
        if (!status) return;
        status.hidden = false;
        status.textContent = message;
        status.classList.remove(
            'prorrog-ia-status--info',
            'prorrog-ia-status--success',
            'prorrog-ia-status--error'
        );
        status.classList.add('prorrog-ia-status--' + (type || 'info'));
    }

    function badge(label, type) {
        return '<span class="prorrog-ia-badge prorrog-ia-badge--' + esc(type || 'info') + '">' + esc(label) + '</span>';
    }

    function list(items) {
        if (!items.length) return '<p>Nenhum sinal objetivo foi identificado automaticamente.</p>';
        return '<ul>' + items.map(function (item) {
            return '<li>' + esc(item) + '</li>';
        }).join('') + '</ul>';
    }

    function findSignals(text) {
        var rules = [
            ['instabilidade clinica ou hemodinamica', /instabilidade|hemodin[aâ]mica|choque|hipotens|dva|noradrenalina|vasopressor/i],
            ['suporte ventilatorio ou respiratorio', /ventila[cç][aã]o|intuba|vm\b|oxig[eê]nio|cateter|mascara|dispneia|satura/i],
            ['infeccao ou sepse em acompanhamento', /sepse|s[eé]ptico|infec[cç][aã]o|antibi[oó]tico|febre|pneumonia/i],
            ['pendencia de exame, procedimento ou avaliacao', /aguarda|pend[eê]ncia|exame|procedimento|avalia[cç][aã]o|parecer|resultado/i],
            ['necessidade de reabilitacao ou cuidados continuados', /fisio|fono|reabilita|curativo|dieta|sonda|home care|desospitaliza/i],
            ['barreira de alta ou transicao de cuidado', /barreira|alta|famil|domic|medica[cç][aã]o|oxigenoterapia|equipamento/i]
        ];
        var signals = [];
        rules.forEach(function (rule) {
            if (rule[1].test(text)) signals.push(rule[0]);
        });
        return signals;
    }

    function buildOpinion() {
        var relatorio = readField(['rel_int', 'rel_visita_vis']);
        var acoes = readField(['acoes_int', 'acoes_int_vis']);
        var programacao = readField(['programacao_int', 'programacao_enf']);
        var acomodacao = readField(['acomodacao_int', 'acomod1_pror']);
        var contexto = readField(['prorrog-ia-contexto']);
        var text = cleanText([relatorio, acoes, programacao, acomodacao, contexto].join('\n\n'));
        var signals = findSignals(text);
        var hasContent = text.length >= 20;
        var highRisk = signals.length >= 3;
        var mediumRisk = signals.length >= 1;
        var statusLabel = highRisk ? 'Prorrogacao sustentada' : (mediumRisk ? 'Prorrogacao a revisar' : 'Dados insuficientes');
        var statusType = highRisk ? 'ok' : (mediumRisk ? 'warn' : 'neutral');

        return {
            hasContent: hasContent,
            statusLabel: statusLabel,
            statusType: statusType,
            signals: signals,
            summary: hasContent
                ? 'A avaliacao automatica usa os textos preenchidos na auditoria, a programacao terapeutica e o contexto complementar.'
                : 'Preencha o relatorio, as acoes, a programacao ou o contexto complementar antes de executar a IA.',
            recommendation: highRisk
                ? 'Ha elementos que justificam continuidade assistencial, desde que estejam documentados no relatorio e alinhados ao periodo solicitado.'
                : (mediumRisk
                    ? 'Existem sinais clinicos, mas o parecer precisa de documentacao mais objetiva para sustentar a prorrogacao.'
                    : 'Nao ha informacao suficiente para sustentar a prorrogacao automaticamente.')
        };
    }

    function render(opinion) {
        var content = byId('prorrog-ia-content');
        if (!content) return;
        content.innerHTML = [
            '<div class="prorrog-ia-result-head">',
            badge(opinion.statusLabel, opinion.statusType),
            '</div>',
            '<div class="prorrog-ia-section">',
            '<strong>Resumo</strong>',
            '<p>' + esc(opinion.summary) + '</p>',
            '</div>',
            '<div class="prorrog-ia-section">',
            '<strong>Sinais encontrados</strong>',
            list(opinion.signals),
            '</div>',
            '<div class="prorrog-ia-section">',
            '<strong>Recomendacao</strong>',
            '<p>' + esc(opinion.recommendation) + '</p>',
            '</div>',
            opinion.hasContent ? '' : '<div class="prorrog-ia-final-alert">Inclua informacoes clinicas antes de usar este parecer.</div>'
        ].join('');
    }

    ready(function () {
        var button = byId('btn-executar-prorrog-ia');
        var toggle = byId('btn-toggle-prorrog-ia');
        var body = byId('prorrog-ia-body');

        if (toggle && body) {
            toggle.addEventListener('click', function () {
                var expanded = toggle.getAttribute('aria-expanded') === 'true';
                body.hidden = expanded;
                toggle.setAttribute('aria-expanded', expanded ? 'false' : 'true');
                var icon = toggle.querySelector('i');
                if (icon) {
                    icon.classList.toggle('bi-chevron-down', expanded);
                    icon.classList.toggle('bi-chevron-up', !expanded);
                }
            });
        }

        if (!button) return;

        button.addEventListener('click', function () {
            var originalHtml = button.innerHTML;
            button.disabled = true;
            button.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Analisando...';
            openPanel();
            setStatus('Gerando parecer de prorrogacao...', 'info');

            window.setTimeout(function () {
                var opinion = buildOpinion();
                render(opinion);
                setStatus(
                    opinion.hasContent ? 'Parecer gerado. Revise antes de salvar.' : 'Inclua dados clinicos para gerar um parecer util.',
                    opinion.hasContent ? 'success' : 'error'
                );
                button.disabled = false;
                button.innerHTML = originalHtml;
            }, 120);
        });
    });
})();
