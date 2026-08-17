<?php
declare(strict_types=1);

require_once __DIR__ . '/globals.php';
FullCareAccess::enforce($conn, $BASE_URL, 'permissoes', 'manage');

if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

$profiles = $conn->query("SELECT id_access_profile, nome, slug, descricao, protegido
    FROM tb_access_profile WHERE ativo = 1 ORDER BY id_access_profile, nome")->fetchAll(PDO::FETCH_ASSOC);
$profileIds = array_map(static fn(array $p): int => (int)$p['id_access_profile'], $profiles);
$selectedId = (int)($_GET['perfil'] ?? ($profileIds[0] ?? 0));
if (!in_array($selectedId, $profileIds, true)) $selectedId = $profileIds[0] ?? 0;

$selectedProfile = null;
foreach ($profiles as $profile) {
    if ((int)$profile['id_access_profile'] === $selectedId) $selectedProfile = $profile;
}

$moduleLabels = [
    'dashboard' => 'Início e central de trabalho', 'manual' => 'Manual',
    'pacientes' => 'Pacientes', 'hospitais' => 'Hospitais', 'seguradoras' => 'Seguradoras',
    'estipulantes' => 'Estipulantes', 'censo' => 'Censo', 'internacoes' => 'Internações',
    'visitas' => 'Visitas', 'gestao' => 'Gestão', 'contas' => 'Contas e RAH',
    'altas' => 'Altas', 'usuarios' => 'Usuários', 'permissoes' => 'Permissões',
    'cadastros_clinicos' => 'Cadastros clínicos', 'cuidado_continuado' => 'Cuidado continuado',
    'bi_operacional' => 'BI operacional', 'bi_estrategico' => 'BI estratégico',
    'solicitacoes' => 'Solicitações',
];
$actionLabels = [
    'view' => 'Visualizar', 'create' => 'Criar', 'edit' => 'Editar', 'delete' => 'Excluir',
    'discharge' => 'Gerar alta', 'revert_discharge' => 'Reverter alta',
    'close_management' => 'Fechar gestão', 'reopen_management' => 'Reabrir gestão',
    'finalize_account' => 'Finalizar conta', 'reopen_account' => 'Reabrir conta',
    'generate_pdf' => 'Gerar PDF', 'export' => 'Exportar', 'manage' => 'Administrar',
];

$stmt = $conn->prepare("SELECT module, action, allowed FROM tb_access_profile_permission WHERE profile_id = :id");
$stmt->execute([':id' => $selectedId]);
$permissions = [];
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $permissions[$row['module']][$row['action']] = (int)$row['allowed'] === 1;
}

$users = $conn->query("SELECT u.id_usuario, u.usuario_user, u.cargo_user, p.nome AS perfil
    FROM tb_user u LEFT JOIN tb_access_profile p ON p.id_access_profile = u.fk_access_profile
    WHERE u.ativo_user = 's' ORDER BY p.id_access_profile, u.usuario_user")->fetchAll(PDO::FETCH_ASSOC);
$isProtected = ($selectedProfile['slug'] ?? '') === 'superadministrador';

include __DIR__ . '/templates/header.php';
?>
<div class="container-fluid py-3 px-3 px-lg-4">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
        <div>
            <h3 class="mb-1">Níveis de acesso</h3>
            <p class="text-muted mb-0">A matriz define o que cada nível pode visualizar e alterar em todo o FullCare.</p>
        </div>
        <a class="btn btn-outline-primary" href="<?= htmlspecialchars($BASE_URL) ?>manual_acessos.html">Ver regras no manual</a>
    </div>

    <div class="alert alert-info py-2">
        O <strong>cargo</strong> descreve a função profissional. O <strong>nível de acesso</strong> controla páginas, menus e operações.
        Alterações nesta tela são registradas em auditoria.
    </div>

    <ul class="nav nav-pills flex-wrap gap-2 mb-3">
        <?php foreach ($profiles as $profile): ?>
            <li class="nav-item"><a class="nav-link <?= (int)$profile['id_access_profile'] === $selectedId ? 'active' : '' ?>"
                href="<?= htmlspecialchars($BASE_URL) ?>administracao/permissoes?perfil=<?= (int)$profile['id_access_profile'] ?>">
                <?= htmlspecialchars($profile['nome']) ?></a></li>
        <?php endforeach; ?>
    </ul>

    <div class="card shadow-sm mb-4">
        <div class="card-header bg-white">
            <strong><?= htmlspecialchars($selectedProfile['nome'] ?? 'Perfil') ?></strong>
            <div class="small text-muted"><?= htmlspecialchars($selectedProfile['descricao'] ?? '') ?></div>
        </div>
        <div class="card-body p-0">
            <?php if ($isProtected): ?>
                <div class="alert alert-warning m-3 mb-0">Este nível é protegido e sempre possui acesso total. Sua matriz não pode ser reduzida.</div>
            <?php endif; ?>
            <form method="post" action="<?= htmlspecialchars($BASE_URL) ?>process_access_profiles.php">
                <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf']) ?>">
                <input type="hidden" name="profile_id" value="<?= $selectedId ?>">
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle mb-0">
                        <thead><tr><th class="ps-3">Módulo</th><?php foreach ($actionLabels as $label): ?><th class="text-center small"><?= htmlspecialchars($label) ?></th><?php endforeach; ?></tr></thead>
                        <tbody>
                        <?php foreach ($moduleLabels as $module => $moduleLabel): ?>
                            <tr><th class="ps-3 text-nowrap"><?= htmlspecialchars($moduleLabel) ?></th>
                            <?php foreach ($actionLabels as $action => $actionLabel): ?>
                                <td class="text-center"><input class="form-check-input" type="checkbox"
                                    name="permissions[<?= htmlspecialchars($module) ?>][<?= htmlspecialchars($action) ?>]" value="1"
                                    <?= !empty($permissions[$module][$action]) || $isProtected ? 'checked' : '' ?> <?= $isProtected ? 'disabled' : '' ?>
                                    aria-label="<?= htmlspecialchars($moduleLabel . ': ' . $actionLabel) ?>"></td>
                            <?php endforeach; ?></tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php if (!$isProtected): ?><div class="p-3 border-top"><button class="btn btn-primary" type="submit">Salvar matriz do nível</button></div><?php endif; ?>
            </form>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-white"><strong>Usuários e níveis atuais</strong></div>
        <div class="table-responsive"><table class="table table-sm table-striped align-middle mb-0">
            <thead><tr><th class="ps-3">Usuário</th><th>Cargo</th><th>Nível de acesso</th><th></th></tr></thead>
            <tbody><?php foreach ($users as $user): ?><tr>
                <td class="ps-3"><?= htmlspecialchars($user['usuario_user']) ?></td>
                <td><?= htmlspecialchars($user['cargo_user'] ?: 'Não informado') ?></td>
                <td><?= htmlspecialchars($user['perfil'] ?: 'Não definido') ?></td>
                <td class="text-end pe-3"><a class="btn btn-sm btn-outline-secondary" href="<?= htmlspecialchars($BASE_URL) ?>usuarios/editar/<?= (int)$user['id_usuario'] ?>">Editar usuário</a></td>
            </tr><?php endforeach; ?></tbody>
        </table></div>
    </div>
</div>
<?php include __DIR__ . '/templates/footer.php'; ?>
