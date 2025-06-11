<?php

require_once("./models/paciente.php");
require_once("./models/message.php");

// Review DAO

class PacienteDAO implements PacienteDAOInterface
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

    public function buildPaciente($data)
    {
        $paciente = new Paciente();

        $paciente->id_paciente = $data["id_paciente"];
        $paciente->nome_pac = $data["nome_pac"];
        $paciente->nome_social_pac = $data["nome_social_pac"];
        $paciente->endereco_pac = $data["endereco_pac"];
        $paciente->sexo_pac = $data["sexo_pac"];
        $paciente->data_nasc_pac = $data["data_nasc_pac"];
        $paciente->cidade_pac = $data["cidade_pac"];
        $paciente->cpf_pac = $data["cpf_pac"];
        $paciente->telefone01_pac = $data["telefone01_pac"];
        $paciente->email01_pac = $data["email01_pac"];
        $paciente->email02_pac = $data["email02_pac"];
        $paciente->telefone02_pac = $data["telefone02_pac"];
        $paciente->numero_pac = $data["numero_pac"];
        $paciente->bairro_pac = $data["bairro_pac"];
        $paciente->ativo_pac = $data["ativo_pac"];
        $paciente->mae_pac = $data["mae_pac"];
        $paciente->data_create_pac = $data["data_create_pac"];
        $paciente->usuario_create_pac = $data["usuario_create_pac"];
        $paciente->fk_usuario_pac = $data["fk_usuario_pac"];
        $paciente->fk_estipulante_pac = $data["fk_estipulante_pac"];
        $paciente->fk_seguradora_pac = $data["fk_seguradora_pac"];
        $paciente->obs_pac = $data["obs_pac"];
        $paciente->matricula_pac = $data["matricula_pac"];
        $paciente->estado_pac = $data["estado_pac"];
        $paciente->complemento_pac = $data["complemento_pac"];
        $paciente->cep_pac = $data["cep_pac"];
        $paciente->deletado_pac = $data["deletado_pac"];


        return $paciente;
    }

    public function findAll()
    {
        $paciente = [];

        $stmt = $this->conn->prepare("SELECT * FROM tb_paciente
        ORDER BY id_paciente asc");

        $stmt->execute();

        $paciente = $stmt->fetchAll();
        return $paciente;
    }

    public function getpacientesBynome_pac($nome_pac)
    {
        $pacientes = [];

        $stmt = $this->conn->prepare("SELECT * FROM tb_paciente
                                    WHERE nome_pac = :nome_pac
                                    ORDER BY id_paciente asc");

        $stmt->bindParam(":nome_pac", $nome_pac);
        $stmt->execute();
        $pacientesArray = $stmt->fetchAll();
        foreach ($pacientesArray as $paciente) {
            $pacientes[] = $this->buildpaciente($paciente);
        }
        return $pacientes;
    }

    public function findByIdSeg($id_paciente)
    {
        $paciente = [];
        $stmt = $this->conn->prepare("SELECT * FROM tb_paciente
                                    WHERE id_paciente = :id_paciente");
        $stmt->bindParam(":id_paciente", $id_paciente);
        $stmt->execute();

        $data = $stmt->fetch();
        // var_dump($data);
        $paciente = $this->buildPaciente($data);

        return $paciente;
    }
    public function findById($id_paciente)
    {
        $paciente = [];
        $stmt = $this->conn->prepare("SELECT 
        pa.nome_pac,
        pa.nome_social_pac,
        pa.endereco_pac,
        pa.bairro_pac,
        pa.numero_pac,
        pa.cidade_pac,
        pa.estado_pac,
        pa.data_nasc_pac,
        pa.ativo_pac,
        pa.telefone01_pac,
        pa.telefone02_pac,
        pa.email01_pac,
        pa.email02_pac,
        pa.cpf_pac,
        pa.complemento_pac,
        pa.data_create_pac,
        pa.mae_pac,
        pa.fk_estipulante_pac,
        pa.cep_pac,
        pa.sexo_pac,
        pa.matricula_pac,
        pa.obs_pac,
        pa.id_paciente,
        es.id_estipulante,
        es.nome_est,
        se.id_seguradora,
		se.seguradora_seg,
		pa.fk_estipulante_pac,
		pa.fk_seguradora_pac

        FROM tb_paciente as pa

        LEFT JOIN tb_seguradora as se On  
        se.id_seguradora = pa.fk_seguradora_pac

        LEFT JOIN tb_estipulante as es On  
        es.id_estipulante = pa.fk_estipulante_pac
        
         WHERE id_paciente = :id_paciente");

        $stmt->bindParam(":id_paciente", $id_paciente);
        $stmt->execute();

        $paciente = $stmt->fetchAll();

        return $paciente;
    }

    public function findByPac($pesquisa_nome, $limite, $inicio)
    {
        $paciente = [];

        $stmt = $this->conn->prepare("SELECT * FROM tb_paciente
                                    WHERE nome_pac LIKE :nome_pac order by nome_pac asc limite $inicio, $limite");

        $stmt->bindValue(":nome_pac", '%' . $pesquisa_nome . '%');

        $stmt->execute();

        $paciente = $stmt->fetchAll();
        return $paciente;
    }

    public function validarCpfExistente($cpf)
    {
        $paciente = [];

        $stmt = $this->conn->prepare("SELECT * FROM tb_paciente WHERE cpf_pac = :cpf");

        $stmt->bindValue(":cpf",  $cpf);

        $stmt->execute();

        $paciente = $stmt->fetchAll();
        return $paciente;
    }

    public function create(Paciente $paciente)
    {

        $stmt = $this->conn->prepare("INSERT INTO tb_paciente (
        nome_pac,
        nome_social_pac,
        cpf_pac,
        data_nasc_pac, 
        sexo_pac, 
        mae_pac, 
        endereco_pac, 
        numero_pac, 
        bairro_pac, 
        cidade_pac, 
        estado_pac,
        complemento_pac,
        email01_pac, 
        email02_pac, 
        telefone01_pac, 
        telefone02_pac, 
        ativo_pac, 
        data_create_pac,
        fk_usuario_pac,
        fk_estipulante_pac,
        fk_seguradora_pac,
        obs_pac,
        matricula_pac,
        usuario_create_pac,
        deletado_pac,
        cep_pac
      ) VALUES (
        :nome_pac,
        :nome_social_pac, 
        :cpf_pac,
        :data_nasc_pac,
        :sexo_pac, 
        :mae_pac, 
        :endereco_pac, 
        :numero_pac, 
        :bairro_pac, 
        :cidade_pac, 
        :estado_pac,
        :complemento_pac,
        :email01_pac, 
        :email02_pac, 
        :telefone01_pac, 
        :telefone02_pac, 
        :ativo_pac, 
        :data_create_pac, 
        :fk_usuario_pac,
        :fk_estipulante_pac,
        :fk_seguradora_pac,
        :obs_pac,
        :matricula_pac,
        :usuario_create_pac,
        :deletado_pac,
        :cep_pac
     )");

        $stmt->bindParam(":nome_pac", $paciente->nome_pac);
        $stmt->bindParam(":nome_social_pac", $paciente->nome_social_pac);
        $stmt->bindParam(":endereco_pac", $paciente->endereco_pac);
        $stmt->bindParam(":bairro_pac", $paciente->bairro_pac);
        $stmt->bindParam(":email01_pac", $paciente->email01_pac);
        $stmt->bindParam(":data_nasc_pac", $paciente->data_nasc_pac);
        $stmt->bindParam(":sexo_pac", $paciente->sexo_pac);
        $stmt->bindParam(":cpf_pac", $paciente->cpf_pac);
        $stmt->bindParam(":email02_pac", $paciente->email02_pac);
        $stmt->bindParam(":telefone01_pac", $paciente->telefone01_pac);
        $stmt->bindParam(":telefone02_pac", $paciente->telefone02_pac);
        $stmt->bindParam(":numero_pac", $paciente->numero_pac);
        $stmt->bindParam(":mae_pac", $paciente->mae_pac);
        $stmt->bindParam(":cidade_pac", $paciente->cidade_pac);
        $stmt->bindParam(":complemento_pac", $paciente->complemento_pac);
        $stmt->bindParam(":ativo_pac", $paciente->ativo_pac);
        $stmt->bindParam(":data_create_pac", $paciente->data_create_pac);
        $stmt->bindParam(":usuario_create_pac", $paciente->usuario_create_pac);
        $stmt->bindParam(":fk_usuario_pac", $paciente->fk_usuario_pac);
        $stmt->bindParam(":fk_estipulante_pac", $paciente->fk_estipulante_pac);
        $stmt->bindParam(":fk_seguradora_pac", $paciente->fk_seguradora_pac);
        $stmt->bindParam(":matricula_pac", $paciente->matricula_pac);
        $stmt->bindParam(":obs_pac", $paciente->obs_pac);
        $stmt->bindParam(":estado_pac", $paciente->estado_pac);
        $stmt->bindParam(":deletado_pac", $paciente->deletado_pac);
        $stmt->bindParam(":cep_pac", $paciente->cep_pac);

        $stmt->execute();

        // Mensagem de sucesso por adicionar filme
        $this->message->setMessage("Paciente adicionado com sucesso!", "success", "list_paciente.php");
    }

    public function update(Paciente $paciente)
    {

        $stmt = $this->conn->prepare("UPDATE tb_paciente SET
        nome_pac = :nome_pac,
        nome_social_pac = :nome_social_pac,
        endereco_pac = :endereco_pac,
        email01_pac = :email01_pac,
        email02_pac = :email02_pac,
        data_nasc_pac = :data_nasc_pac,
        sexo_pac = :sexo_pac,
        cpf_pac = :cpf_pac,
        numero_pac = :numero_pac,
        telefone01_pac = :telefone01_pac,
        telefone02_pac = :telefone02_pac,
        cidade_pac = :cidade_pac,
        bairro_pac = :bairro_pac,
        complemento_pac = :complemento_pac,
        mae_pac = :mae_pac,
        ativo_pac = :ativo_pac,
        usuario_create_pac = :usuario_create_pac,
        data_create_pac = :data_create_pac,
        matricula_pac = :matricula_pac,
        fk_estipulante_pac = :fk_estipulante_pac,
        fk_seguradora_pac = :fk_seguradora_pac,
        fk_usuario_pac = :fk_usuario_pac,
        estado_pac = :estado_pac,
        obs_pac = :obs_pac,
        cep_pac = :cep_pac

        WHERE id_paciente = :id_paciente 
      ");

        $stmt->bindParam(":nome_pac", $paciente->nome_pac);
        $stmt->bindParam(":nome_social_pac", $paciente->nome_social_pac);
        $stmt->bindParam(":endereco_pac", $paciente->endereco_pac);
        $stmt->bindParam(":email01_pac", $paciente->email01_pac);
        $stmt->bindParam(":email02_pac", $paciente->email02_pac);
        $stmt->bindParam(":data_nasc_pac", $paciente->data_nasc_pac);
        $stmt->bindParam(":cpf_pac", $paciente->cpf_pac);
        $stmt->bindParam(":sexo_pac", $paciente->sexo_pac);
        $stmt->bindParam(":numero_pac", $paciente->numero_pac);
        $stmt->bindParam(":telefone01_pac", $paciente->telefone01_pac);
        $stmt->bindParam(":telefone02_pac", $paciente->telefone02_pac);
        $stmt->bindParam(":cidade_pac", $paciente->cidade_pac);
        $stmt->bindParam(":bairro_pac", $paciente->bairro_pac);
        $stmt->bindParam(":complemento_pac", $paciente->complemento_pac);
        $stmt->bindParam(":mae_pac", $paciente->mae_pac);
        $stmt->bindParam(":ativo_pac", $paciente->ativo_pac);
        $stmt->bindParam(":usuario_create_pac", $paciente->usuario_create_pac);
        $stmt->bindParam(":data_create_pac", $paciente->data_create_pac);
        $stmt->bindParam(":obs_pac", $paciente->obs_pac);
        $stmt->bindParam(":matricula_pac", $paciente->matricula_pac);
        $stmt->bindParam(":estado_pac", $paciente->estado_pac);
        $stmt->bindParam(":fk_estipulante_pac", $paciente->fk_estipulante_pac);
        $stmt->bindParam(":fk_seguradora_pac", $paciente->fk_seguradora_pac);
        $stmt->bindParam(":fk_usuario_pac", $paciente->fk_usuario_pac);
        $stmt->bindParam(":cep_pac", $paciente->cep_pac);
        $stmt->bindParam(":id_paciente", $paciente->id_paciente);

        $stmt->execute();

        // Mensagem de sucesso por editar paciente
        $this->message->setMessage("Paciente atualizado com sucesso!", "success", "list_paciente.php");
    }

    public function destroy($id_paciente)
    {
        $stmt = $this->conn->prepare("DELETE FROM tb_paciente WHERE id_paciente = $id_paciente");

        $stmt->bindParam(":id_paciente", $id_paciente);

        $stmt->execute();

        // Mensagem de sucesso por remover filme
        $this->message->setMessage("Paciente removido com sucesso!", "success", "list_paciente.php");
    }


    public function findGeral()
    {

        $pacientes = [];

        $stmt = $this->conn->query("SELECT * FROM tb_paciente where deletado_pac <> 's' ORDER BY id_paciente");

        $stmt->execute();

        $pacientes = $stmt->fetchAll();

        return $pacientes;
    }
    public function selectAllpaciente($where = null, $order = null, $limite = null) // function para pesquisar apenas os pacintes que nao foram deletados 
    {
        //DADOS DA QUERY
        $where = strlen($where) ? 'WHERE ' . $where : '';
        $where = $where . ' AND deletado_pac <> "s" '; // filtrar apenas os pacientes que nao foram deletados

        $order = strlen($order) ? 'ORDER BY ' . $order : '';
        $limite = strlen($limite) ? 'LIMIT ' . $limite : '';

        //MONTA A QUERY
        $query = $this->conn->query('SELECT * FROM tb_paciente ' . $where . ' ' . $order . ' ' . $limite);

        $query->execute();

        $paciente = $query->fetchAll();

        return $paciente;
    }

    public function deletarUpdate(paciente $paciente)
    {
        $deletado_pac = "s";
        $stmt = $this->conn->prepare("UPDATE tb_paciente SET
        
        deletado_pac = :deletado_pac

        WHERE id_paciente = :id_paciente 
      ");

        $stmt->bindParam(":deletado_pac", $paciente->deletado_pac);

        $stmt->bindParam(":id_paciente", $paciente->id_paciente);
        $stmt->execute();

        // Mensagem de sucesso por editar hospital
        $this->message->setMessage("Paciente deletado com sucesso!", "success", "list_paciente.php");
    }


    public function Qtdpaciente($where = null, $order = null, $limite = null)
    {
        $paciente = [];
        $internacao = [];
        //DADOS DA QUERY
        $where = strlen($where) ? 'WHERE ' . $where : '';
        $where = $where . ' AND deletado_pac <> "s" '; // filtrar apenas os pacientes que nao foram deletados

        $order = strlen($order) ? 'ORDER BY ' . $order : '';
        $limite = strlen($limite) ? 'LIMIT ' . $limite : '';

        $stmt = $this->conn->query('SELECT * ,COUNT(id_paciente) as qtd FROM tb_paciente ' . $where . ' ' . $order . ' ' . $limite);

        $stmt->execute();

        $QtdTotalPac = $stmt->fetch();

        return $QtdTotalPac;
    }

    public function verificaId1()
    {
        try {
            // Prepara a chamada da stored procedure
            $stmt = $this->conn->prepare('CALL mydb_accert.verificar_e_criar_id1()');

            // Executa a chamada da stored procedure
            $success = $stmt->execute();

            // Verifica se a chamada da stored procedure foi bem-sucedida
            if ($success) {
                return true;
            } else {
                return false;
            }
        } catch (Exception $e) {
            // Lidar com exceções
            echo "Erro: " . $e->getMessage();
            return false;
        }
    }
}