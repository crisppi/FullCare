<?php

class uti
{
  public $id_uti;
  public $fk_internacao_uti;
  public $fk_visita_uti;
  public $fk_user_uti;
  public $criterios_uti;
  public $data_alta_uti;
  public $data_internacao_uti;
  public $dva_uti;
  public $especialidade_uti;
  public $internacao_uti;
  public $internado_uti;
  public $just_uti;
  public $motivo_uti;
  public $rel_uti;
  public $saps_uti;
  public $score_uti;
  public $vm_uti;
  public $id_internacao;
  public $internacao_uti_int;
  public $internado_uti_int;
  public $data_create_uti;
  public $usuario_create_uti;
  public $glasgow_uti;
  public $suporte_vent_uti;
  public $dist_met_uti;
  public $justifique_uti;
  public $hora_internacao_uti;
}

interface utiDAOInterface
{

  public function builduti($uti);
  public function findAll();
  public function getuti();
  public function findById($id_uti);
  //public function findByTitle($title);
  public function create(uti $uti);
  public function update(uti $uti);
  public function destroy($id_uti);
  public function findGeral();
  public function findMaxUTI();
  public function findAltaUpdate($internadosUTI);
};