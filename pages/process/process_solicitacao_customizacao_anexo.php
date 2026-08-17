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

if (!FullCareAccess::can($conn, 'solicitacoes', 'delete')) {
    $message->setMessage('Acesso restrito à diretoria.', 'danger', 'list_solicitacao_customizacao.php');
    exit;
}

$idSolicitacao = (int)(filter_input(INPUT_POST, 'id_solicitacao', FILTER_VALIDATE_INT) ?: 0);
$idAnexo = (int)(filter_input(INPUT_POST, 'id_anexo', FILTER_VALIDATE_INT) ?: 0);

if ($idSolicitacao && $idAnexo) {
    $dao->deleteAnexo($idAnexo, $idSolicitacao);
    $message->setMessage('Anexo removido.', 'success', 'solicitacao_customizacao.php?id=' . $idSolicitacao);
    exit;
}

$message->setMessage('Anexo não encontrado.', 'danger', 'list_solicitacao_customizacao.php');
