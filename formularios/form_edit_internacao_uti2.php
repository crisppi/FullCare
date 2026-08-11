<?php 
 $u = $utiList[0] ?? [];
 ?>

<div id="container-uti" style="display:none; margin:5px">
    <!-- DADOS PARA FORMULÁRIO UTI -->
    <div class="form-group row">

        <!-- Chaves principais -->
        <input type="hidden" name="id_uti" value="<?= $u['id_uti'] ?? '' ?>">
        <input type="hidden" id="fk_internacao_uti" name="fk_internacao_uti" value="<?= $intern['id_internacao'] ?>">
        <input type="hidden" name="fk_visita_uti" value="<?= htmlspecialchars((string)($u['fk_visita_uti'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
        <input type="hidden" name="fk_user_uti" value="<?= htmlspecialchars((string)($u['fk_user_uti'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
        <input type="hidden" name="usuario_create_uti" value="<?= htmlspecialchars((string)($u['usuario_create_uti'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
        <input type="hidden" name="internacao_uti" value="<?= htmlspecialchars((string)($u['internacao_uti'] ?? 's'), ENT_QUOTES, 'UTF-8') ?>">

        <div class="form-group col-sm-2">
            <label for="internado_uti">Internado UTI</label>
            <select class="form-control-sm form-control" id="internado_uti" name="internado_uti">
                <option value="n" <?= $u['internado_uti'] === 'n' ? 'selected' : '' ?>>Não</option>
                <option value="s" <?= $u['internado_uti'] === 's' ? 'selected' : '' ?>>Sim</option>
            </select>
        </div>

        <div class="form-group col-sm-2">
            <label for="motivo_uti">Motivo</label>
            <select class="form-control-sm form-control" id="motivo_uti" name="motivo_uti">
                <option value="">Selecione</option>
                <?php
                $motivoAtual = trim((string)($u['motivo_uti'] ?? ''));
                if ($motivoAtual !== '' && !in_array($motivoAtual, $dados_UTI, true)) {
                    echo '<option value="' . htmlspecialchars($motivoAtual, ENT_QUOTES, 'UTF-8') . '" selected>'
                        . htmlspecialchars($motivoAtual, ENT_QUOTES, 'UTF-8') . '</option>';
                }
                sort($dados_UTI, SORT_ASC);
                foreach ($dados_UTI as $motivo) {
                    $selected = ($u['motivo_uti'] ?? '') === $motivo ? 'selected' : '';
                    echo "<option value=\"$motivo\" $selected>$motivo</option>";
                }
                ?>
            </select>
        </div>

        <div class="form-group col-sm-2">
            <label for="just_uti">Justificativa</label>
            <select class="form-control-sm form-control" id="just_uti" name="just_uti">
                <option value="Pertinente" <?= ($u['just_uti'] ?? '') === 'Pertinente' ? 'selected' : '' ?>>Pertinente
                </option>
                <option value="Não pertinente" <?= ($u['just_uti'] ?? '') === 'Não pertinente' ? 'selected' : '' ?>>Não
                    pertinente</option>
            </select>
        </div>

        <div class="form-group col-sm-2">
            <label for="criterio_uti">Critério</label>
            <select class="form-control-sm form-control" id="criterio_uti" name="criterio_uti">
                <option value="">Selecione</option>
                <?php
                $criterioAtual = trim((string)($u['criterios_uti'] ?? ''));
                if ($criterioAtual !== '' && !in_array($criterioAtual, $criterios_UTI, true)) {
                    echo '<option value="' . htmlspecialchars($criterioAtual, ENT_QUOTES, 'UTF-8') . '" selected>'
                        . htmlspecialchars($criterioAtual, ENT_QUOTES, 'UTF-8') . '</option>';
                }
                sort($criterios_UTI, SORT_ASC);
                foreach ($criterios_UTI as $crit) {
                    $selected = ($u['criterios_uti'] ?? '') === $crit ? 'selected' : '';
                    echo "<option value=\"$crit\" $selected>$crit</option>";
                }
                ?>
            </select>
        </div>

        <div class="form-group col-sm-2">
            <label for="data_internacao_uti">Data internação UTI</label>
            <input type="date" class="form-control-sm form-control" id="data_internacao_uti" name="data_internacao_uti"
                value="<?= !empty($u['entrada']) ? date('Y-m-d', strtotime($u['entrada'])) : '' ?>">
        </div>

        <div class="form-group col-sm-2">
            <label for="hora_internacao_uti">Hora entrada</label>
            <input type="time" class="form-control-sm form-control" id="hora_internacao_uti" name="hora_internacao_uti"
                value="<?= htmlspecialchars(substr((string)($u['hora'] ?? ''), 0, 5), ENT_QUOTES, 'UTF-8') ?>">
        </div>

        <div class="form-group col-sm-2">
            <label for="data_alta_uti">Data alta UTI</label>
            <input type="date" class="form-control-sm form-control" id="data_alta_uti" name="data_alta_uti"
                value="<?= htmlspecialchars((string)($u['saida'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
        </div>

        <div class="form-group col-sm-2">
            <label for="especialidade_uti">Especialidade</label>
            <input type="text" class="form-control-sm form-control" id="especialidade_uti" name="especialidade_uti"
                value="<?= htmlspecialchars((string)($u['especialidade_uti'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
        </div>

        <div class="form-group row">
            <div class="form-group col-sm-2">
                <label for="vm_uti">VM</label>
                <select class="form-control-sm form-control" id="vm_uti" name="vm_uti">
                    <option value="n" <?= ($u['vm_uti'] ?? '') === 'n' ? 'selected' : '' ?>>Não</option>
                    <option value="s" <?= ($u['vm_uti'] ?? '') === 's' ? 'selected' : '' ?>>Sim</option>
                </select>
            </div>

            <div class="form-group col-sm-2">
                <label for="dva_uti">DVA</label>
                <select class="form-control-sm form-control" id="dva_uti" name="dva_uti">
                    <option value="n" <?= ($u['dva_uti'] ?? '') === 'n' ? 'selected' : '' ?>>Não</option>
                    <option value="s" <?= ($u['dva_uti'] ?? '') === 's' ? 'selected' : '' ?>>Sim</option>
                </select>
            </div>

            <div class="form-group col-sm-2">
                <label for="score_uti">Score</label>
                <select class="form-control-sm form-control" id="score_uti" name="score_uti">
                    <option value="">Selecione</option>
                    <?php if (($u['score_uti'] ?? '') !== '' && ((int)$u['score_uti'] < 1 || (int)$u['score_uti'] > 5)): ?>
                        <option value="<?= htmlspecialchars((string)$u['score_uti'], ENT_QUOTES, 'UTF-8') ?>" selected><?= htmlspecialchars((string)$u['score_uti'], ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endif; ?>
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <option value="<?= $i ?>" <?= ($u['score_uti'] ?? '') == $i ? 'selected' : '' ?>><?= $i ?></option>
                    <?php endfor; ?>
                </select>
            </div>

            <div class="form-group col-sm-2">
                <label for="saps_uti">Saps</label>
                <select class="form-control-sm form-control" id="saps_uti" name="saps_uti">
                    <option value="">Selecione</option>
                    <?php
                    $sapsAtual = trim((string)($u['saps_uti'] ?? ''));
                    if ($sapsAtual !== '' && !in_array($sapsAtual, $dados_saps, true)) {
                        echo '<option value="' . htmlspecialchars($sapsAtual, ENT_QUOTES, 'UTF-8') . '" selected>'
                            . htmlspecialchars($sapsAtual, ENT_QUOTES, 'UTF-8') . '</option>';
                    }
                    sort($dados_saps, SORT_ASC);
                    foreach ($dados_saps as $saps) {
                        $selected = ($u['saps_uti'] ?? '') === $saps ? 'selected' : '';
                        echo "<option value=\"$saps\" $selected>$saps</option>";
                    }
                    ?>
                </select>
            </div>

            <div class="form-group col-sm-2">
                <label for="glasgow_uti">Glasgow</label>
                <input type="number" min="3" max="15" class="form-control-sm form-control" id="glasgow_uti" name="glasgow_uti"
                    value="<?= htmlspecialchars((string)($u['glasgow_uti'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
            </div>

            <div class="form-group col-sm-2">
                <label for="suporte_vent_uti">Suporte ventilatório</label>
                <select class="form-control-sm form-control" id="suporte_vent_uti" name="suporte_vent_uti">
                    <option value="n" <?= ($u['suporte_vent_uti'] ?? '') === 'n' ? 'selected' : '' ?>>Não</option>
                    <option value="s" <?= ($u['suporte_vent_uti'] ?? '') === 's' ? 'selected' : '' ?>>Sim</option>
                </select>
            </div>

            <div class="form-group col-sm-2">
                <label for="dist_met_uti">Distúrbio metabólico</label>
                <select class="form-control-sm form-control" id="dist_met_uti" name="dist_met_uti">
                    <option value="n" <?= ($u['dist_met_uti'] ?? '') === 'n' ? 'selected' : '' ?>>Não</option>
                    <option value="s" <?= ($u['dist_met_uti'] ?? '') === 's' ? 'selected' : '' ?>>Sim</option>
                </select>
            </div>

            <div style="margin-top:30px" class="form-group col-sm-2">
                <a style="color:blue; font-size:0.8em" href="https://www.rccc.eu/ppc/indicadores/saps3.html"
                    target="_blank">Calcular SAPS</a>
            </div>
        </div>

        <div class="form-group col-sm-12">
            <label for="rel_uti">Relatório</label>
            <textarea style="resize:none" onclick="aumentarTextUTI()" onblur="this.rows=2" onfocus="this.rows=6"
                rows="2" class="form-control" id="rel_uti"
                name="rel_uti"><?= htmlspecialchars($u['rel_uti'] ?? '') ?></textarea>
        </div>
        <div class="form-group col-sm-12">
            <label for="justifique_uti">Justificativa clínica</label>
            <textarea rows="2" class="form-control" id="justifique_uti" name="justifique_uti"><?= htmlspecialchars((string)($u['justifique_uti'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
        </div>
    </div>
    <hr>
</div>

<script>
    // mudar linhas do text relatorio UTI 
    var text_relatorio_uti = document.querySelector("#rel_uti");

    function aumentarTextUTI() {
        if (text_relatorio_uti.rows == "2") {
            text_relatorio_uti.rows = "30"
        } else {
            text_relatorio_uti.rows = "2"
        }
    }
</script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.0/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.1/dist/js/bootstrap.bundle.min.js"></script>
