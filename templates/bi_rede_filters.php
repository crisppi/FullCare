<form method="get">
    <div class="bi-panel bi-filters bi-filters-wrap">
        <div class="bi-filter">
            <label for="data_ini">Data inicial</label>
            <input type="date" id="data_ini" name="data_ini" value="<?= e($filterValues['data_ini']) ?>">
        </div>
        <div class="bi-filter">
            <label for="data_fim">Data final</label>
            <input type="date" id="data_fim" name="data_fim" value="<?= e($filterValues['data_fim']) ?>">
        </div>
        <div class="bi-filter">
            <label for="hospital_id">Hospital</label>
            <select id="hospital_id" name="hospital_id">
                <option value="">Todos</option>
                <?php foreach ($filterOptions['hospitais'] as $opt): ?>
                    <option value="<?= e($opt['value']) ?>" <?= (string)$filterValues['hospital_id'] === (string)$opt['value'] ? 'selected' : '' ?>>
                        <?= e($opt['label']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="bi-filter">
            <label for="seguradora_id">Seguradora</label>
            <select id="seguradora_id" name="seguradora_id">
                <option value="">Todas</option>
                <?php foreach ($filterOptions['seguradoras'] as $opt): ?>
                    <option value="<?= e($opt['value']) ?>" <?= (string)$filterValues['seguradora_id'] === (string)$opt['value'] ? 'selected' : '' ?>>
                        <?= e($opt['label']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="bi-filter">
            <label for="regiao">Regiao</label>
            <select id="regiao" name="regiao">
                <option value="">Todas</option>
                <?php foreach ($filterOptions['regioes'] as $opt): ?>
                    <option value="<?= e($opt['value']) ?>" <?= (string)$filterValues['regiao'] === (string)$opt['value'] ? 'selected' : '' ?>>
                        <?= e($opt['label']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="bi-filter">
            <label for="tipo_admissao">Tipo de admissao</label>
            <select id="tipo_admissao" name="tipo_admissao">
                <option value="">Todos</option>
                <?php foreach ($filterOptions['tipos_admissao'] as $opt): ?>
                    <option value="<?= e($opt['value']) ?>" <?= (string)$filterValues['tipo_admissao'] === (string)$opt['value'] ? 'selected' : '' ?>>
                        <?= e($opt['label']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="bi-filter">
            <label for="modo_internacao">Modo de internacao</label>
            <select id="modo_internacao" name="modo_internacao">
                <option value="">Todos</option>
                <?php foreach ($filterOptions['modos_internacao'] as $opt): ?>
                    <option value="<?= e($opt['value']) ?>" <?= (string)$filterValues['modo_internacao'] === (string)$opt['value'] ? 'selected' : '' ?>>
                        <?= e($opt['label']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="bi-filter">
            <label for="uti">UTI</label>
            <select id="uti" name="uti">
                <option value="">Todos</option>
                <option value="s" <?= $filterValues['uti'] === 's' ? 'selected' : '' ?>>Sim</option>
                <option value="n" <?= $filterValues['uti'] === 'n' ? 'selected' : '' ?>>Nao</option>
            </select>
        </div>
        <div class="bi-actions">
            <button class="bi-btn" type="submit">Aplicar filtros</button>
        </div>
    </div>
</form>
