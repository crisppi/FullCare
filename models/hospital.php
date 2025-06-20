<?php

class Hospital
{
  public $id_hospital;
  public $nome_hosp;
  public $cidade_hosp;
  public $estado_hosp;
  public $endereco_hosp;
  public $email01_hosp;
  public $email02_hosp;
  public $telefone01_hosp;
  public $telefone02_hosp;
  public $numero_hosp;
  public $bairro_hosp;
  public $cnpj_hosp;
  public $ativo_hosp;
  public $coordMedico_hosp;
  public $emailCoordMedico_hosp;
  public $coordFat_hosp;
  public $email_coordFat_hosp;
  public $data_create_hosp;
  public $usuario_create_hosp;
  public $fk_usuario_hosp;
  public $longitude_hosp;
  public $latitude_hosp;
  public $diretor_hosp;
  public $coordenador_fat_hosp;
  public $coordenador_medico_hosp;
  public $logo_hosp;
  public $cep_hosp;
  public $deletado_hosp;
}

interface HospitalDAOInterface
{

  public function buildHospital($hospital);
  public function findAll();
  public function gethospital();
  public function findById($id_hospital);
  public function findByHosp($pesquisa_nome);
  public function create(Hospital $hospital);
  public function update(Hospital $hospital);
  public function destroy($id_hospital);
  public function findGeral();

  public function selectAllHospital($where = null, $order = null, $limit = null);
  public function QtdHospital();
};
