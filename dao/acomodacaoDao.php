<?php

require_once("./models/acomodacao.php");
require_once("./models/hospital.php");
require_once("./models/message.php");

// Review DAO
require_once("dao/acomodacaoDao.php");

class acomodacaoDAO implements acomodacaoDAOInterface
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

    private function safeWhere(?string $where): string
    {
        $where = trim((string)$where);
        if ($where === '') {
            return '';
        }
        if (preg_match('/(;|--|\/\*|\*\/|\bUNION\b|\bSLEEP\b|\bBENCHMARK\b|\bINTO\s+OUTFILE\b|\bLOAD_FILE\b)/i', $where)) {
            throw new InvalidArgumentException('Filtro WHERE inválido.');
        }
        return 'WHERE ' . $where;
    }

    private function safeOrder(?string $order): string
    {
        $order = trim((string)$order);
        if ($order === '') {
            return '';
        }
        $parts = array_map('trim', explode(',', $order));
        $clean = [];
        foreach ($parts as $part) {
            if ($part === '') {
                continue;
            }
            if (!preg_match('/^[a-zA-Z0-9_\\.]+(\\s+(ASC|DESC))?$/i', $part)) {
                throw new InvalidArgumentException('Ordenação inválida.');
            }
            $clean[] = $part;
        }
        return $clean ? 'ORDER BY ' . implode(', ', $clean) : '';
    }

    private function safeLimit(?string $limit): string
    {
        $limit = trim((string)$limit);
        if ($limit === '') {
            return '';
        }
        if (!preg_match('/^\\d+(\\s*,\\s*\\d+)?$/', $limit)) {
            throw new InvalidArgumentException('Limite inválido.');
        }
        return 'LIMIT ' . $limit;
    }

    public function buildacomodacao($acomodacao)
    {
        $acomod = new acomodacao();

        $acomod->id_acomodacao = $acomodacao["id_acomodacao"];
        $acomod->acomodacao_aco = $acomodacao["acomodacao_aco"];
        $acomod->fk_hospital = $acomodacao["fk_hospital"];
        $acomod->data_contrato_aco = $acomodacao["data_contrato_aco"];
        $acomod->usuario_create_acomodacao = $acomodacao["usuario_create_acomodacao"];
        $acomod->data_create_acomodacao = $acomodacao["data_create_acomodacao"];
        $acomod->fk_usuario_acomodacao = $acomodacao["fk_usuario_acomodacao"];
        $acomod->valor_aco = $acomodacao["valor_aco"];
        return $acomodacao;
    }

    public function joinAcomodacaoHospital()
    {

        $acomodacao = [];

        $stmt = $this->conn->query("SELECT ac.id_acomodacao, ac.data_contrato_aco, ac.valor_aco, ac.acomodacao_aco, ho.id_hospital, ho.nome_hosp
         FROM tb_acomodacao ac 
         iNNER JOIN tb_hospital as ho On  
         ac.fk_hospital = ho.id_hospital
         ORDER BY ac.id_acomodacao DESC");
        $stmt->execute();
        $acomodacao = $stmt->fetchAll();
        return $acomodacao;
    }

    // mostrar acomocacao por id_acomodacao
    public function joinAcomodacaoHospitalshow($id_acomodacao)

    {
        $stmt = $this->conn->prepare("SELECT ac.id_acomodacao, ac.data_contrato_aco, ac.fk_hospital, ac.valor_aco, ac.acomodacao_aco, ho.id_hospital, ho.nome_hosp
         FROM tb_acomodacao ac          
         iNNER JOIN tb_hospital as ho On  
         ac.fk_hospital = ho.id_hospital
         where id_acomodacao = :id_acomodacao");
        $stmt->bindValue(':id_acomodacao', (int)$id_acomodacao, PDO::PARAM_INT);

        $stmt->execute();

        $acomodacao = $stmt->fetch();
        return $acomodacao;
    }
    public function findAll() {}

    public function getacomodacao()
    {

        $acomodacao = [];

        $stmt = $this->conn->query("SELECT * FROM tb_acomodacao ORDER BY id_acomodacao DESC");

        $stmt->execute();

        if ($stmt->rowCount() > 0) {

            $acomodacaoArray = $stmt->fetchAll();

            foreach ($acomodacaoArray as $acomodacao) {
                $acomodacao[] = $this->buildacomodacao($acomodacao);
            }
        }

        return $acomodacao;
    }

    public function getacomodacaoByNome($nome)
    {

        $acomodacao = [];

        $stmt = $this->conn->prepare("SELECT * FROM tb_acomodacao
                                    WHERE acomodacao_aco = :acomodacao_aco
                                    ORDER BY id_acomodacao DESC");

        $stmt->bindParam(":acomodacao_aco", $acomodacao_aco);

        $stmt->execute();

        return $acomodacao;
    }

    public function getHospitalByAcomodacao($nome)
    {

        $acomod_hosp = [];

        $stmt = $this->conn->prepare("SELECT * FROM tb_hospital
                                    WHERE acomodacao_aco = :acomodacao_aco
                                    ORDER BY id_acomodacao DESC");

        $stmt->bindParam(":acomodacao_aco", $acomodacao_aco);

        $stmt->execute();

        return $acomod_hosp;
    }

    public function findById($id_acomodacao)
    {
        $acomodacao = [];
        $stmt = $this->conn->prepare("SELECT * FROM tb_acomodacao
                                    WHERE id_acomodacao = :id_acomodacao");
        $stmt->bindValue(":id_acomodacao", (int)$id_acomodacao, PDO::PARAM_INT);
        $stmt->execute();

        $data = $stmt->fetch();
        $acomodacao = $this->buildacomodacao($data);

        return $acomodacao;
    }

    public function findByIdUpdate($id_acomodacao)
    {

        $acomodacao = [];

        $stmt = $this->conn->prepare("SELECT * FROM tb_acomodacao
                                    WHERE id_acomodacao = :id_acomodacao");

        $stmt->bindValue(":id_acomodacao", $id_acomodacao);

        $stmt->execute();

        if ($stmt->rowCount() > 0) {

            $acomodacaoArray = $stmt->fetchAll();

            foreach ($acomodacaoArray as $acomodacao) {
                $acomodacao[] = $this->buildacomodacao($acomodacao);
            }
        }

        return $acomodacao;
    }

    public function create(acomodacao $acomodacao)
    {

        $stmt = $this->conn->prepare("INSERT INTO tb_acomodacao (
        acomodacao_aco, 
        fk_hospital,
        valor_aco,
        fk_usuario_acomodacao,
        usuario_create_acomodacao,
        data_create_acomodacao,
        data_contrato_aco
      ) VALUES (
        :acomodacao_aco, 
        :fk_hospital,
        :valor_aco,
        :fk_usuario_acomodacao,
        :usuario_create_acomodacao,
        :data_create_acomodacao,
        :data_contrato_aco
            )");

        $stmt->bindParam(":acomodacao_aco", $acomodacao->acomodacao_aco);
        $stmt->bindParam(":fk_hospital", $acomodacao->fk_hospital);
        $stmt->bindParam(":valor_aco", $acomodacao->valor_aco);
        $stmt->bindParam(":fk_usuario_acomodacao", $acomodacao->fk_usuario_acomodacao);
        $stmt->bindParam(":usuario_create_acomodacao", $acomodacao->usuario_create_acomodacao);
        $stmt->bindParam(":data_create_acomodacao", $acomodacao->data_create_acomodacao);
        $stmt->bindParam(":data_contrato_aco", $acomodacao->data_contrato_aco);

        $stmt->execute();

        // Mensagem de sucesso por adicionar acomodacao
        $this->message->setMessage("acomodacao adicionado com sucesso!", "success", "list_acomodacao.php");
    }

    public function update($acomodacao)
    {

        $stmt = $this->conn->prepare("UPDATE tb_acomodacao SET
        acomodacao_aco = :acomodacao_aco,
        valor_aco = :valor_aco,
        fk_hospital = :fk_hospital,
        data_contrato_aco = :data_contrato_aco
        
        WHERE id_acomodacao = :id_acomodacao 
      ");

        $stmt->bindParam(":acomodacao_aco", $acomodacao['acomodacao_aco']);
        $stmt->bindParam(":valor_aco", $acomodacao['valor_aco']);
        $stmt->bindParam(":fk_hospital", $acomodacao['fk_hospital']);
        $stmt->bindParam(":id_acomodacao", $acomodacao['id_acomodacao']);
        $stmt->bindParam(":data_contrato_aco", $acomodacao['data_contrato_aco']);

        $stmt->execute();

        // Mensagem de sucesso por editar acomodacao
        $this->message->setMessage("acomodacao atualizado com sucesso!", "success", "list_acomodacao.php");
    }

    public function destroy($id_acomodacao)
    {
        $stmt = $this->conn->prepare("DELETE FROM tb_acomodacao WHERE id_acomodacao = :id_acomodacao");

        $stmt->bindParam(":id_acomodacao", $id_acomodacao);

        $stmt->execute();

        // Mensagem de sucesso por remover filme
        $this->message->setMessage("acomodacao removido com sucesso!", "success", "list_acomodacao.php");
    }


    public function findGeral()
    {

        $acomodacao = [];

        $stmt = $this->conn->query("SELECT * FROM tb_acomodacao ORDER BY id_acomodacao DESC");

        $stmt->execute();

        $acomodacao = $stmt->fetchAll();

        return $acomodacao;
    }

    public function findGeralByHospital($id_hospital)
    {
        $stmt = $this->conn->prepare("SELECT * FROM tb_acomodacao WHERE fk_hospital = :id_hospital ORDER BY id_acomodacao DESC");
        $stmt->bindValue(':id_hospital', (int)$id_hospital, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }



    // MODELO DE FILTRO COM SELECT ATUAL COM FILTROS E PAGINACAO

    public function selectAllacomodacao($where = null, $order = null, $limit = null)
    {
        $where = $this->safeWhere($where);
        $order = $this->safeOrder($order);
        $limit = $this->safeLimit($limit);

        //MONTA A QUERY
        $query = $this->conn->prepare('SELECT 
        ac.id_acomodacao,  
        ac.acomodacao_aco, 
        ac.valor_aco, 
        ac.data_contrato_aco,   
        ho.id_hospital, 
        ho.nome_hosp 
    FROM tb_acomodacao ac 

        iNNER JOIN tb_hospital as ho On  
        ac.fk_hospital = ho.id_hospital ' . $where . ' ' . $order . ' ' . $limit);

        $query->execute();

        $acomodacao = $query->fetchAll();

        return $acomodacao;
    }

    public function QtdAcomodacao($where = null, $order = null, $limite = null)
    {
        $hospital = [];
        $where = $this->safeWhere($where);
        $order = $this->safeOrder($order);
        $limite = $this->safeLimit($limite);

        $stmt = $this->conn->prepare('SELECT * ,COUNT(id_acomodacao) as qtd FROM tb_acomodacao ' . $where . ' ' . $order . ' ' . $limite);

        $stmt->execute();

        $QtdTotalAnt = $stmt->fetch();

        return $QtdTotalAnt;
    }

    public function calcularSaving($de, $para, $qtd)
    {
        $sql = "SELECT (MAX(de) - MAX(para)) * :qtd AS saving FROM (
            SELECT
                CASE WHEN id_acomodacao = :de THEN valor_aco END AS de,
                CASE WHEN id_acomodacao = :para THEN valor_aco END AS para
            FROM tb_acomodacao
            WHERE id_acomodacao IN (:de_in, :para_in)
        ) AS valores";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':qtd', (float)$qtd);
        $stmt->bindValue(':de', (int)$de, PDO::PARAM_INT);
        $stmt->bindValue(':para', (int)$para, PDO::PARAM_INT);
        $stmt->bindValue(':de_in', (int)$de, PDO::PARAM_INT);
        $stmt->bindValue(':para_in', (int)$para, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch();
    }
}
