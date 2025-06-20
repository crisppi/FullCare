<?php

class Patologia
{
  public $id_patologia;
  public $patologia_pat;
  public $dias_pato;
  public $fk_usuario_pat;
  public $fk_cid_10_pat;
  public $usuario_create_pat;
  public $cat;
  public $descricao;
  public $data_create_pat;
}

interface patologiaDAOInterface
{

  public function buildpatologia($patologia);
  public function findAll();
  public function getpatologia();
  public function findById($id_patologia);
  public function findByPatologia($pesquisa_pat);
  public function create(patologia $patologia);
  public function update(patologia $patologia);
  public function destroy($id_patologia);
  public function findGeral();

  public function selectAllPatologia($where = null, $order = null, $limit = null);
  public function QtdPatologia();
};