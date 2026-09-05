<?php
include_once("check_logado.php");
require_once("templates/header.php");
require_once("ajax/_auth_scope.php");
require_once("app/services/InternacaoChartService.php");

if (!isset($conn) || !($conn instanceof PDO)) {
    die("Conexão não disponível.");
}

$ctx = ajax_user_context($conn);
$chartService = new InternacaoChartService($conn);
$hospitais = $chartService->listHospitals($ctx);
?>

<link rel="stylesheet" href="<?= htmlspecialchars($BASE_URL, ENT_QUOTES, 'UTF-8') ?>css/inteligencia_graficos.css?v=<?= filemtime(__DIR__ . '/css/inteligencia_graficos.css') ?>">

<div class="ai-chart-page">
    <div class="fc-module-header fc-module-header--inteligencia">
        <div class="fc-module-header__copy">
            <p class="fc-module-header__kicker">Inteligência Operacional</p>
            <h1 class="fc-module-header__title">IA Gráficos</h1>
            <p class="fc-module-header__subtitle">Crie gráficos operacionais a partir de perguntas sobre saving, negociações, internações, hospitais, seguradoras, permanência, visitas, UTI e eventos.</p>
        </div>
        <div class="fc-module-header__actions">
            <a class="btn btn-light btn-sm" href="<?= htmlspecialchars($BASE_URL . 'inteligencia/assistente-internacoes', ENT_QUOTES, 'UTF-8') ?>">
                <i class="bi bi-chat-dots"></i> IA de Internações
            </a>
        </div>
    </div>

    <div class="ai-chart-shell">
        <aside class="ai-chart-panel ai-chart-sidebar">
            <h2>Exemplos</h2>
            <div class="ai-chart-suggestions">
                <button type="button" class="ai-chart-suggestion" data-question="Crie um gráfico de saving por hospital">Saving por hospital</button>
                <button type="button" class="ai-chart-suggestion" data-question="Mostre saving por auditor">Saving por auditor</button>
                <button type="button" class="ai-chart-suggestion" data-question="Mostre a evolução mensal do saving">Evolução do saving</button>
                <button type="button" class="ai-chart-suggestion" data-question="Mostre saving por tipo de negociação">Saving por tipo</button>
                <button type="button" class="ai-chart-suggestion" data-question="Mostre faturamento por hospital">Faturamento</button>
                <button type="button" class="ai-chart-suggestion" data-question="Mostre evolução mensal da glosa">Glosa mensal</button>
                <button type="button" class="ai-chart-suggestion" data-question="Mostre contas abertas por hospital">Contas abertas</button>
                <button type="button" class="ai-chart-suggestion" data-question="Mostre eventos adversos por tipo">Eventos adversos</button>
                <button type="button" class="ai-chart-suggestion" data-question="Crie um gráfico de internações por hospital">Internações por hospital</button>
                <button type="button" class="ai-chart-suggestion" data-question="Mostre a evolução mensal das internações">Evolução mensal</button>
                <button type="button" class="ai-chart-suggestion" data-question="Faça um gráfico de visitas em atraso por hospital">Visitas em atraso</button>
                <button type="button" class="ai-chart-suggestion" data-question="Mostre longa permanência por hospital">Longa permanência</button>
                <button type="button" class="ai-chart-suggestion" data-question="Compare internações por seguradora">Por seguradora</button>
            </div>
        </aside>

        <main class="ai-chart-main">
            <section class="ai-chart-panel">
                <form id="aiChartForm" class="ai-chart-prompt">
                    <textarea id="aiChartQuestion" placeholder="Ex.: crie um gráfico de saving por hospital nos últimos 180 dias" required></textarea>
                    <button class="ai-chart-submit" type="submit" title="Gerar gráfico"><i class="bi bi-stars"></i></button>
                    <button id="aiChartClear" class="ai-chart-clear" type="button" title="Limpar conteúdo"><i class="bi bi-x-lg"></i></button>
                </form>
                <div class="ai-chart-stage">
                    <div class="ai-chart-stage-head">
                        <h2 id="aiChartTitle" class="ai-chart-stage-title">Gráfico sob demanda</h2>
                        <span id="aiChartMeta" class="ai-chart-stage-meta">Aguardando pedido</span>
                    </div>
                    <div id="aiChartEmpty" class="ai-chart-empty">Digite um pedido ou escolha um exemplo para gerar o gráfico.</div>
                    <div id="aiChartWrap" class="ai-chart-canvas-wrap" style="display:none;">
                        <canvas id="aiChartCanvas"></canvas>
                    </div>
                </div>
            </section>

            <section class="ai-chart-panel ai-chart-insight">
                <h2>Leitura da IA</h2>
                <div class="ai-chart-insight-meta">
                    <span class="ai-chart-insight-speaker">FullCare - IA</span>
                    <span id="aiChartInsightTime" class="ai-chart-insight-time"><?= htmlspecialchars(date('d/m/Y H:i'), ENT_QUOTES, 'UTF-8') ?></span>
                </div>
                <p id="aiChartInsight" class="ai-chart-insight-text">A interpretação aparecerá após gerar o gráfico.</p>
            </section>

            <section class="ai-chart-panel ai-chart-table">
                <h2>Dados usados</h2>
                <div id="aiChartTable">Nenhum dado carregado ainda.</div>
            </section>
        </main>
    </div>
</div>

<script>
(function() {
    const endpoint = <?= json_encode($BASE_URL . 'ajax/internacao_chart_ai.php') ?>;
    const form = document.getElementById('aiChartForm');
    const input = document.getElementById('aiChartQuestion');
    const title = document.getElementById('aiChartTitle');
    const meta = document.getElementById('aiChartMeta');
    const insight = document.getElementById('aiChartInsight');
    const insightTime = document.getElementById('aiChartInsightTime');
    const table = document.getElementById('aiChartTable');
    const empty = document.getElementById('aiChartEmpty');
    const wrap = document.getElementById('aiChartWrap');
    const canvas = document.getElementById('aiChartCanvas');
    const clearBtn = document.getElementById('aiChartClear');
    let chartInstance = null;

    function filters() {
        return {
            hospital_id: '',
            status: 'todos',
            days: 180
        };
    }

    function palette(count) {
        const colors = ['#2f6f9f', '#1aa58d', '#5e3db8', '#f59e0b', '#d94b67', '#0ea5e9', '#7c3aed', '#20a37a', '#ef7d34', '#2563eb', '#b85ab5', '#64748b'];
        return Array.from({length: count}, function(_, idx) {
            return colors[idx % colors.length];
        });
    }

    function hexToRgba(hex, alpha) {
        const clean = String(hex || '#2f6f9f').replace('#', '');
        const value = clean.length === 3
            ? clean.split('').map(function(ch) { return ch + ch; }).join('')
            : clean;
        const intVal = parseInt(value, 16);
        const r = (intVal >> 16) & 255;
        const g = (intVal >> 8) & 255;
        const b = intVal & 255;
        return 'rgba(' + r + ',' + g + ',' + b + ',' + alpha + ')';
    }

    function metricColor(chart) {
        const metric = String((chart && (chart.metric || chart.dataset_label)) || '').toLowerCase();
        const titleText = String(title.textContent || '').toLowerCase();
        const combined = metric + ' ' + titleText;
        if (/saving|economia/.test(combined)) return '#1aa58d';
        if (/glosa/.test(combined)) return '#d94b67';
        if (/valor|faturamento|custo|apresentado|final/.test(combined)) return '#2f6f9f';
        if (/evento|gestão|gestao|alto custo|opme/.test(combined)) return '#f59e0b';
        if (/visita/.test(combined)) return '#0ea5e9';
        if (/uti/.test(combined)) return '#5e3db8';
        if (/seguradora/.test(combined)) return '#7c3aed';
        return '#386fa4';
    }

    function isMoneyMetric(chart) {
        const metric = String((chart && (chart.metric || chart.dataset_label)) || '');
        return metric.indexOf('R$') !== -1 || /saving|valor|glosa|faturamento|custo/i.test(metric);
    }

    function formatNumberBR(value, decimals) {
        const num = Number(value || 0);
        return num.toLocaleString('pt-BR', {
            minimumFractionDigits: decimals,
            maximumFractionDigits: decimals
        });
    }

    function formatMoneyBR(value) {
        return 'R$ ' + formatNumberBR(value, 2);
    }

    function formatMetricValue(value, chart) {
        return isMoneyMetric(chart) ? formatMoneyBR(value) : formatNumberBR(value, Number(value) % 1 === 0 ? 0 : 2);
    }

    function formatMessageDate(date) {
        return date.toLocaleString('pt-BR', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    }

    function touchInsightTime() {
        insightTime.textContent = formatMessageDate(new Date());
    }

    function formatExtraValue(value) {
        const text = String(value || '');
        const match = text.match(/^R\$\s*(-?\d+(?:[.,]\d+)?)$/);
        if (!match) {
            return text || '-';
        }
        return formatMoneyBR(Number(match[1].replace(',', '.')));
    }

    function renderChart(payload) {
        const chart = payload.chart || {};
        const labels = chart.labels || [];
        const values = chart.values || [];
        const missing = chart.missing || [];
        const type = chart.type || 'bar';
        title.textContent = payload.title || 'Gráfico';
        meta.textContent = (chart.metric || 'Indicador') + ' por ' + (chart.dimension || 'dimensão');
        insight.textContent = payload.insight || 'Sem leitura disponível.';
        touchInsightTime();
        renderTable(payload.rows || [], chart);

        if (!labels.length) {
            if (chartInstance) {
                chartInstance.destroy();
                chartInstance = null;
            }
            wrap.style.display = 'none';
            empty.style.display = 'flex';
            empty.textContent = 'Nenhum dado encontrado para os filtros atuais.';
            return;
        }

        const validLinePoints = values.filter(function(value, index) {
            return !missing[index] && value !== null && value !== undefined && value !== '' && Number.isFinite(Number(value));
        }).length;
        if (type === 'line' && validLinePoints < 2) {
            if (chartInstance) {
                chartInstance.destroy();
                chartInstance = null;
            }
            wrap.style.display = 'none';
            empty.style.display = 'flex';
            empty.textContent = 'Há dados em menos de dois meses. Não dá para desenhar uma evolução confiável com este período.';
            return;
        }

        empty.style.display = 'none';
        wrap.style.display = '';
        if (chartInstance) {
            chartInstance.destroy();
        }

        const colors = palette(labels.length);
        const lineColor = metricColor(chart);
        const ctx = canvas.getContext('2d');
        const lineFill = ctx.createLinearGradient(0, 0, 0, 360);
        lineFill.addColorStop(0, hexToRgba(lineColor, .28));
        lineFill.addColorStop(1, hexToRgba(lineColor, .04));
        const dataset = {
            label: chart.dataset_label || chart.metric || 'Valor',
            data: values,
            backgroundColor: type === 'line' ? lineFill : colors.map(function(color) { return hexToRgba(color, .82); }),
            borderColor: type === 'line' ? lineColor : colors,
            borderWidth: type === 'line' ? 3 : 1,
            pointBackgroundColor: type === 'line' ? lineColor : colors,
            pointBorderColor: '#fff',
            pointBorderWidth: 2,
            pointRadius: 4,
            pointHoverRadius: 6,
            fill: type === 'line'
        };
        if (type === 'doughnut') {
            dataset.backgroundColor = colors.map(function(color) { return hexToRgba(color, .86); });
            dataset.borderColor = '#fff';
            dataset.borderWidth = 2;
        }

        const options = {
            responsive: true,
            maintainAspectRatio: false,
            legend: {display: type === 'doughnut'},
            tooltips: {
                mode: 'index',
                intersect: false,
                callbacks: {
                    label: function(tooltipItem, data) {
                        const dataset = data.datasets[tooltipItem.datasetIndex] || {};
                        const rawValue = type === 'doughnut' ? dataset.data[tooltipItem.index] : tooltipItem.yLabel;
                        const prefix = dataset.label ? dataset.label + ': ' : '';
                        return prefix + formatMetricValue(rawValue, chart);
                    }
                }
            }
        };
        if (type !== 'doughnut') {
            options.scales = {
                yAxes: [{ticks: {
                    beginAtZero: true,
                    callback: function(value) {
                        return formatMetricValue(value, chart);
                    }
                }}],
                xAxes: [{ticks: {autoSkip: false, maxRotation: 35, minRotation: 0}}]
            };
        }

        chartInstance = new Chart(ctx, {
            type: type,
            data: {
                labels: labels,
                datasets: [dataset]
            },
            options: options
        });
    }

    function renderTable(rows, chart) {
        if (!rows.length) {
            table.textContent = 'Nenhum dado para exibir.';
            return;
        }
        const dimension = escapeHtml(chart.dimension || 'Dimensão');
        const metric = escapeHtml(chart.metric || 'Valor');
        table.innerHTML = '<table><thead><tr><th>' + dimension + '</th><th>Observação</th><th>' + metric + '</th></tr></thead><tbody>' +
            rows.map(function(row) {
                const hasValue = row.value !== null && row.value !== undefined && row.value !== '';
                const valueText = hasValue ? formatMetricValue(row.value, chart) : 'Sem dado';
                return '<tr><td>' + escapeHtml(row.label || '-') + '</td><td>' + escapeHtml(formatExtraValue(row.extra)) + '</td><td>' + escapeHtml(valueText) + '</td></tr>';
            }).join('') +
            '</tbody></table>';
    }

    function escapeHtml(value) {
        return String(value).replace(/[&<>"']/g, function(ch) {
            return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'})[ch];
        });
    }

    function resetContent() {
        input.value = '';
        title.textContent = 'Gráfico sob demanda';
        meta.textContent = 'Aguardando pedido';
        insight.textContent = 'A interpretação aparecerá após gerar o gráfico.';
        touchInsightTime();
        table.textContent = 'Nenhum dado carregado ainda.';
        empty.style.display = 'flex';
        empty.textContent = 'Digite um pedido ou escolha um exemplo para gerar o gráfico.';
        wrap.style.display = 'none';
        if (chartInstance) {
            chartInstance.destroy();
            chartInstance = null;
        }
        input.focus();
    }

    function renderAssistantResponse(payload) {
        if (chartInstance) {
            chartInstance.destroy();
            chartInstance = null;
        }
        title.textContent = payload.title || 'Resposta da IA';
        meta.textContent = payload.mode === 'clarify' ? 'Mais detalhes necessários' : 'Ajuda';
        wrap.style.display = 'none';
        empty.style.display = 'flex';
        const examples = Array.isArray(payload.examples) ? payload.examples : [];
        if (examples.length) {
            empty.innerHTML = examples.map(function(example) {
                return '<button type="button" class="ai-chart-help-example" data-question="' + escapeHtml(example) + '">' + escapeHtml(example) + '</button>';
            }).join('');
        } else {
            empty.textContent = 'Sem gráfico para exibir nesta resposta.';
        }
        insight.textContent = payload.insight || 'Pergunte por um indicador e um agrupamento para gerar um gráfico.';
        touchInsightTime();
        if (examples.length) {
            table.innerHTML = '<table><thead><tr><th>Exemplo</th><th>Uso</th><th>Resultado</th></tr></thead><tbody>' +
                examples.map(function(example) {
                    return '<tr><td>' + escapeHtml(example) + '</td><td>Pergunta pronta</td><td>Gera gráfico quando clicado</td></tr>';
                }).join('') +
                '</tbody></table>';
        } else {
            table.textContent = 'Nenhum dado carregado.';
        }
        document.querySelectorAll('.ai-chart-help-example').forEach(function(btn) {
            btn.addEventListener('click', function() {
                const question = btn.getAttribute('data-question') || btn.textContent;
                input.value = question;
                generate(question);
            });
        });
    }

    async function generate(question) {
        title.textContent = 'Gerando gráfico...';
        meta.textContent = 'Consultando dados';
        insight.textContent = 'Analisando pedido e preparando visualização...';
        touchInsightTime();
        table.textContent = 'Carregando dados...';
        empty.style.display = 'flex';
        empty.textContent = 'Gerando gráfico...';
        wrap.style.display = 'none';

        try {
            const response = await fetch(endpoint, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({question: question, filters: filters()})
            });
            const data = await response.json();
            if (!response.ok || !data.success) {
                throw new Error(data.error || 'Não foi possível gerar o gráfico.');
            }
            if (data.mode === 'help' || data.mode === 'clarify') {
                renderAssistantResponse(data);
                return;
            }
            renderChart(data);
        } catch (err) {
            title.textContent = 'Não foi possível gerar';
            meta.textContent = 'Erro';
            insight.textContent = err && err.message ? err.message : 'Erro inesperado.';
            touchInsightTime();
            table.textContent = 'Sem dados.';
            empty.style.display = 'flex';
            empty.textContent = 'Tente ajustar o pedido ou os filtros.';
            wrap.style.display = 'none';
        }
    }

    form.addEventListener('submit', function(event) {
        event.preventDefault();
        const question = input.value.trim();
        if (question) {
            generate(question);
        }
    });

    document.querySelectorAll('.ai-chart-suggestion').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const question = btn.getAttribute('data-question') || btn.textContent;
            input.value = question;
            generate(question);
        });
    });

    clearBtn.addEventListener('click', resetContent);
})();
</script>

<?php include_once("templates/footer.php"); ?>
