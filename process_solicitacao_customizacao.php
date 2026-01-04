<?php
require_once("globals.php");
require_once("db.php");
require_once("models/message.php");
require_once("dao/solicitacaoCustomizacaoDao.php");

$message = new Message($BASE_URL);
$dao = new SolicitacaoCustomizacaoDAO($conn, $BASE_URL);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$norm = function ($s) {
    $s = mb_strtolower(trim((string)$s), 'UTF-8');
    $c = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $s);
    $s = $c !== false ? $c : $s;
    return preg_replace('/[^a-z]/', '', $s);
};
$cargo = (string)($_SESSION['cargo'] ?? '');
$nivel = (string)($_SESSION['nivel'] ?? '');
$isDiretoria = in_array($norm($cargo), ['diretoria', 'diretor', 'administrador', 'admin', 'board'], true)
    || in_array($norm($nivel), ['diretoria', 'diretor', 'administrador', 'admin', 'board'], true)
    || ((int)$nivel === -1);
$emailUser = strtolower(trim((string)($_SESSION['email_user'] ?? '')));
$isFullcare = str_ends_with($emailUser, '@fullcare.com.br');
$isCliente = $emailUser !== '' && !$isFullcare;

$type = filter_input(INPUT_POST, 'type');
$id = (int)(filter_input(INPUT_POST, 'id_solicitacao', FILTER_VALIDATE_INT) ?: 0);

function sanitizeDate($value): ?string
{
    $value = trim((string)$value);
    if ($value === '') return null;
    return $value;
}

function sanitizeDateTime($value): ?string
{
    $value = trim((string)$value);
    if ($value === '') return null;
    $value = str_replace('T', ' ', $value);
    return $value;
}

$solicitacao = new SolicitacaoCustomizacao();
$solicitacao->id_solicitacao = $id ?: null;
$solicitacao->nome = trim((string)filter_input(INPUT_POST, 'nome')) ?: null;
$solicitacao->empresa = trim((string)filter_input(INPUT_POST, 'empresa')) ?: null;
$solicitacao->cargo = trim((string)filter_input(INPUT_POST, 'cargo')) ?: null;
$solicitacao->email = trim((string)filter_input(INPUT_POST, 'email')) ?: null;
$solicitacao->telefone = trim((string)filter_input(INPUT_POST, 'telefone')) ?: null;
$solicitacao->data_solicitacao = sanitizeDate(filter_input(INPUT_POST, 'data_solicitacao'));
$solicitacao->descricao = trim((string)filter_input(INPUT_POST, 'descricao')) ?: null;
$solicitacao->problema_atual = trim((string)filter_input(INPUT_POST, 'problema_atual')) ?: null;
$solicitacao->resultado_esperado = trim((string)filter_input(INPUT_POST, 'resultado_esperado')) ?: null;
$solicitacao->impacto_nivel = trim((string)filter_input(INPUT_POST, 'impacto_nivel')) ?: null;
$solicitacao->descricao_impacto = trim((string)filter_input(INPUT_POST, 'descricao_impacto')) ?: null;
$solicitacao->prioridade = trim((string)filter_input(INPUT_POST, 'prioridade')) ?: null;
$solicitacao->prazo_desejado = sanitizeDate(filter_input(INPUT_POST, 'prazo_desejado'));
$solicitacao->responsavel = trim((string)filter_input(INPUT_POST, 'responsavel')) ?: null;
$solicitacao->assinatura = trim((string)filter_input(INPUT_POST, 'assinatura')) ?: null;
$solicitacao->data_aprovacao = sanitizeDate(filter_input(INPUT_POST, 'data_aprovacao'));
$solicitacao->aprovacao_conex = trim((string)filter_input(INPUT_POST, 'aprovacao_conex')) ?: null;

$modulosSelecionados = (array)($_POST['modulos'] ?? []);
$moduloOutro = trim((string)($_POST['modulo_outro'] ?? ''));
$modulos = [];
foreach ($modulosSelecionados as $mod) {
    $mod = trim((string)$mod);
    if ($mod === '') continue;
    $modulos[] = [
        'modulo' => $mod,
        'modulo_outro' => ($mod === 'Outro' ? $moduloOutro : null),
    ];
}

$tiposSelecionados = (array)($_POST['tipos'] ?? []);
$tipos = [];
foreach ($tiposSelecionados as $tipo) {
    $tipo = trim((string)$tipo);
    if ($tipo === '') continue;
    $tipos[] = $tipo;
}

$solicitacao->status = 'Aberto';
$solicitacao->resolvido_em = null;
$solicitacao->resolvido_por = null;
$solicitacao->versao_sistema = null;
$solicitacao->prazo_resposta = null;
$solicitacao->precificacao = null;
$solicitacao->observacoes_resposta = null;
$solicitacao->aprovacao_resposta = null;
$solicitacao->data_resposta = null;

if ($isFullcare) {
    $solicitacao->prazo_resposta = sanitizeDate(filter_input(INPUT_POST, 'prazo_resposta'));
    $solicitacao->precificacao = trim((string)filter_input(INPUT_POST, 'precificacao')) ?: null;
    $solicitacao->observacoes_resposta = trim((string)filter_input(INPUT_POST, 'observacoes_resposta')) ?: null;
    $solicitacao->aprovacao_resposta = trim((string)filter_input(INPUT_POST, 'aprovacao_resposta')) ?: null;
    $solicitacao->data_resposta = sanitizeDate(filter_input(INPUT_POST, 'data_resposta'));
    $solicitacao->status = trim((string)filter_input(INPUT_POST, 'status')) ?: 'Aberto';
    $solicitacao->resolvido_em = sanitizeDateTime(filter_input(INPUT_POST, 'resolvido_em'));
    $solicitacao->resolvido_por = (int)($_SESSION['id_usuario'] ?? 0) ?: null;
    $solicitacao->versao_sistema = trim((string)filter_input(INPUT_POST, 'versao_sistema')) ?: null;

    if ($solicitacao->status === 'Resolvido' && !$solicitacao->resolvido_em) {
        $solicitacao->resolvido_em = date('Y-m-d H:i:s');
    }
}

$solicitacao->usuario_create = (int)($_SESSION['id_usuario'] ?? 0) ?: null;

$anexos = [];
$anexoTipo = trim((string)($_POST['anexo_tipo'] ?? '')) ?: null;
$allowedExt = ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx'];
$maxSize = 10 * 1024 * 1024;

if ($isCliente && !empty($_FILES['anexos']['name'][0])) {
    $total = count($_FILES['anexos']['name']);
    for ($i = 0; $i < $total; $i++) {
        $err = $_FILES['anexos']['error'][$i] ?? UPLOAD_ERR_NO_FILE;
        if ($err !== UPLOAD_ERR_OK) {
            continue;
        }

        $name = $_FILES['anexos']['name'][$i] ?? '';
        $tmp = $_FILES['anexos']['tmp_name'][$i] ?? '';
        $size = (int)($_FILES['anexos']['size'][$i] ?? 0);
        $type = $_FILES['anexos']['type'][$i] ?? '';

        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        if (!in_array($ext, $allowedExt, true)) {
            continue;
        }
        if ($size <= 0 || $size > $maxSize) {
            continue;
        }

        $safeName = uniqid('custom_', true) . '.' . $ext;
        $destPath = 'uploads/customizacao/' . $safeName;
        if (!move_uploaded_file($tmp, $destPath)) {
            continue;
        }

        $anexos[] = [
            'tipo' => $anexoTipo,
            'nome_original' => $name,
            'arquivo' => $destPath,
            'mime' => $type,
            'tamanho' => $size,
        ];
    }
}

try {
    if ($type === 'update' && $id > 0) {
        $current = $dao->findById($id);
        if (!$current) {
            throw new RuntimeException('Solicitação não encontrada.');
        }
        $existing = $current['solicitacao'];

        if (!$isCliente) {
            $solicitacao->nome = $existing->nome;
            $solicitacao->empresa = $existing->empresa;
            $solicitacao->cargo = $existing->cargo;
            $solicitacao->email = $existing->email;
            $solicitacao->telefone = $existing->telefone;
            $solicitacao->data_solicitacao = $existing->data_solicitacao;
            $solicitacao->descricao = $existing->descricao;
            $solicitacao->problema_atual = $existing->problema_atual;
            $solicitacao->resultado_esperado = $existing->resultado_esperado;
            $solicitacao->impacto_nivel = $existing->impacto_nivel;
            $solicitacao->descricao_impacto = $existing->descricao_impacto;
            $solicitacao->prioridade = $existing->prioridade;
            $solicitacao->prazo_desejado = $existing->prazo_desejado;
            $solicitacao->responsavel = $existing->responsavel;
            $solicitacao->assinatura = $existing->assinatura;
            $solicitacao->data_aprovacao = $existing->data_aprovacao;
            $solicitacao->aprovacao_conex = $existing->aprovacao_conex;
            $modulos = $current['modulos'] ?? [];
            $tipos = array_map(fn($t) => $t['tipo'] ?? '', $current['tipos'] ?? []);
            $anexos = [];
        }
        if (!$isFullcare) {
            $solicitacao->prazo_resposta = $existing->prazo_resposta;
            $solicitacao->precificacao = $existing->precificacao;
            $solicitacao->observacoes_resposta = $existing->observacoes_resposta;
            $solicitacao->aprovacao_resposta = $existing->aprovacao_resposta;
            $solicitacao->data_resposta = $existing->data_resposta;
            $solicitacao->status = $existing->status ?: 'Aberto';
            $solicitacao->resolvido_em = $existing->resolvido_em;
            $solicitacao->resolvido_por = $existing->resolvido_por;
            $solicitacao->versao_sistema = $existing->versao_sistema;
        }

        $dao->update($solicitacao, $modulos, $tipos, $anexos);
        $message->setMessage('Solicitação atualizada.', 'success', 'list_solicitacao_customizacao.php');
    } else {
        if (!$isCliente) {
            $message->setMessage('Apenas usuários clientes podem criar solicitações.', 'danger', 'solicitacao_customizacao.php');
            exit;
        }
        $dao->create($solicitacao, $modulos, $tipos, $anexos);
        $message->setMessage('Solicitação registrada com sucesso.', 'success', 'solicitacao_customizacao.php');
    }
} catch (Throwable $e) {
    $message->setMessage('Erro ao salvar a solicitação.', 'danger', 'solicitacao_customizacao.php');
}
