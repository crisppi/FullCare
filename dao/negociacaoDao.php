<?php

require_once("./models/negociacao.php");
require_once("./models/message.php");

// Review DAO
require_once("dao/negociacaoDao.php");

class negociacaoDAO implements negociacaoDAOInterface
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

    public function buildNegociacao($data)
    {
        $negociacao = new Negociacao();

        $negociacao->id_negociacao = $data["id_negociacao"];
        $negociacao->fk_id_int = $data["fk_id_int"];

        $negociacao->qtd = $data["qtd"];
        $negociacao->troca_de = $data["troca_de"];
        $negociacao->troca_para = $data["troca_para"];
        $negociacao->saving = $data["saving"];

        $negociacao->fk_usuario_neg = $data["fk_usuario_neg"];

        return $negociacao;
    }
    public function joinnegociacaoHospitalshow($id_negociacao)
    {
        $stmt = $this->conn->query("SELECT ac.id_negociacao, ac.fk_hospital, ac.valor_aco, ac.negociacao_aco, ho.id_hospital, ho.nome_hosp
         FROM tb_negociacao ac          
         iNNER JOIN tb_hospital as ho On  
         ac.fk_hospital = ho.id_hospital
         where id_negociacao = $id_negociacao   
         ");

        $stmt->execute();

        $negociacao = $stmt->fetch();
        return $negociacao;
    }
    public function findAll()
    {
        $negociacao = [];

        $stmt = $this->conn->prepare("SELECT * FROM tb_negociacao
        ORDER BY id_negociacao asc");

        $stmt->execute();

        $negociacao = $stmt->fetchAll();
        return $negociacao;
    }

    public function findByNegociacao($pesquisa_nome)
    {

        $usuario = [];

        $stmt = $this->conn->prepare("SELECT * FROM tb_negociacao
                                    WHERE nome_est LIKE :nome_est ");

        $stmt->bindValue(":nome_est", '%' . $pesquisa_nome . '%');

        $stmt->execute();

        $usuario = $stmt->fetchAll();
        return $usuario;
    }
    public function getnegociacao()
    {

        $negociacao = [];

        $stmt = $this->conn->query("SELECT * FROM tb_negociacao ORDER BY id_negociacao asc");

        $stmt->execute();

        if ($stmt->rowCount() > 0) {

            $negociacaoArray = $stmt->fetchAll();

            foreach ($negociacaoArray as $negociacao) {
                $negociacao[] = $this->buildNegociacao($negociacao);
            }
        }

        return $negociacao;
    }

    public function getnegociacaoByNome($nome)
    {

        $negociacao = [];

        $stmt = $this->conn->prepare("SELECT * FROM tb_negociacao
                                    WHERE nome_est = :nome_est
                                    ORDER BY id_negociacao asc");

        $stmt->bindParam(":nome_est", $nome_est);

        $stmt->execute();

        if ($stmt->rowCount() > 0) {

            $negociacaoArray = $stmt->fetchAll();

            foreach ($negociacaoArray as $negociacao) {
                $negociacao[] = $this->buildNegociacao($negociacao);
            }
        }

        return $negociacao;
    }

    public function findById($id_negociacao)
    {
        $negociacao = [];
        $stmt = $this->conn->prepare("SELECT * FROM tb_negociacao
                                    WHERE id_negociacao = :id_negociacao");

        $stmt->bindParam(":id_negociacao", $id_negociacao);
        $stmt->execute();

        $data = $stmt->fetch();
        //var_dump($data);
        $negociacao = $this->buildNegociacao($data);

        return $negociacao;
    }



    public function create(Negociacao $negociacao)
    {
        $stmt = $this->conn->prepare("
        INSERT INTO tb_negociacao (
            fk_id_int, 
            troca_de, 
            troca_para, 
            qtd, 
            saving, 
            fk_usuario_neg,
            data_inicio_neg,
            data_fim_neg,
            tipo_negociacao
        ) VALUES (
            :fk_id_int, 
            :troca_de, 
            :troca_para, 
            :qtd, 
            :saving, 
            :fk_usuario_neg,
            :data_inicio_neg,
            :data_fim_neg,
            :tipo_negociacao
        )
    ");

        $stmt->bindParam(":fk_id_int", $negociacao->fk_id_int);
        $stmt->bindParam(":troca_de", $negociacao->troca_de);
        $stmt->bindParam(":troca_para", $negociacao->troca_para);
        $stmt->bindParam(":qtd", $negociacao->qtd);
        $stmt->bindParam(":saving", $negociacao->saving);
        $stmt->bindParam(":fk_usuario_neg", $negociacao->fk_usuario_neg);
        $stmt->bindParam(":data_inicio_neg", $negociacao->data_inicio_neg);
        $stmt->bindParam(":data_fim_neg", $negociacao->data_fim_neg);
        $stmt->bindParam(":tipo_negociacao", $negociacao->tipo_negociacao);

        $stmt->execute();

        $this->message->setMessage("Negociação adicionada com sucesso!", "success", "cad_internacao_niveis.php");
    }


    public function update(negociacao $negociacao) // ainda nao atualizado
    {

        $stmt = $this->conn->prepare("UPDATE tb_negociacao SET
        nome_est = :nome_est,
        endereco_est = :endereco_est,
        email01_est = :email01_est,
        email02_est = :email02_est,
        cnpj_est = :cnpj_est,
        numero_est = :numero_est,
        telefone01_est = :telefone01_est,
        telefone02_est = :telefone02_est,
        cidade_est = :cidade_est,
        bairro_est = :bairro_est

        WHERE id_negociacao = :id_negociacao 
      ");

        $stmt->bindParam(":fk_id_int", $negociacao->fk_id_int);
        $stmt->bindParam(":qtd_1", $negociacao->qtd_1);
        $stmt->bindParam(":qtd_2", $negociacao->qtd_2);
        $stmt->bindParam(":qtd_3", $negociacao->qtd_3);
        $stmt->bindParam(":troca_de_1", $negociacao->troca_de_1);
        $stmt->bindParam(":troca_de_2", $negociacao->troca_de_2);
        $stmt->bindParam(":troca_de_3", $negociacao->troca_de_3);
        $stmt->bindParam(":troca_para_1", $negociacao->troca_para_1);
        $stmt->bindParam(":troca_para_2", $negociacao->troca_para_2);
        $stmt->bindParam(":troca_para_3", $negociacao->troca_para_3);

        $stmt->bindParam(":id_negociacao", $negociacao->id_negociacao);
        $stmt->execute();

        // Mensagem de sucesso por editar negociacao
        $this->message->setMessage("negociacao atualizado com sucesso!", "success", "list_negociacao.php");
    }

    public function destroy($id_negociacao)
    {
        $stmt = $this->conn->prepare("DELETE FROM tb_negociacao WHERE id_negociacao = :id_negociacao");

        $stmt->bindParam(":id_negociacao", $id_negociacao);

        $stmt->execute();

        // Mensagem de sucesso por remover filme
        $this->message->setMessage("negociacao removido com sucesso!", "success", "list_negociacao.php");
    }

    // METODO DE PROCURA POR ID DA INTERNACAO PARA UTILIZACAO NO FORM NEGOCIACAO    
    public function findByLastId($lastId)
    {

        $negociacao = [];

        $stmt = $this->conn->query("SELECT 
        ng.id_negociacao,
        ng.fk_id_int, 
        ng.qtd_1, 
        ng.qtd_2, 
        ng.qtd_3, 
        ng.troca_de_1, 
        ng.troca_de_2, 
        ng.troca_de_3, 
        ng.troca_para_1, 
        ng.troca_para_2, 
        ng.troca_para_3,
        ng.fk_usuario_neg, 
        pa.id_paciente,
        pa.nome_pac,
        ho.id_hospital, 
        ho.nome_hosp,
        ac.id_internacao,
        ac.internado_int,
        ac.fk_hospital_int,
        ac.data_intern_int,
        ac.fk_paciente_int,
        ad.fk_hospital,
        ad.valor_aco,
        ad.acomodacao_aco
        
        FROM tb_negociacao ng 
    
            left JOIN tb_internacao AS ac ON
            ng.fk_id_int = ac.id_internacao
            
            INNER JOIN tb_hospital AS ho ON  
            ac.fk_hospital_int = ho.id_hospital
    
            INNER JOIN tb_paciente AS pa ON
            ac.fk_paciente_int = pa.id_paciente 
            
            INNER JOIN tb_acomodacao AS ad ON  
            ho.id_hospital = ad.fk_hospital
            
            WHERE ac.id_internacao = $lastId ");

        $stmt->execute();

        $negociacao = $stmt->fetchAll();

        return $negociacao;
    }

    // METODO DE PROCURA SEM FILTROS
    public function findGeral()
    {

        $negociacao = [];

        $stmt = $this->conn->query("SELECT 
        ng.id_negociacao,
        ng.fk_id_int, 
        ng.qtd_1, 
        ng.qtd_2, 
        ng.qtd_3, 
        ng.troca_de_1, 
        ng.troca_de_2, 
        ng.troca_de_3, 
        ng.troca_para_1, 
        ng.troca_para_2, 
        ng.troca_para_3,
        ng.fk_usuario_neg, 
        pa.id_paciente,
        pa.nome_pac,
        ho.id_hospital, 
        ho.nome_hosp,
        ac.id_internacao,
        ac.internado_int,
        ac.fk_hospital_int,
        ac.data_intern_int,
        ac.fk_paciente_int
        
        FROM tb_negociacao ng 
    
            INNER JOIN tb_internacao AS ac ON
            ng.fk_id_int = ac.id_internacao
            
            INNER JOIN tb_hospital AS ho ON  
            ac.fk_hospital_int = ho.id_hospital
    
            INNER JOIN tb_paciente AS pa ON
            ac.fk_paciente_int = pa.id_paciente 
        ");

        $stmt->execute();

        $negociacao = $stmt->fetchAll();

        return $negociacao;
    }

    public function selectAllnegociacao($where = null, $order = null, $limit = null)
    {
        //DADOS DA QUERY
        $where = strlen($where) ? 'WHERE ' . $where : '';
        $order = strlen($order) ? 'ORDER BY ' . $order : '';
        $limit = strlen($limit) ? 'LIMIT ' . $limit : '';

        //MONTA A QUERY
        $query = $this->conn->query('SELECT 
        ng.id_negociacao,
        ng.fk_id_int, 
        ng.qtd_1, 
        ng.qtd_2, 
        ng.qtd_3, 
        ng.troca_de_1, 
        ng.troca_de_2, 
        ng.troca_de_3, 
        ng.troca_para_1, 
        ng.troca_para_2, 
        ng.troca_para_3, 
        ng.fk_usuario_neg,
        pa.id_paciente,
        pa.nome_pac,
        ho.id_hospital, 
        ho.nome_hosp,
        ac.id_internacao,
        ac.internado_int,
        ac.fk_hospital_int,
        ac.data_intern_int,
        ac.fk_paciente_int,
        ad.fk_hospital,
        ad.valor_aco,
        ad.acomodacao_aco
        
        FROM tb_negociacao ng 
    
            INNER JOIN tb_internacao AS ac ON
            ng.fk_id_int = ac.id_internacao
            
            INNER JOIN tb_hospital AS ho ON  
            ac.fk_hospital_int = ho.id_hospital

            INNER JOIN tb_acomodacao AS ad ON  
            ho.id_hospital = ad.fk_hospital
    
            INNER JOIN tb_paciente AS pa ON
            ac.fk_paciente_int = pa.id_paciente  ' . $where . ' ' . $order . ' ' . $limit);

        $query->execute();

        $negociacao = $query->fetchAll();

        return $negociacao;
    }
    public function findMaxVis()
    {

        $gestao = [];

        $stmt = $this->conn->query("SELECT max(id_visita) as ultimoReg from tb_visita");

        $stmt->execute();

        $gestaoIdMaxVis = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $gestaoIdMaxVis;
    }
    public function Qtdnegociacao($where = null, $order = null, $limite = null)
    {
        $negociacao = [];
        //DADOS DA QUERY
        $where = strlen($where) ? 'WHERE ' . $where : '';
        $order = strlen($order) ? 'ORDER BY ' . $order : '';
        $limite = strlen($limite) ? 'LIMIT ' . $limite : '';

        $stmt = $this->conn->query('SELECT * ,COUNT(id_negociacao) as qtd FROM tb_negociacao ' . $where . ' ' . $order . ' ' . $limite);

        $stmt->execute();

        $QtdTotalEst = $stmt->fetch();

        return $QtdTotalEst;
    }

    public function existeNegociacao($negociacao)
    {
        $query = "SELECT COUNT(*) AS total FROM negociacoes 
                  WHERE fk_id_int = :fk_id_int 
                  AND troca_de = :troca_de 
                  AND troca_para = :troca_para 
                  AND qtd = :qtd 
                  AND saving = :saving";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':fk_id_int', $negociacao->fk_id_int);
        $stmt->bindParam(':troca_de', $negociacao->troca_de);
        $stmt->bindParam(':troca_para', $negociacao->troca_para);
        $stmt->bindParam(':qtd', $negociacao->qtd);
        $stmt->bindParam(':saving', $negociacao->saving);
        $stmt->execute();

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($result && isset($result['total'])) {
            return $result['total'] > 0;
        }

        return false; // Ou trate como desejar
    }

}