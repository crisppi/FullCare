<?php
define('SKIP_HEADER', true);
include_once("check_logado.php");

require_once("globals.php");
require_once("db.php");
require_once("models/message.php");
require_once("dao/usuarioDao.php");
require_once("dao/hospitalUserDao.php");

$userDao = new UserDAO($conn, $BASE_URL);
$hospitalUserDao = new hospitalUserDAO($conn, $BASE_URL);
Gate::enforceAction($conn, $BASE_URL, 'delete', 'Você não tem permissão para excluir vínculo hospital-usuário.');

$message = new Message($BASE_URL);
if (strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    http_response_code(405);
    header('Location: ' . $BASE_URL . 'list_hospitalUser.php', true, 303);
    exit;
}

$csrf = (string)filter_input(INPUT_POST, 'csrf', FILTER_UNSAFE_RAW);
if (!csrf_is_valid($csrf)) {
    http_response_code(400);
    $message->setMessage("CSRF inválido.", "error", "list_hospitalUser.php");
    exit;
}

$id_hospitalUser = filter_input(INPUT_POST, "id_hospitalUser", FILTER_VALIDATE_INT);
if ($id_hospitalUser) {
    $hospitalUserDao->destroy($id_hospitalUser);
}

header('Location: ' . $BASE_URL . 'list_hospitalUser.php', true, 303);
exit;
