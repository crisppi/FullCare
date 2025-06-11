<?php

require_once("./models/tuss.php");
require_once("./models/message.php");

// Review DAO
require_once("dao/tussDao.php");

class tussDAO implements tussDAOInterface
{

    private $conn;
    private $url;
    public $message;

    public function __construct(PDO $conn, $url)
    {
        $this->conn = $conn;
        $this->url = $url;
        $this->message = new Message($url);
    }

    public function buildtuss($data)
    {
        $tuss = new tuss();

        // $tuss->id_tuss = $data["id_tuss"];
        $tuss->tuss_solicitado = $data["tuss_solicitado"];
        $tuss->tuss_liberado_sn = $data["tuss_liberado_sn"];
        $tuss->qtd_tuss_solicitado = $data["qtd_tuss_solicitado"];
        $tuss->qtd_tuss_liberado = $data["qtd_tuss_liberado"];
        $tuss->data_realizacao_tuss = $data["data_realizacao_tuss"];
        $tuss->fk_int_tuss = $data["fk_int_tuss"];
        $tuss->fk_vis_tuss = $data["fk_vis_tuss"];

        $tuss->fk_usuario_tuss = $data["fk_usuario_tuss"];
        $tuss->data_create_tuss = $data["data_create_tuss"];


        return $tuss;
    }

    public function findAll()
    {
        $tuss = [];

        $stmt = $this->conn->prepare("SELECT * FROM tb_tuss
        ORDER BY id_tuss asc");

        $stmt->execute();

        $tuss = $stmt->fetchAll();
        return $tuss;
    }

    public function create(tuss $tuss)
    {

        $stmt = $this->conn->prepare("INSERT INTO tb_tuss (
        fk_usuario_tuss,
        fk_int_tuss, 
        fk_vis_tuss, 
        data_create_tuss,
        tuss_solicitado, 
        tuss_liberado_sn, 
        qtd_tuss_solicitado, 
        qtd_tuss_liberado,
        data_realizacao_tuss
        
      ) VALUES (
        :fk_usuario_tuss,
        :fk_int_tuss, 
        :fk_vis_tuss, 
        :data_create_tuss,
        :tuss_solicitado, 
        :tuss_liberado_sn, 
        :qtd_tuss_solicitado, 
        :qtd_tuss_liberado,
        :data_realizacao_tuss
        
     )");

        $stmt->bindParam(":fk_usuario_tuss", $tuss->fk_usuario_tuss);
        $stmt->bindParam(":fk_int_tuss", $tuss->fk_int_tuss);
        $stmt->bindParam(":fk_vis_tuss", $tuss->fk_vis_tuss);
        $stmt->bindParam(":data_create_tuss", $tuss->data_create_tuss);

        $stmt->bindParam(":tuss_solicitado", $tuss->tuss_solicitado);
        $stmt->bindParam(":tuss_liberado_sn", $tuss->tuss_liberado_sn);
        $stmt->bindParam(":qtd_tuss_solicitado", $tuss->qtd_tuss_solicitado);
        $stmt->bindParam(":qtd_tuss_liberado", $tuss->qtd_tuss_liberado);
        $stmt->bindParam(":data_realizacao_tuss", $tuss->data_realizacao_tuss);

        $stmt->execute();

        // Mensagem de sucesso por adicionar filme
        $this->message->setMessage("tuss adicionado com sucesso!", "success", "list_tuss.php");
    }

    public function update(tuss $tuss)
    {

        $stmt = $this->conn->prepare("UPDATE tb_tuss SET
        tuss_solicitado = :tuss_solicitado,
        ativo_tuss = :ativo_tuss,
        tuss_liberado_sn = :tuss_liberado_sn,
        qtd_tuss_solicitado = :qtd_tuss_solicitado,
        fk_int_tuss = :fk_int_tuss,
        fk_vis_tuss = :fk_vis_tuss,
        data_realizacao_tuss = :data_realizacao_tuss,
        fk_usuario_tuss = :fk_usuario_tuss,
        data_create_tuss = :data_create_tuss,
        qtd_tuss_liberado = :qtd_tuss_liberado,
        glosa_tuss = :glosa_tuss

        WHERE id_tuss = :id_tuss 
      ");

        $stmt->bindParam(":tuss_solicitado", $tuss->tuss_solicitado);
        $stmt->bindParam(":tuss_liberado_sn", $tuss->tuss_liberado_sn);
        $stmt->bindParam(":qtd_tuss_solicitado", $tuss->qtd_tuss_solicitado);
        $stmt->bindParam(":fk_int_tuss", $tuss->fk_int_tuss);
        $stmt->bindParam(":fk_usuario_tuss", $tuss->fk_usuario_tuss);
        $stmt->bindParam(":fk_vis_tuss", $tuss->fk_vis_tuss);
        $stmt->bindParam(":data_realizacao_tuss", $tuss->data_realizacao_tuss);
        $stmt->bindParam(":data_create_tuss", $tuss->data_create_tuss);
        $stmt->bindParam(":qtd_tuss_liberado", $tuss->qtd_tuss_liberado);
        $stmt->bindParam(":glosa_tuss", $tuss->glosa_tuss);

        $stmt->bindParam(":id_tuss", $tuss->id_tuss);
        $stmt->execute();

        // Mensagem de sucesso por editar tuss
        $this->message->setMessage("tuss atualizado com sucesso!", "success", "list_tuss.php");
    }

    public function destroy($id_tuss)
    {
        $stmt = $this->conn->prepare("DELETE FROM tb_tuss WHERE id_tuss = :id_tuss");

        $stmt->bindParam(":id_tuss", $id_tuss);

        $stmt->execute();

        // Mensagem de sucesso por remover filme
        $this->message->setMessage("tuss removido com sucesso!", "success", "list_tuss.php");
    }


    public function findGeral()
    {

        $tuss = [];

        $stmt = $this->conn->query("SELECT * FROM tb_tuss ORDER BY tuss_solicitado asc");

        $stmt->execute();

        $tuss = $stmt->fetchAll();

        return $tuss;
    }

    public function selectAllTUSSByIntern($id_internacao)
    {
        $query = $this->conn->query('
        SELECT 
            ac.id_internacao, 
            ac.data_visita_int, 
            ac.fk_paciente_int, 
            ac.fk_hospital_int, 
            ac.internado_int,
            ac.visita_no_int,
            ac.senha_int,
            tu.fk_int_tuss,
            tu.tuss_solicitado,
            tu.tuss_liberado_sn,
            tu.qtd_tuss_solicitado,
            tu.qtd_tuss_liberado,
            tu.data_realizacao_tuss,
            tua.terminologia_tuss
        FROM tb_tuss AS tu 
            LEFT JOIN tb_internacao ac ON ac.id_internacao = tu.fk_int_tuss
            LEFT JOIN (
                SELECT DISTINCT cod_tuss, terminologia_tuss
                FROM tb_tuss_ans
            ) tua ON tua.cod_tuss = tu.tuss_solicitado
        WHERE tu.fk_int_tuss = ' . $id_internacao);

        $query->execute();

        $hospital = $query->fetchAll();

        return $hospital;
    }



    public function selectAllInternacaoTUSS($where = null, $order = null, $limit = null)
    {
        //DADOS DA QUERY
        $where = strlen($where) ? 'WHERE ' . $where : '';
        $order = strlen($order) ? 'ORDER BY ' . $order : '';
        $limit = strlen($limit) ? 'LIMIT ' . $limit : '';

        $group = ' GROUP BY ac.id_internacao ';

        //MONTA A QUERY
        $query = $this->conn->query('SELECT 
        ac.id_internacao, 
        ac.data_visita_int, 
        ac.fk_paciente_int, 
        ac.fk_hospital_int, 
        ac.internado_int,
        ac.visita_no_int,
        ac.senha_int,
        pa.id_paciente,
        pa.nome_pac,
        ho.id_hospital, 
        vi.fk_internacao_vis,
        vi.rel_visita_vis,
        vi.data_visita_vis,
        ho.id_hospital,
        ho.nome_hosp,
        hos.fk_hospital_user,
        hos.fk_usuario_hosp,
        se.id_usuario,
        se.usuario_user,
        se.cargo_user,
        tu.fk_int_tuss,
        tu.tuss_solicitado,
        tu.tuss_liberado_sn,
        tu.qtd_tuss_solicitado,
        tu.qtd_tuss_liberado,
        tu.data_realizacao_tuss
        
        FROM tb_tuss AS tu 
        
            LEFT JOIN tb_internacao ac on
            ac.id_internacao = tu.fk_int_tuss
    
            LEFT JOIN tb_hospital AS ho ON  
            ac.fk_hospital_int = ho.id_hospital
            
			LEFT JOIN tb_hospitalUser AS hos ON
            hos.fk_hospital_user = ho.id_hospital
            
			LEFT JOIN tb_user AS se ON  
            se.id_usuario = hos.fk_usuario_hosp
           
            LEFT JOIN tb_paciente AS pa ON
            ac.fk_paciente_int = pa.id_paciente 

            LEFT join tb_visita as vi on
            ac.id_internacao = vi.fk_internacao_vis

            
                        
             ' . $where . ' ' . $group . ' ' . $order . ' ' . $limit);

        $query->execute();

        $hospital = $query->fetchAll();

        return $hospital;
    }

    public function selectAlltuss($where = null, $order = null, $limit = null)
    {
        //DADOS DA QUERY
        $where = strlen($where) ? 'WHERE ' . $where : '';
        $order = strlen($order) ? 'ORDER BY ' . $order : '';
        $limit = strlen($limit) ? 'LIMIT ' . $limit : '';

        //MONTA A QUERY
        $query = $this->conn->query('SELECT * FROM tb_tuss ' . $where . ' ' . $order . ' ' . $limit);

        $query->execute();

        $tuss = $query->fetchAll();

        return $tuss;
    }

    public function findByIdIntern($id_internacao)
    {
        //MONTA A QUERY
        $query = $this->conn->query('SELECT * FROM tb_tuss WHERE fk_int_tuss = ' . $id_internacao);

        $query->execute();

        $tuss = $query->fetchAll();

        return $tuss;
    }
}