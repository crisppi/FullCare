<?php
$cptCarenciasPaciente = $cptCarenciasPaciente ?? [];
if (!$cptCarenciasPaciente && !empty($id_paciente) && isset($conn)) {
    try {
        $stmtCpt = $conn->prepare("SELECT * FROM tb_paciente_cpt_carencia WHERE fk_paciente_cpt = :id AND deletado_cpt = 'n' ORDER BY data_inicio_cpt DESC, id_cpt_carencia DESC");
        $stmtCpt->execute([':id' => (int)$id_paciente]);
        $cptCarenciasPaciente = $stmtCpt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (Throwable $e) {
        $cptCarenciasPaciente = [];
    }
}
?>
<div class="step entity-step-card entity-step-card--collapsible is-collapsed" id="step-cpt-carencia">
    <div class="entity-step-header" role="button" tabindex="0" aria-expanded="false" aria-controls="step-cpt-carencia-panel">
        <div class="entity-step-copy">
            <p class="internacao-card__eyebrow mb-1">Tabela adicional</p>
            <h3 class="entity-step-title">CPT e carências</h3>
            <p class="entity-step-desc">Registre coberturas parciais temporárias e períodos de carência do paciente.</p>
        </div>
        <span class="entity-step-toggle">Abrir</span>
    </div>
    <div class="entity-step-panel" id="step-cpt-carencia-panel" hidden>
        <div class="inline-manager-card mb-3">
            <div class="row">
                <div class="form-group col-md-2 mb-2"><label for="cpt_tipo_inline">Tipo</label><select class="form-control" id="cpt_tipo_inline"><option value="cpt">CPT</option><option value="carencia">Carência</option></select></div>
                <div class="form-group col-md-4 mb-2"><label for="cpt_descricao_inline">Descrição</label><input type="text" class="form-control" id="cpt_descricao_inline" placeholder="Doença, procedimento ou cobertura" maxlength="255"></div>
                <div class="form-group col-md-2 mb-2"><label for="cpt_inicio_inline">Início</label><input type="date" class="form-control" id="cpt_inicio_inline"></div>
                <div class="form-group col-md-2 mb-2"><label for="cpt_fim_inline">Fim</label><input type="date" class="form-control" id="cpt_fim_inline"></div>
                <div class="form-group col-md-1 mb-2"><label for="cpt_status_inline">Situação</label><select class="form-control" id="cpt_status_inline"><option value="vigente">Vigente</option><option value="cumprida">Cumprida</option><option value="encerrada">Encerrada</option><option value="cancelada">Cancelada</option></select></div>
                <div class="form-group col-md-1 mb-2 cpt-add-column"><button type="button" id="btnAddCptInline" class="btn btn-primary inline-add-btn" aria-label="Adicionar CPT ou carência">+</button></div>
            </div>
            <div class="form-group mb-2"><label for="cpt_observacao_inline">Observação</label><input type="text" class="form-control" id="cpt_observacao_inline" maxlength="1000"></div>
            <div class="table-responsive mt-2">
                <table class="table table-sm table-striped mb-0">
                    <thead><tr><th>Tipo</th><th>Descrição</th><th>Vigência</th><th>Situação</th><th>Observação</th><th>Ação</th></tr></thead>
                    <tbody id="cptTableBody">
                        <tr id="cptTableEmpty" style="display: <?= empty($cptCarenciasPaciente) ? '' : 'none' ?>;"><td colspan="6" class="text-muted text-center">Nenhuma CPT ou carência registrada.</td></tr>
                        <?php foreach ($cptCarenciasPaciente as $cpt): ?>
                        <tr>
                            <td><?= ($cpt['tipo_cpt'] ?? '') === 'cpt' ? 'CPT' : 'Carência' ?></td>
                            <td><?= htmlspecialchars((string)($cpt['descricao_cpt'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= !empty($cpt['data_inicio_cpt']) ? date('d/m/Y', strtotime($cpt['data_inicio_cpt'])) : '—' ?> a <?= !empty($cpt['data_fim_cpt']) ? date('d/m/Y', strtotime($cpt['data_fim_cpt'])) : '—' ?></td>
                            <td><?= ucfirst(htmlspecialchars((string)($cpt['status_cpt'] ?? 'vigente'), ENT_QUOTES, 'UTF-8')) ?></td>
                            <td><?= htmlspecialchars((string)($cpt['observacao_cpt'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><button type="button" class="btn btn-sm btn-outline-danger btn-remove-cpt"><i class="fas fa-trash-alt"></i></button></td>
                            <td style="display:none"><input type="hidden" name="cpt_tipo[]" value="<?= htmlspecialchars((string)($cpt['tipo_cpt'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"><input type="hidden" name="cpt_descricao[]" value="<?= htmlspecialchars((string)($cpt['descricao_cpt'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"><input type="hidden" name="cpt_inicio[]" value="<?= htmlspecialchars((string)($cpt['data_inicio_cpt'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"><input type="hidden" name="cpt_fim[]" value="<?= htmlspecialchars((string)($cpt['data_fim_cpt'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"><input type="hidden" name="cpt_status[]" value="<?= htmlspecialchars((string)($cpt['status_cpt'] ?? 'vigente'), ENT_QUOTES, 'UTF-8') ?>"><input type="hidden" name="cpt_observacao[]" value="<?= htmlspecialchars((string)($cpt['observacao_cpt'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div id="cptHiddenContainer"></div>
        </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const body = document.getElementById('cptTableBody');
    const empty = document.getElementById('cptTableEmpty');
    const hidden = document.getElementById('cptHiddenContainer');
    const add = document.getElementById('btnAddCptInline');
    if (!body || !empty || !hidden || !add) return;
    const esc = value => { const div = document.createElement('div'); div.textContent = value || ''; return div.innerHTML; };
    const updateEmpty = () => { empty.style.display = body.querySelectorAll('tr:not(#cptTableEmpty)').length ? 'none' : ''; };
    const bindRemove = button => button.addEventListener('click', function () { const row = button.closest('tr'); if (row) row.remove(); updateEmpty(); });
    body.querySelectorAll('.btn-remove-cpt').forEach(bindRemove);
    add.addEventListener('click', function () {
        const item = {tipo:document.getElementById('cpt_tipo_inline').value, descricao:document.getElementById('cpt_descricao_inline').value.trim(), inicio:document.getElementById('cpt_inicio_inline').value, fim:document.getElementById('cpt_fim_inline').value, status:document.getElementById('cpt_status_inline').value, observacao:document.getElementById('cpt_observacao_inline').value.trim()};
        if (!item.descricao) { document.getElementById('cpt_descricao_inline').focus(); return; }
        if (item.inicio && item.fim && item.fim < item.inicio) { document.getElementById('cpt_fim_inline').setCustomValidity('A data final não pode ser anterior à data inicial.'); document.getElementById('cpt_fim_inline').reportValidity(); return; }
        document.getElementById('cpt_fim_inline').setCustomValidity('');
        const row = document.createElement('tr');
        const tipoLabel = item.tipo === 'cpt' ? 'CPT' : 'Carência';
        row.innerHTML = `<td>${tipoLabel}</td><td>${esc(item.descricao)}</td><td>${item.inicio || '—'} a ${item.fim || '—'}</td><td>${esc(item.status)}</td><td>${esc(item.observacao || '—')}</td><td><button type="button" class="btn btn-sm btn-outline-danger btn-remove-cpt"><i class="fas fa-trash-alt"></i></button></td>`;
        const holder = document.createElement('td'); holder.style.display = 'none';
        [['cpt_tipo[]',item.tipo],['cpt_descricao[]',item.descricao],['cpt_inicio[]',item.inicio],['cpt_fim[]',item.fim],['cpt_status[]',item.status],['cpt_observacao[]',item.observacao]].forEach(([name,value]) => { const input=document.createElement('input'); input.type='hidden'; input.name=name; input.value=value; holder.appendChild(input); });
        row.appendChild(holder); bindRemove(row.querySelector('.btn-remove-cpt')); body.appendChild(row); updateEmpty();
        ['cpt_descricao_inline','cpt_inicio_inline','cpt_fim_inline','cpt_observacao_inline'].forEach(id => document.getElementById(id).value=''); document.getElementById('cpt_status_inline').value='vigente';
    });
    updateEmpty();
});
</script>
