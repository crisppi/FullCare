<?php
include_once("check_logado.php");
require_once("templates/header.php");
require_once("ajax/_auth_scope.php");
require_once("app/services/AuditoriaClinicaAIService.php");

if (!isset($conn) || !($conn instanceof PDO)) {
    die("Conexao nao disponivel.");
}

function fc_clinical_e($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$ctx = ajax_user_context($conn);
$clinicalService = new AuditoriaClinicaAIService($conn, $BASE_URL);
$hospitais = $clinicalService->listHospitals($ctx);
$clinicalScopeMode = function_exists('ajax_scope_mode') ? ajax_scope_mode($ctx) : 'hospital';
$clinicalHospitalScoped = ($clinicalScopeMode === 'hospital');
?>

<link rel="stylesheet" href="<?= fc_clinical_e($BASE_URL) ?>css/producao_ia_clinica.css?v=<?= filemtime(__DIR__ . '/css/producao_ia_clinica.css') ?>">

<div class="clinical-ai-page">
    <div class="fc-module-header fc-module-header--producao">
        <div class="fc-module-header__copy">
            <p class="fc-module-header__kicker">Producao</p>
            <h1 class="fc-module-header__title">IA Cl&iacute;nica</h1>
            <p class="fc-module-header__subtitle">Pesquisa para auditoria assistencial com foco em internacao, patologia, UTI, visitas e eventos clinicos. Sem custos, faturamento ou saving real.</p>
        </div>
        <div class="fc-module-header__actions">
            <a class="btn btn-light btn-sm" href="<?= fc_clinical_e($BASE_URL . 'internacoes/lista') ?>">
                <i class="bi bi-list-ul"></i> Internacoes
            </a>
        </div>
    </div>

    <div class="clinical-ai-shell">
        <aside class="clinical-ai-panel clinical-ai-filters">
            <h2>Filtros</h2>
            <div class="clinical-ai-field">
                <label for="clinicalHospital">Hospital</label>
                <select id="clinicalHospital">
                    <option value=""><?= $clinicalHospitalScoped ? 'Todos os meus hospitais' : 'Todos os hospitais' ?></option>
                    <?php foreach ($hospitais as $h): ?>
                        <option value="<?= (int)$h['id_hospital'] ?>"><?= fc_clinical_e($h['nome_hosp']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="clinical-ai-field">
                <label for="clinicalStatus">Status</label>
                <select id="clinicalStatus">
                    <option value="internados">Internados</option>
                    <option value="todos">Todos</option>
                    <option value="alta">Com alta</option>
                </select>
            </div>
            <div class="clinical-ai-field">
                <label for="clinicalFocus">Foco clinico</label>
                <select id="clinicalFocus">
                    <option value="geral">Geral</option>
                    <option value="uti">UTI</option>
                    <option value="eventos">Eventos adversos</option>
                    <option value="patologia">Patologia</option>
                    <option value="longa_permanencia">Longa permanencia</option>
                    <option value="sem_visita">Sem visita recente</option>
                    <option value="oportunidade">Oportunidade qualitativa</option>
                </select>
            </div>
            <div class="clinical-ai-field">
                <label for="clinicalDays">Periodo</label>
                <select id="clinicalDays">
                    <option value="30">Ultimos 30 dias</option>
                    <option value="90">Ultimos 90 dias</option>
                    <option value="180" selected>Ultimos 180 dias</option>
                    <option value="365">Ultimos 12 meses</option>
                    <option value="730">Ultimos 24 meses</option>
                </select>
            </div>

            <div class="clinical-ai-suggestions">
                <button type="button" class="clinical-ai-suggestion" data-question="Quais casos precisam de revisao clinica hoje e por que?">Casos para revisar hoje</button>
                <button type="button" class="clinical-ai-suggestion" data-question="Resuma os pacientes com registro de UTI e os pontos de auditoria clinica.">UTI e pontos de atencao</button>
                <button type="button" class="clinical-ai-suggestion" data-question="Quais patologias concentram maior permanencia no periodo?">Patologias e permanencia</button>
                <button type="button" class="clinical-ai-suggestion" data-question="Liste eventos adversos, tipo de evento e pendencias clinicas registradas.">Eventos adversos</button>
                <button type="button" class="clinical-ai-suggestion" data-question="Onde pode existir oportunidade qualitativa de economia assistencial sem analisar valores?">Oportunidade qualitativa</button>
                <button type="button" class="clinical-ai-suggestion" data-question="Quais internacoes estao sem visita recente e precisam atualizar evolucao?">Sem visita recente</button>
            </div>
        </aside>

        <main class="clinical-ai-panel clinical-ai-main">
            <div id="clinicalMessages" class="clinical-ai-messages">
                <div class="clinical-ai-entry assistant">
                    <div class="clinical-ai-meta">
                        <span class="clinical-ai-speaker">FullCare - IA</span>
                        <span class="clinical-ai-time"><?= fc_clinical_e(date('d/m/Y H:i')) ?></span>
                    </div>
                    <div class="clinical-ai-message assistant">Ola. Posso pesquisar as internacoes filtradas com foco clinico para auditoria: patologia, permanencia, UTI, visitas, eventos adversos e oportunidades qualitativas de cuidado.</div>
                </div>
            </div>
            <form id="clinicalForm" class="clinical-ai-composer">
                <textarea id="clinicalQuestion" placeholder="Pergunte sobre quadro clinico, patologia, UTI, eventos, visitas ou permanencia..." required></textarea>
                <button class="clinical-ai-send" type="submit" title="Enviar"><i class="bi bi-send"></i></button>
                <button id="clinicalClear" class="clinical-ai-clear" type="button" title="Limpar conteúdo"><i class="bi bi-x-lg"></i></button>
            </form>
        </main>

        <aside class="clinical-ai-panel clinical-ai-results">
            <h2>Casos citados</h2>
            <div id="clinicalResults" class="clinical-ai-empty">As internacoes relacionadas a resposta aparecerao aqui.</div>
        </aside>
    </div>
</div>

<script>
(function() {
    const endpoint = <?= json_encode($BASE_URL . 'ajax/producao_ia_clinica.php') ?>;
    const messages = document.getElementById('clinicalMessages');
    const form = document.getElementById('clinicalForm');
    const input = document.getElementById('clinicalQuestion');
    const results = document.getElementById('clinicalResults');
    const clearBtn = document.getElementById('clinicalClear');
    const initialMessage = 'Ola. Posso pesquisar as internacoes filtradas com foco clinico para auditoria: patologia, permanencia, UTI, visitas, eventos adversos e oportunidades qualitativas de cuidado.';
    const initialResults = 'As internacoes relacionadas a resposta aparecerao aqui.';
    const loggedUserName = <?= json_encode(trim((string)($_SESSION['usuario_user'] ?? $_SESSION['login_user'] ?? $_SESSION['email_user'] ?? 'Usuário')) ?: 'Usuário') ?>;

    function filters() {
        return {
            hospital_id: document.getElementById('clinicalHospital').value,
            status: document.getElementById('clinicalStatus').value,
            focus: document.getElementById('clinicalFocus').value,
            days: document.getElementById('clinicalDays').value
        };
    }

    function addMessage(type, text) {
        const entry = document.createElement('div');
        entry.className = 'clinical-ai-entry ' + type;

        const meta = document.createElement('div');
        meta.className = 'clinical-ai-meta';

        const speaker = document.createElement('span');
        speaker.className = 'clinical-ai-speaker';
        speaker.textContent = type === 'user' ? loggedUserName : 'FullCare - IA';

        const time = document.createElement('span');
        time.className = 'clinical-ai-time';
        time.textContent = formatMessageDate(new Date());

        meta.appendChild(speaker);
        meta.appendChild(time);

        const el = document.createElement('div');
        el.className = 'clinical-ai-message ' + type;
        el.textContent = text;

        entry.appendChild(meta);
        entry.appendChild(el);
        messages.appendChild(entry);
        messages.scrollTop = messages.scrollHeight;
        return el;
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

    function renderResults(items) {
        if (!items || !items.length) {
            results.className = 'clinical-ai-empty';
            results.textContent = 'Nenhum caso estruturado retornado para esta pergunta.';
            return;
        }
        results.className = '';
        results.innerHTML = items.map(function(item) {
            const flags = (item.flags || []).map(function(flag) {
                return '<span class="clinical-ai-flag">' + escapeHtml(flag) + '</span>';
            }).join('');
            return '<a class="clinical-ai-result" href="' + escapeAttr(item.url) + '">' +
                '<strong>#' + escapeHtml(String(item.id)) + ' &middot; ' + escapeHtml(item.paciente || 'Paciente') + '</strong>' +
                '<small>' + escapeHtml(item.hospital || '-') + '</small>' +
                '<small>' + escapeHtml(item.patologia || 'Sem patologia') + '</small>' +
                '<small>' + escapeHtml(String(item.dias_internado || 0)) + ' dia(s) de permanencia' +
                (item.dias_sem_visita !== null && item.dias_sem_visita !== undefined ? ' &middot; ' + escapeHtml(String(item.dias_sem_visita)) + ' dia(s) sem visita' : '') +
                '</small>' +
                (flags ? '<div class="clinical-ai-flags">' + flags + '</div>' : '') +
                '</a>';
        }).join('');
    }

    function escapeHtml(value) {
        return String(value).replace(/[&<>"']/g, function(ch) {
            return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'})[ch];
        });
    }

    function escapeAttr(value) {
        return escapeHtml(value).replace(/`/g, '&#096;');
    }

    function resetContent() {
        input.value = '';
        messages.innerHTML = '';
        addMessage('assistant', initialMessage);
        results.className = 'clinical-ai-empty';
        results.textContent = initialResults;
        input.focus();
    }

    async function send(question) {
        addMessage('user', question);
        input.value = '';
        const waiting = addMessage('assistant', 'Analisando internacoes pelo recorte clinico...');
        try {
            const response = await fetch(endpoint, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({question: question, filters: filters()})
            });
            const data = await response.json();
            if (!response.ok || !data.success) {
                throw new Error(data.error || 'Nao foi possivel gerar a resposta.');
            }
            waiting.textContent = data.answer || 'Sem resposta.';
            renderResults(data.results || []);
        } catch (err) {
            waiting.textContent = 'Nao consegui responder agora: ' + (err && err.message ? err.message : 'erro inesperado');
        }
    }

    form.addEventListener('submit', function(event) {
        event.preventDefault();
        const question = input.value.trim();
        if (question) {
            send(question);
        }
    });

    document.querySelectorAll('.clinical-ai-suggestion').forEach(function(btn) {
        btn.addEventListener('click', function() {
            send(btn.getAttribute('data-question') || btn.textContent);
        });
    });

    clearBtn.addEventListener('click', resetContent);
})();
</script>

<?php include_once("templates/footer.php"); ?>
