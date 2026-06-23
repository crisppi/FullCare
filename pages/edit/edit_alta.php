<?php
include_once("check_logado.php");

require_once("templates/header.php");
require_once("models/usuario.php");
require_once("models/internacao.php");
require_once("dao/usuarioDao.php");
require_once("dao/internacaoDao.php");
include("array_dados.php");

$internacao = new internacao();
$userDao = new UserDAO($conn, $BASE_URL);
$internacaoDao = new internacaoDAO($conn, $BASE_URL);

// Receber id do usuário
$id_internacao = (int)filter_input(INPUT_GET, "id_internacao", FILTER_SANITIZE_NUMBER_INT);
$internacao = $internacaoDao->findById($id_internacao);

$Internacao_geral = new internacaoDAO($conn, $BASE_URL);
$order = null;
$limite = null;
$where = $id_internacao > 0 ? 'ac.id_internacao = :id_internacao' : '';
$whereParams = $id_internacao > 0 ? [':id_internacao' => $id_internacao] : [];
$internacao = $internacaoDao->selectAllInternacao($where, $order, $limite, $whereParams);
extract($internacao);

$dataAtual = date('Y-m-d');

function dateToTs(?string $date): ?int
{
    if (!$date) return null;
    $ts = strtotime(substr((string)$date, 0, 10));
    return $ts ? (int)$ts : null;
}
function daysExclusive(int $startTs, int $endTs): int
{
    if ($endTs <= $startTs) return 0;
    return (int)floor(($endTs - $startTs) / 86400);
}
function computeCoverageAndGaps(array $intervals, int $startTs, int $endTs): array
{
    $totalDays = daysExclusive($startTs, $endTs);
    if ($totalDays <= 0) {
        return [0, 0, []];
    }

    if (!$intervals) {
        return [0, $totalDays, [[date('d/m/Y', $startTs), date('d/m/Y', $endTs - 86400)]]];
    }

    usort($intervals, fn($a, $b) => $a['s'] <=> $b['s']);
    $merged = [];
    foreach ($intervals as $it) {
        if (empty($merged)) {
            $merged[] = $it;
            continue;
        }
        $lastIdx = count($merged) - 1;
        if ($it['s'] <= $merged[$lastIdx]['e']) {
            if ($it['e'] > $merged[$lastIdx]['e']) {
                $merged[$lastIdx]['e'] = $it['e'];
            }
            continue;
        }
        $merged[] = $it;
    }

    $coveredDays = 0;
    $gaps = [];
    $cursor = $startTs;
    foreach ($merged as $range) {
        if ($range['s'] > $cursor) {
            $gaps[] = [date('d/m/Y', $cursor), date('d/m/Y', $range['s'] - 86400)];
        }
        $coveredDays += daysExclusive($range['s'], $range['e']);
        if ($range['e'] > $cursor) {
            $cursor = $range['e'];
        }
    }

    if ($cursor < $endTs) {
        $gaps[] = [date('d/m/Y', $cursor), date('d/m/Y', $endTs - 86400)];
    }

    $missingDays = max(0, $totalDays - $coveredDays);
    return [$coveredDays, $missingDays, $gaps];
}

$pr_pendente_label = '';
$internStart = $internacao['0']['data_intern_int'] ?? null;
$internStartTs = dateToTs($internStart);
$altaStmt = $conn->prepare("SELECT MAX(data_alta_alt) AS data_alta_alt FROM tb_alta WHERE fk_id_int_alt = :id");
$altaStmt->bindValue(':id', (int)$id_internacao, PDO::PARAM_INT);
$altaStmt->execute();
$altaRow = $altaStmt->fetch(PDO::FETCH_ASSOC) ?: [];
$altaDate = $altaRow['data_alta_alt'] ?? null;
$internEnd = $altaDate ?: $dataAtual;
$internEndTs = dateToTs($internEnd);

if ($internStartTs && $internEndTs && $internEndTs > $internStartTs) {
    $stmt = $conn->prepare("SELECT prorrog1_ini_pror, prorrog1_fim_pror FROM tb_prorrogacao WHERE fk_internacao_pror = :id ORDER BY prorrog1_ini_pror");
    $stmt->bindValue(':id', (int)$id_internacao, PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    if (!$rows) {
        $stmt = $conn->prepare("
            SELECT data_inicio_neg AS prorrog1_ini_pror, data_fim_neg AS prorrog1_fim_pror
            FROM tb_negociacao
            WHERE fk_id_int = :id
              AND tipo_negociacao = 'PRORROGACAO_AUTOMATICA'
            ORDER BY data_inicio_neg
        ");
        $stmt->bindValue(':id', (int)$id_internacao, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
    $intervals = [];
    foreach ($rows as $p) {
        $iniTs = dateToTs($p['prorrog1_ini_pror'] ?? null);
        if (!$iniTs) continue;
        $fimBaseTs = dateToTs($p['prorrog1_fim_pror'] ?? null) ?: ($internEndTs - 86400);
        $fimTs = $fimBaseTs + 86400;
        if ($fimTs <= $internStartTs || $iniTs >= $internEndTs) continue;
        $iniTs = max($iniTs, $internStartTs);
        $fimTs = min($fimTs, $internEndTs);
        $intervals[] = ['s' => $iniTs, 'e' => $fimTs];
    }
    [, $missingDays, $gaps] = computeCoverageAndGaps($intervals, $internStartTs, $internEndTs);
    if ($missingDays > 0) {
        $parts = array_map(fn($g) => $g[0] . ' → ' . $g[1], $gaps);
        $pr_pendente_label = $missingDays . ' dias | ' . implode(' • ', $parts);
    }
}

?>

<style>
    .alta-page {
        width: 100%;
        margin: 0;
        padding: 8px 12px 28px;
    }

    .alta-hero {
        background: linear-gradient(135deg, #1f5d99, #58a9ff);
        color: #fff;
        border-radius: 12px;
        padding: 9px 14px;
        box-shadow: 0 8px 16px rgba(35, 102, 147, 0.14);
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        margin: 0 0 10px;
    }

    .alta-hero h1 {
        margin: 0;
        font-size: .96rem;
        letter-spacing: .02em;
        color: #fff;
        font-weight: 800;
    }

    .alta-hero__tag {
        background: rgba(255, 255, 255, 0.2);
        padding: 4px 10px;
        border-radius: 999px;
        font-weight: 600;
        font-size: .68rem;
        white-space: nowrap;
    }

    .alta-page__content {
        margin: 0;
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .alta-card {
        background: #f8f9fc;
        border: 1px solid #e5eaf3;
        border-radius: 10px;
        box-shadow: 0 5px 12px rgba(45, 18, 70, .045);
        padding: 10px 12px 12px;
    }

    .alta-card__header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        margin-bottom: 8px;
    }

    .alta-card__eyebrow {
        text-transform: uppercase;
        letter-spacing: .18em;
        font-size: .56rem;
        margin: 0;
        color: #5e2363;
        font-weight: 800;
    }

    .alta-card__title {
        margin: 2px 0 0;
        font-size: .78rem;
        color: #2e114c;
        font-weight: 750;
        line-height: 1.1;
    }

    .alta-card__tag {
        background: #f8eefc;
        color: #5e2363;
        padding: 3px 9px;
        border-radius: 999px;
        font-weight: 600;
        font-size: .65rem;
        white-space: nowrap;
    }

    .alta-card .form-control,
    .alta-card select.form-control {
        min-height: 28px !important;
        height: 28px !important;
        padding: 3px 8px;
        font-size: .68rem;
        border-radius: 6px;
        line-height: 1.2;
        border: 1px solid #cbd5e1;
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, .95), 0 1px 3px rgba(15, 23, 42, .10);
    }

    .alta-card label.control-label {
        margin-bottom: 2px;
        font-size: .62rem;
        line-height: 1.1;
        color: #5f6673;
        font-weight: 700;
    }

    .alta-card .form-group {
        margin-bottom: 4px;
    }

    .alta-card input[type="date"].form-control,
    .alta-card input[type="time"].form-control,
    .alta-card select.form-control {
        padding-top: 4px;
        padding-bottom: 4px;
    }
    .alta-open-badge {
        background: #ffe3e3;
        color: #8a1c1c;
        border: 1px solid #dc3545;
        border-radius: 999px;
        padding: 3px 10px;
        font-weight: 600;
        font-size: .68rem;
        text-decoration: none;
        white-space: nowrap;
        display: inline-flex;
        align-items: center;
    }
    .alta-open-badge:hover {
        background: #ffd6d6;
        color: #7a1414;
    }
    .alta-confirm-dialog {
        display: none;
        position: fixed;
        inset: 0;
        z-index: 1050;
        background: rgba(0, 0, 0, .4);
        align-items: center;
        justify-content: center;
    }
    .alta-confirm-content {
        background: #fff;
        width: 90%;
        max-width: 520px;
        border-radius: 12px;
        padding: 18px 20px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, .25);
    }
    .alta-confirm-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 10px;
    }
    .alta-confirm-close {
        cursor: pointer;
        font-size: 1.5rem;
        line-height: 1;
    }
    .alta-confirm-actions {
        display: flex;
        justify-content: flex-end;
        gap: 10px;
        margin-top: 14px;
    }

    .alta-actions {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 2px 8px 0;
    }

    .alta-actions #cadastrar_alta {
        min-width: 92px;
        min-height: 30px;
        padding: 5px 12px;
        font-size: .72rem;
        font-weight: 700;
        border-radius: 8px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        background-color: #0d6efd !important;
        border-color: #0d6efd !important;
        color: #fff !important;
    }

    @media (max-width: 576px) {
        .alta-page {
            padding: 6px 8px 24px;
        }

        .alta-hero,
        .alta-card__header {
            align-items: flex-start;
            flex-direction: column;
        }

        .alta-hero__tag,
        .alta-card__tag,
        .alta-open-badge {
            white-space: normal;
        }

        .alta-actions #cadastrar_alta {
            width: 100%;
            min-width: 0;
        }
    }
</style>

<!-- formulario alta -->
<div class="alta-page">
    <div class="alta-hero">
        <div>
            <h1>Alta Hospitalar</h1>
        </div>
        <span class="alta-hero__tag">Campos obrigatórios em destaque</span>
    </div>

    <div class="alta-page__content">
        <form action="<?= $BASE_URL ?>process_alta.php" id="add-movie-form" method="POST"
            enctype="multipart/form-data">
            <input type="hidden" name="type" value="alta">

            <div class="alta-card alta-card--general">
                <div class="alta-card__header">
                    <div>
                        <p class="alta-card__eyebrow">Dados essenciais</p>
                        <h2 class="alta-card__title">Hospital, paciente e datas</h2>
                    </div>
                    <span class="alta-card__tag">Campos obrigatórios marcados</span>
                </div>
                <div class="row g-2">
                    <div class="form-group col-sm-1">
                        <label class="control-label">Id-Int</label>
                        <input type="text" readonly class="form-control" id="id_internacao" name="id_internacao"
                            value="<?= $internacao['0']['id_internacao'] ?>">
                    </div>
                    <div class="form-group col-sm-3">
                        <label class="control-label">Hospital</label>
                        <input type="text" readonly class="form-control" value="<?= $internacao['0']['nome_hosp'] ?>">
                    </div>
                    <div class="form-group col-sm-3">
                        <label class="control-label">Paciente</label>
                        <input type="text" readonly class="form-control" value="<?= $internacao['0']['nome_pac'] ?>">
                    </div>

                    <div class="form-group col-sm-2">
                        <label class="control-label" for="data_alta_int">Data internação</label>
                        <input type="date" class="form-control"
                            value='<?php echo $internacao['0']['data_intern_int'] ?>' id="data_intern_int"
                            name="data_intern_int" readonly placeholder="" required>
                    </div>
                </div>
            </div>

            <div class="alta-card alta-card--alta">
                <div class="alta-card__header">
                    <div>
                        <p class="alta-card__eyebrow">Alta</p>
                        <h3 class="alta-card__title">Data, hora e motivo</h3>
                    </div>
                    <?php if (!empty($pr_pendente_label)): ?>
                        <a class="alta-open-badge"
                            href="<?= $BASE_URL ?>internacoes/editar/<?= (int)$id_internacao ?>?section=prorrog#collapseProrrog">
                            Diárias sem prorrogação: <?= htmlspecialchars($pr_pendente_label, ENT_QUOTES, 'UTF-8') ?>
                        </a>
                    <?php endif; ?>
                </div>
                <div class="row g-2">
                    <div class="form-group col-sm-2">
                        <label class="control-label" for="data_alta_alt">Data Alta</label>
                        <input type="date" onchange="checkDataAlta()" class="form-control"
                            value='<?php echo date('Y-m-d') ?>' id="data_alta_alt" name="data_alta_alt" placeholder=""
                            autofocus required>
                        <div class="notif-input oculto" id="notif-input">
                            Data inválida !
                        </div>
                    </div>
                    <div class="form-group col-sm-2">
                        <label class="control-label" for="hora_alta_alt">Hora Alta</label>
                        <input type="time" class="form-control" value='<?= date('H:i') ?>' id="hora_alta_alt"
                            name="hora_alta_alt" required>
                    </div>
                    <div class="form-group col-sm-3">
                        <label class="control-label" for="tipo_alta_alt">Tipo de alta</label>
                        <select class="form-control" id="tipo_alta_alt" name="tipo_alta_alt" required>
                            <option value="">Selecione o motivo da alta</option>
                            <?php
                            sort($dados_alta, SORT_ASC);
                            foreach ($dados_alta as $alta) { ?>
                            <option value="<?= $alta; ?>">
                                <?= $alta; ?>
                            </option>
                            <?php } ?>
                        </select>
                    </div>
                </div>

                <input type="hidden" class="form-control" value="n" id="internado_int" name="internado_int"
                    placeholder="">
                <input type="hidden" class="form-control" value='<?php echo date('Y-m-d') ?>' id="data_create_alt"
                    name="data_create_alt" placeholder="">
                <input type="hidden" value="<?= $_SESSION['email_user']; ?>" class="form-control" id="usuario_alt"
                    name="usuario_alt">
                <input type="hidden" class="form-control" id="fk_usuario_alt" value="<?= $_SESSION['id_usuario'] ?>"
                    name="fk_usuario_alt" placeholder="Digite o usuário">
            </div>

            <?php if ($internacao['0']['internado_uti'] == "s") { ?>
            <div class="alta-card alta-card--uti">
                <div class="alta-card__header">
                    <div>
                        <p class="alta-card__eyebrow">UTI</p>
                        <h3 class="alta-card__title">Alta de UTI</h3>
                    </div>
                    <span class="alta-card__tag">Obrigatório</span>
                </div>
                <div class="row g-2">
                    <div class="form-group col-sm-2">
                        <label class="control-label" for="data_alta_uti">Data alta UTI</label>
                        <input type="date" class="form-control" value='<?php echo date('Y-m-d') ?>'
                            id="data_alta_uti" name="data_alta_uti" require>
                        <div class="notif-input oculto" id="notif-input2">
                            Data inválida !
                        </div>
                    </div>
                    <div class="form-group col-sm-2">
                        <input class="form-control" type="hidden" name="alta_uti" value="alta_uti">
                    </div>
                    <div class="form-group col-sm-2">
                        <input type="hidden" class="form-control" name="id_uti"
                            value="<?= $internacao['0']['fk_internacao_uti'] ?>">
                    </div>
                    <div class="form-group col-sm-2">
                        <input type="hidden" name="id_uti" value="<?= $internacao['0']['id_uti'] ?>">
                    </div>
                    <div class="form-group col-sm-2">
                        <input type="hidden" class="form-control" value="n" id="internado_uti" name="internado_uti"
                            placeholder="internado_uti">
                    </div>

                    <input type="hidden" name="type-uti" id="alta_uti" value="alta_uti">
                    <input type="hidden" name="fk_internacao_uti" id="fk_internacao_uti"
                        value="<?= $internacao['0']['fk_internacao_uti'] ?>">
                    <div class="form-group col-sm-2">
                        <input type="hidden" class="form-control" value="n" id="internado_uti" name="internado_uti"
                            placeholder="internado_uti">
                    </div>
                </div>
            </div>
            <?php } ?>

            <div class="alta-actions">
                <button id="cadastrar_alta" type="submit" class="btn btn-primary btn-submit-standard">
                    <i style="font-size: 1rem;" class="fas fa-check edit-icon"></i>
                    Alta
                </button>
            </div>
        </form>
    </div>
</div>

<?php if (!empty($pr_pendente_label)): ?>
<div id="altaConfirmDialog" class="alta-confirm-dialog" role="dialog" aria-modal="true" aria-labelledby="altaConfirmTitle">
    <div class="alta-confirm-content">
        <div class="alta-confirm-header">
            <strong id="altaConfirmTitle">Atenção</strong>
            <span class="alta-confirm-close" onclick="closeAltaConfirm()">&times;</span>
        </div>
        <div>
            Deseja dar alta mesmo com diárias a serem prorrogadas?
        </div>
        <div class="alta-confirm-actions">
            <button type="button" class="btn btn-outline-secondary" onclick="closeAltaConfirm()">Cancelar</button>
            <button type="button" class="btn btn-danger" onclick="confirmAlta()">Sim, dar alta</button>
        </div>
    </div>
</div>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.1/dist/js/bootstrap.bundle.min.js"
    integrity="sha384-gtEjrD/SeCtmISkJkNUaaKMoLD0//ElJ19smozuHV6z3Iehds+3Ulb9Bn9Plx0x4" crossorigin="anonymous">
</script>

<?php
?>
<script src="js/scriptDataAltaHospitalar.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.0/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.1/dist/js/bootstrap.bundle.min.js"></script>
<?php if (!empty($pr_pendente_label)): ?>
<script>
    window.fullcareAltaForm = document.getElementById('add-movie-form');
    window.fullcareAltaConfirmOpen = false;

    function openAltaConfirm() {
        var dlg = document.getElementById('altaConfirmDialog');
        if (dlg) {
            dlg.style.display = 'flex';
            window.fullcareAltaConfirmOpen = true;
        }
    }
    function closeAltaConfirm() {
        var dlg = document.getElementById('altaConfirmDialog');
        if (dlg) {
            dlg.style.display = 'none';
            window.fullcareAltaConfirmOpen = false;
        }
    }
    function confirmAlta() {
        closeAltaConfirm();
        if (window.fullcareAltaForm) window.fullcareAltaForm.submit();
    }
    if (window.fullcareAltaForm && window.fullcareAltaForm.dataset.altaConfirmBound !== '1') {
        window.fullcareAltaForm.dataset.altaConfirmBound = '1';
        window.fullcareAltaForm.addEventListener('submit', function(e) {
            if (window.fullcareAltaConfirmOpen) return;
            e.preventDefault();
            openAltaConfirm();
        });
    }
</script>
<?php endif; ?>

</html>
