<?php

require_once("./models/patologia.php");
require_once("./models/message.php");

// Review DAO
require_once("dao/patologiaDao.php");

class patologiaDAO implements patologiaDAOInterface
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

    public function buildpatologia($data)
    {
        $patologia = new patologia();

        $patologia->id_patologia = $data["id_patologia"];
        $patologia->patologia_pat = $data["patologia_pat"];
        $patologia->dias_pato = $data["dias_pato"];
        $patologia->fk_usuario_pat = $data["fk_usuario_pat"];
        $patologia->usuario_create_pat = $data["usuario_create_pat"];
        $patologia->data_create_pat = $data["data_create_pat"];
        $patologia->fk_usuario_pat = $data["fk_usuario_pat"];
        $patologia->fk_cid_10_pat = $data["fk_cid_10_pat"];
        $patologia->cat = $data["cat"];
        $patologia->descricao = $data["descricao"];

        return $patologia;
    }

    public function findAll()
    {
        $patologia = [];

        $stmt = $this->conn->prepare("SELECT * FROM tb_patologia p
        LEFT JOIN tb_cid c ON p.fk_cid_10_pat = c.id_cid
        ORDER BY id_patologia asc");

        $stmt->execute();

        $patologia = $stmt->fetchAll();
        return $patologia;
    }

    public function getpatologia()
    {

        $patologia = [];

        $stmt = $this->conn->query("SELECT * FROM tb_patologia ORDER BY id_patologia asc");

        $stmt->execute();

        if ($stmt->rowCount() > 0) {

            $patologiaArray = $stmt->fetchAll();

            foreach ($patologiaArray as $patologia) {
                $patologia[] = $this->buildpatologia($patologia);
            }
        }

        return $patologia;
    }

    public function getpatologiaByNome($nome)
    {

        $patologia = [];

        $stmt = $this->conn->prepare("SELECT * FROM tb_patologia p
                                    LEFT JOIN tb_cid c ON p.fk_cid_10_pat = c.id_cid
                                    WHERE patologia_pat = :patologia_pat
                                    ORDER BY id_patologia asc");

        $stmt->bindParam(":patologia_pat", $patologia_pat);

        $stmt->execute();

        if ($stmt->rowCount() > 0) {

            $patologiaArray = $stmt->fetchAll();

            foreach ($patologiaArray as $patologia) {
                $patologia[] = $this->buildpatologia($patologia);
            }
        }

        return $patologia;
    }

    public function findById($id_patologia)
    {
        $patologia = [];
        $stmt = $this->conn->prepare("SELECT * FROM tb_patologia p
                                    LEFT JOIN tb_cid c ON p.fk_cid_10_pat = c.id_cid
                                    WHERE id_patologia = :id_patologia");

        $stmt->bindParam(":id_patologia", $id_patologia);
        $stmt->execute();

        $data = $stmt->fetch();
        //var_dump($data);
        $patologia = $this->buildpatologia($data);

        return $patologia;
    }

    public function findByTitle($title)
    {

        $patologia = [];

        $stmt = $this->conn->prepare("SELECT * FROM FROM tb_patologia p
                                    LEFT JOIN tb_cid c ON p.fk_cid_10_pat = c.id_cid
                                    WHERE patologia_pat LIKE :patologia_pat");

        $stmt->bindValue(":title", '%' . $title . '%');

        $stmt->execute();

        if ($stmt->rowCount() > 0) {

            $patologiaArray = $stmt->fetchAll();

            foreach ($patologiaArray as $patologia) {
                $patologia[] = $this->buildpatologia($patologia);
            }
        }

        return $patologia;
    }

    public function create(patologia $patologia)
    {

        $stmt = $this->conn->prepare("INSERT INTO tb_patologia (
        patologia_pat,
        fk_usuario_pat,
        fk_cid_10_pat,
        dias_pato,
        usuario_create_pat,
        data_create_pat
      ) VALUES (
        :patologia_pat,
        :fk_usuario_pat,
        :fk_cid_10_pat,
        :dias_pato,
        :usuario_create_pat,
        :data_create_pat
     )");

        $stmt->bindParam(":patologia_pat", $patologia->patologia_pat);
        $stmt->bindParam(":dias_pato", $patologia->dias_pato);
        $stmt->bindParam(":fk_usuario_pat", $patologia->fk_usuario_pat);
        $stmt->bindParam(":fk_cid_10_pat", $patologia->fk_cid_10_pat);

        $stmt->bindParam(":usuario_create_pat", $patologia->usuario_create_pat);
        $stmt->bindParam(":data_create_pat", $patologia->data_create_pat);

        $stmt->execute();

        // Mensagem de sucesso por adicionar filme
        $this->message->setMessage("patologia adicionado com sucesso!", "success", "list_patologia.php");
    }

    public function findByPatologia($pesquisa_nome)
    {

        $patologia = [];

        $stmt = $this->conn->prepare("SELECT * FROM tb_patologia
                                    WHERE patologia_pat LIKE :patologia_pat ");

        $stmt->bindValue(":patologia_pat", '%' . $pesquisa_nome . '%');

        $stmt->execute();

        $patologia = $stmt->fetchAll();
        return $patologia;
    }
    public function update(patologia $patologia)
    {
        $stmt = $this->conn->prepare("UPDATE tb_patologia SET
        patologia_pat = :patologia_pat,
        dias_pato = :dias_pato,
        fk_cid_10_pat = :fk_cid_10_pat
        
        WHERE id_patologia = :id_patologia 
      ");

        $stmt->bindParam(":patologia_pat", $patologia->patologia_pat);
        $stmt->bindParam(":dias_pato", $patologia->dias_pato);
        $stmt->bindParam(":fk_cid_10_pat", $patologia->fk_cid_10_pat);

        // $stmt->bindParam(":usuario_create_pat", $patologia->usuario_create_pat);
        // $stmt->bindParam(":data_create_pat", $patologia->data_create_pat);
        // $stmt->bindParam(":fk_usuario_pat", $patologia->fk_usuario_pat);

        $stmt->bindParam(":id_patologia", $patologia->id_patologia);
        $stmt->execute();

        // Mensagem de sucesso por editar patologia
        $this->message->setMessage("patologia atualizado com sucesso!", "success", "list_patologia.php");
    }

    public function destroy($id_patologia)
    {
        $stmt = $this->conn->prepare("DELETE FROM tb_patologia WHERE id_patologia = :id_patologia");

        $stmt->bindParam(":id_patologia", $id_patologia);

        $stmt->execute();

        // Mensagem de sucesso por remover filme
        $this->message->setMessage("Patologia removido com sucesso!", "success", "list_patologia.php");
    }


    public function findGeral()
    {

        $patologia = [];

        $stmt = $this->conn->query("SELECT * FROM tb_patologia p
        LEFT JOIN tb_cid c ON p.fk_cid_10_pat = c.id_cid WHERE p.id_patologia > 1 ORDER BY id_patologia asc");

        $stmt->execute();

        $patologia = $stmt->fetchAll();

        return $patologia;
    }
    public function selectAllPatologia($where = null, $order = null, $limit = null)
    {
        //DADOS DA QUERY
        $where = strlen($where) ? 'WHERE ' . $where : ' ';
        $order = strlen($order) ? 'ORDER BY ' . $order : '';
        $limit = strlen($limit) ? 'LIMIT ' . $limit : '';

        //MONTA A QUERY
        $query = $this->conn->query('SELECT * FROM tb_patologia p
        LEFT JOIN tb_cid c ON p.fk_cid_10_pat = c.id_cid ' . $where . ' ' . $order . ' ' . $limit);
        $query->execute();

        $patologia = $query->fetchAll();

        return $patologia;
    }


    public function QtdPatologia($where = null, $order = null, $limite = null)
    {
        $hospital = [];
        //DADOS DA QUERY
        $where = strlen($where) ? 'WHERE ' . $where : '';
        $order = strlen($order) ? 'ORDER BY ' . $order : '';
        $limite = strlen($limite) ? 'LIMIT ' . $limite : '';

        $stmt = $this->conn->query('SELECT * ,COUNT(id_patologia) as qtd FROM tb_patologia p
        LEFT JOIN tb_cid c ON p.fk_cid_10_pat = c.id_cid ' . $where . ' ' . $order . ' ' . $limite);

        $stmt->execute();

        $QtdTotalPat = $stmt->fetch();

        return $QtdTotalPat;
    }
}




# Limita o número de registros a serem mostrados por página
$limite = 10;

# Se pg não existe atribui 1 a variável pg
$pg = (isset($_GET['pag'])) ? (int) $_GET['pag'] : 1;

# Atribui a variável inicio o inicio de onde os registros vão ser
# mostrados por página, exemplo 0 à 10, 11 à 20 e assim por diante
$inicio = ($pg * $limite) - $limite;
$pesquisa_hosp = "";
# seleciona o total de registros  
$sql_Total = 'SELECT id_patologia FROM tb_patologia';
