<?php
// include_once ("check_logado.php");
include_once("db.php");
include_once("globals.php");
include_once("models/mensagem.php");
include_once("dao/mensagemDao.php");

$mensagemDao = new mensagemDAO($conn, $BASE_URL);

$de_usuario = $_GET['de_usuario'];
$para_usuario = $_GET['para_usuario'];
$ultima_msg = $_GET['ultima_msg'];
$messages = $mensagemDao->getMensagemsBetweenUsers($de_usuario, $para_usuario, $ultima_msg);

foreach ($messages as $mensagem) {
  $messageClass = ($mensagem->de_usuario == $de_usuario) ? 'sent' : 'received';
  $dataMensagem = date('d/m/Y H:i', strtotime($mensagem->data_mensagem)); // Formata a data/hora da mensagem

  // Substituir o padrão "link_capeante=numero" por um link
  $mensagemComLink = preg_replace_callback('/link_capeante=(\d+)/', function ($matches) {
    $id_capeante = $matches[1]; // Captura o número após o "link_capeante="
    return "<a href='cad_capeante_audit.php?id_capeante={$id_capeante}' target='_blank'>Link Capeante #{$id_capeante}</a>";
  }, $mensagem->mensagem);

  echo "<div class='message $messageClass' data-id='{$mensagem->id_mensagem}'>
            <span class='text'>{$mensagemComLink}</span>
            <span class='message-date'>$dataMensagem</span>
          </div>";
}
?>