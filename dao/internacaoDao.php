<?php

require_once("./models/internacao.php");
require_once("./models/message.php");

// Review DAO

class internacaoDAO implements internacaoDAOInterface
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

    public function buildinternacao($data)
    {
        $internacao = new internacao();
        $internacao->id_internacao = $data['id_internacao'];
        $internacao->fk_hospital_int = $data["fk_hospital_int"];
        $internacao->fk_paciente_int = $data["fk_paciente_int"];
        $internacao->acoes_int = $data["acoes_int"];
        $internacao->fk_patologia_int = $data["fk_patologia_int"];
        $internacao->fk_patologia2 = $data["fk_patologia2"];
        $internacao->acomodacao_int = $data["acomodacao_int"];
        $internacao->modo_internacao_int = $data["modo_internacao_int"];
        $internacao->tipo_admissao_int = $data["tipo_admissao_int"];
        $internacao->data_intern_int = $data["data_intern_int"];
        $internacao->data_visita_int = $data["data_visita_int"];
        $internacao->data_create_int = $data["data_create_int"];
        $internacao->usuario_create_int = $data["usuario_create_int"];
        $internacao->titular_int = $data["titular_int"];
        $internacao->especialidade_int = $data["especialidade_int"];
        $internacao->grupo_patologia_int = $data["grupo_patologia_int"];
        $internacao->primeira_vis_int = $data["primeira_vis_int"];
        $internacao->visita_no_int = $data["visita_no_int"];
        $internacao->internado_int = $data["internado_int"];
        $internacao->visita_med_int = $data["visita_med_int"];
        $internacao->visita_enf_int = $data["visita_enf_int"];
        $internacao->senha_int = $data['senha_int'];
        $internacao->rel_int = $data['rel_int'];
        $internacao->visita_auditor_prof_med = $data['visita_auditor_prof_med'];
        $internacao->visita_auditor_prof_enf = $data['visita_auditor_prof_enf'];
        $internacao->fk_usuario_int = $data['fk_usuario_int'];
        $internacao->censo_int = $data['censo_int'];
        $internacao->programacao_int = $data['programacao_int'];
        $internacao->origem_int = $data['origem_int'];
        $internacao->int_pertinente_int = $data['int_pertinente_int'];
        $internacao->rel_pertinente_int = $data['rel_pertinente_int'];
        $internacao->hora_intern_int = $data['hora_intern_int'];

        //dados de visita enfermagem

        return $internacao;
    }

    public function findAll()
    {
        $internacao = [];

        $stmt = $this->conn->prepare("SELECT * FROM tb_internacao
        ORDER BY id_internacao asc");

        $stmt->execute();

        $internacao = $stmt->fetchAll();
        return $internacao;
    }

    public function getinternacaoBynome_pac($nome_pac)
    {
        $internacao = [];

        $stmt = $this->conn->prepare("SELECT * FROM tb_internacao
                                    WHERE nome_pac = :nome_pac
                                    ORDER BY id_internacao asc");

        $stmt->bindParam(":nome_pac", $nome_pac);
        $stmt->execute();
        $internacaoArray = $stmt->fetchAll();
        foreach ($internacaoArray as $internacao) {
            $internacao[] = $this->buildinternacao($internacao);
        }
        return $internacao;
    }

    public function findByPac($pesquisa_nome)
    {
        $internacao = [];

        $stmt = $this->conn->prepare("SELECT * FROM tb_internacao
                                    WHERE nome_pac LIKE :nome_pac ");

        $stmt->bindValue(":nome_pac", '%' . $pesquisa_nome . '%');

        $stmt->execute();

        $internacao = $stmt->fetchAll();
        return $internacao;
    }



    public function findByPacId($pac_id)
    {
        $internacao = [];

        $stmt = $this->conn->prepare("SELECT * FROM tb_internacao ac iNNER JOIN tb_hospital as ho On  
                                    ac.fk_hospital_int = ho.id_hospital
                                    left JOIN tb_antecedente as an On  
                                    ac.fk_patologia2 = an.id_antecedente
                                    WHERE fk_paciente_int = :pac_id ");

        $stmt->bindValue(":pac_id", $pac_id);

        $stmt->execute();

        $internacao = $stmt->fetchAll();
        return $internacao;
    }

    public function create(internacao $internacao)
    {

        $stmt = $this->conn->prepare("INSERT INTO tb_internacao (
            fk_hospital_int, 
            fk_paciente_int, 
            rel_int, 
            fk_patologia_int, 
            fk_patologia2, 
            data_intern_int, 
            acoes_int,
            internado_int, 
            modo_internacao_int, 
            tipo_admissao_int, 
            titular_int, 
            crm_int, 
            data_visita_int, 
            grupo_patologia_int,
            data_create_int,
            usuario_create_int,
            primeira_vis_int,
            visita_no_int,
            visita_enf_int,
            visita_med_int,
            senha_int,
            acomodacao_int,
            visita_auditor_prof_med,
            visita_auditor_prof_enf,
            fk_usuario_int,
            censo_int,
            especialidade_int,
            programacao_int,
            origem_int,
            int_pertinente_int,
            rel_pertinente_int,
            hora_intern_int
   
         ) VALUES (
           :fk_hospital_int, 
           :fk_paciente_int,
           :rel_int, 
           :fk_patologia_int, 
           :fk_patologia2, 
           :data_intern_int, 
           :acoes_int, 
           :internado_int, 
           :modo_internacao_int, 
           :tipo_admissao_int,
           :titular_int, 
           :crm_int, 
           :data_visita_int, 
           :grupo_patologia_int,
           :data_create_int,
           :usuario_create_int,
           :primeira_vis_int,
           :visita_no_int,
           :visita_enf_int,
           :visita_med_int,
           :senha_int,
           :acomodacao_int,
           :visita_auditor_prof_med,
           :visita_auditor_prof_enf,
           :fk_usuario_int,
           :censo_int,
           :especialidade_int,
           :programacao_int,
           :origem_int,
           :int_pertinente_int,
           :rel_pertinente_int,
           :hora_intern_int
        )");

        $stmt->bindParam(":fk_hospital_int", $internacao->fk_hospital_int);
        $stmt->bindParam(":fk_paciente_int", $internacao->fk_paciente_int);
        $stmt->bindParam(":rel_int", $internacao->rel_int, PDO::PARAM_STR);
        $stmt->bindParam(":fk_patologia_int", $internacao->fk_patologia_int);
        $stmt->bindParam(":fk_patologia2", $internacao->fk_patologia2);
        $stmt->bindParam(":data_intern_int", $internacao->data_intern_int);
        $stmt->bindParam(":internado_int", $internacao->internado_int);
        $stmt->bindParam(":acoes_int", $internacao->acoes_int, PDO::PARAM_STR);
        $stmt->bindParam(":modo_internacao_int", $internacao->modo_internacao_int);
        $stmt->bindParam(":tipo_admissao_int", $internacao->tipo_admissao_int);
        $stmt->bindParam(":especialidade_int", $internacao->especialidade_int);
        $stmt->bindParam(":data_create_int", $internacao->data_create_int);
        $stmt->bindParam(":usuario_create_int", $internacao->usuario_create_int);
        $stmt->bindParam(":data_visita_int", $internacao->data_visita_int);
        $stmt->bindParam(":grupo_patologia_int", $internacao->grupo_patologia_int);
        $stmt->bindParam(":primeira_vis_int", $internacao->primeira_vis_int);
        $stmt->bindParam(":visita_no_int", $internacao->visita_no_int);
        $stmt->bindParam(":visita_med_int", $internacao->visita_med_int);
        $stmt->bindParam(":visita_enf_int", $internacao->visita_enf_int);
        $stmt->bindParam(":senha_int", $internacao->senha_int, PDO::PARAM_STR);
        $stmt->bindParam(":acomodacao_int", $internacao->acomodacao_int);
        $stmt->bindParam(":visita_auditor_prof_med", $internacao->visita_auditor_prof_med);
        $stmt->bindParam(":visita_auditor_prof_enf", $internacao->visita_auditor_prof_enf);
        $stmt->bindParam(":titular_int", $internacao->titular_int);
        $stmt->bindParam(":crm_int", $internacao->crm_int, PDO::PARAM_STR);
        $stmt->bindParam(":acoes_int", $internacao->acoes_int, PDO::PARAM_STR);
        $stmt->bindParam(":censo_int", $internacao->censo_int);
        $stmt->bindParam(":programacao_int", $internacao->programacao_int, PDO::PARAM_STR);
        $stmt->bindParam(":origem_int", $internacao->origem_int);
        $stmt->bindParam(":int_pertinente_int", $internacao->int_pertinente_int);
        $stmt->bindParam(":rel_pertinente_int", $internacao->rel_pertinente_int);
        $stmt->bindParam(":hora_intern_int", $internacao->hora_intern_int);
        $stmt->bindParam(":fk_usuario_int", $internacao->fk_usuario_int);

        $stmt->execute();

        // Mensagem de sucesso por adicionar filme
        $this->message->setMessage("internacao adicionado com sucesso!", "success", "list_internacao.php");

        return $this->findLastId();
    }

    public function update(internacao $internacao)
    {
        $stmt = $this->conn->prepare("UPDATE tb_internacao SET
        fk_hospital_int = :fk_hospital_int,
        fk_paciente_int = :fk_paciente_int,
        rel_int = :rel_int,
        fk_patologia_int = :fk_patologia_int,
        fk_patologia2 = :fk_patologia2,
        data_intern_int = :data_intern_int,
        acoes_int = :acoes_int,
        internado_int = :internado_int,
        modo_internacao_int = :modo_internacao_int,
        tipo_admissao_int = :tipo_admissao_int,
        titular_int = :titular_int,
        crm_int = :crm_int,
        data_visita_int = :data_visita_int,
        grupo_patologia_int = :grupo_patologia_int,
        data_create_int = :data_create_int,
        usuario_create_int = :usuario_create_int,
        primeira_vis_int = :primeira_vis_int,
        visita_no_int = :visita_no_int,
        visita_enf_int = :visita_enf_int,
        visita_med_int = :visita_med_int,
        senha_int = :senha_int,
        acomodacao_int = :acomodacao_int,
        visita_auditor_prof_med = :visita_auditor_prof_med,
        visita_auditor_prof_enf = :visita_auditor_prof_enf,
        fk_usuario_int = :fk_usuario_int,
        censo_int = :censo_int,
        especialidade_int = :especialidade_int,
        origem_int = :origem_int,
        int_pertinente_int = :int_pertinente_int,
        rel_pertinente_int = :rel_pertinente_int,
        hora_intern_int = :hora_intern_int

        WHERE id_internacao = :id_internacao
    ");

        $stmt->bindParam(":fk_hospital_int", $internacao->fk_hospital_int);
        $stmt->bindParam(":fk_paciente_int", $internacao->fk_paciente_int);
        $stmt->bindParam(":rel_int", $internacao->rel_int, PDO::PARAM_STR);
        $stmt->bindParam(":fk_patologia_int", $internacao->fk_patologia_int);
        $stmt->bindParam(":fk_patologia2", $internacao->fk_patologia2);
        $stmt->bindParam(":data_intern_int", $internacao->data_intern_int);
        $stmt->bindParam(":acoes_int", $internacao->acoes_int, PDO::PARAM_STR);
        $stmt->bindParam(":internado_int", $internacao->internado_int);
        $stmt->bindParam(":modo_internacao_int", $internacao->modo_internacao_int);
        $stmt->bindParam(":tipo_admissao_int", $internacao->tipo_admissao_int);
        $stmt->bindParam(":titular_int", $internacao->titular_int, PDO::PARAM_STR);
        $stmt->bindParam(":crm_int", $internacao->crm_int, PDO::PARAM_STR);
        $stmt->bindParam(":data_visita_int", $internacao->data_visita_int, PDO::PARAM_STR);
        $stmt->bindParam(":grupo_patologia_int", $internacao->grupo_patologia_int);
        $stmt->bindParam(":data_create_int", $internacao->data_create_int);
        $stmt->bindParam(":usuario_create_int", $internacao->usuario_create_int);
        $stmt->bindParam(":primeira_vis_int", $internacao->primeira_vis_int);
        $stmt->bindParam(":visita_no_int", $internacao->visita_no_int);
        $stmt->bindParam(":visita_enf_int", $internacao->visita_enf_int);
        $stmt->bindParam(":visita_med_int", $internacao->visita_med_int);
        $stmt->bindParam(":senha_int", $internacao->senha_int, PDO::PARAM_STR);
        $stmt->bindParam(":acomodacao_int", $internacao->acomodacao_int);
        $stmt->bindParam(":visita_auditor_prof_med", $internacao->visita_auditor_prof_med);
        $stmt->bindParam(":visita_auditor_prof_enf", $internacao->visita_auditor_prof_enf);
        $stmt->bindParam(":fk_usuario_int", $internacao->fk_usuario_int);
        $stmt->bindParam(":censo_int", $internacao->censo_int);
        $stmt->bindParam(":especialidade_int", $internacao->especialidade_int);
        $stmt->bindParam(":origem_int", $internacao->origem_int);
        $stmt->bindParam(":int_pertinente_int", $internacao->int_pertinente_int);
        $stmt->bindParam(":rel_pertinente_int", $internacao->rel_pertinente_int);
        $stmt->bindParam(":hora_intern_int", $internacao->hora_intern_int);
        $stmt->bindParam(":id_internacao", $internacao->id_internacao);

        $stmt->execute();

        // Mensagem de sucesso
        $this->message->setMessage("Internação atualizada com sucesso!", "success", "list_internacao.php");
    }


    public function updateCenso(internacao $internacao)
    {

        $stmt = $this->conn->prepare("UPDATE tb_internacao SET 
       fk_hospital_int= :fk_hospital_int, 
        fk_paciente_int= :fk_paciente_int,
        fk_patologia_int = :fk_patologia_int,
        fk_patologia2 = :fk_patologia2,
        internado_int = :internado_int,
        acoes_int = :acoes_int,
        acomodacao_int = :acomodacao_int,
        modo_internacao_int = :modo_internacao_int,
        tipo_admissao_int = :tipo_admissao_int,
        data_intern_int = :data_intern_int,
        data_visita_int = :data_visita_int,
        usuario_create_int = :usuario_create_int,
        data_create_int = :data_create_int,
        especialidade_int = :especialidade_int,
        titular_int = :titular_int,
        crm_int = :crm_int,
        rel_int = :rel_int,
        grupo_patologia_int = :grupo_patologia_int,
        censo_int = :censo_int,
        visita_auditor_prof_med = :visita_auditor_prof_med,
        visita_auditor_prof_enf = :visita_auditor_prof_enf,
        visita_enf_int = :visita_enf_int,
        visita_med_int = :visita_med_int,
        senha_int = :senha_int,
        origem_int = :origem_int,
        int_pertinente_int = :int_pertinente_int,
        rel_pertinente_int = :rel_pertinente_int,
        hora_intern_int = :hora_intern_int
        
        WHERE id_internacao = :id_internacao 
      ");

        $stmt->bindParam(":fk_hospital_int", $internacao->fk_hospital_int);
        $stmt->bindParam(":fk_paciente_int", $internacao->fk_paciente_int);
        $stmt->bindParam(":fk_patologia_int", $internacao->fk_patologia_int);
        $stmt->bindParam(":fk_patologia2", $internacao->fk_patologia2);
        $stmt->bindParam(":internado_int", $internacao->internado_int);
        $stmt->bindParam(":acoes_int", $internacao->acoes_int, PDO::PARAM_STR);
        $stmt->bindParam(":acomodacao_int", $internacao->acomodacao_int);
        $stmt->bindParam(":modo_internacao_int", $internacao->modo_internacao_int);
        $stmt->bindParam(":tipo_admissao_int", $internacao->tipo_admissao_int);
        $stmt->bindParam(":data_intern_int", $internacao->data_intern_int);
        $stmt->bindParam(":data_visita_int", $internacao->data_visita_int);
        $stmt->bindParam(":usuario_create_int", $internacao->usuario_create_int);
        $stmt->bindParam(":data_create_int", $internacao->data_create_int);
        $stmt->bindParam(":especialidade_int", $internacao->especialidade_int);
        $stmt->bindParam(":titular_int", $internacao->titular_int);
        $stmt->bindParam(":crm_int", $internacao->crm_int);
        $stmt->bindParam(":rel_int", $internacao->rel_int, PDO::PARAM_STR);
        $stmt->bindParam(":grupo_patologia_int", $internacao->grupo_patologia_int);
        $stmt->bindParam(":censo_int", $internacao->censo_int);
        $stmt->bindParam(":visita_auditor_prof_med", $internacao->visita_auditor_prof_med);
        $stmt->bindParam(":visita_auditor_prof_enf", $internacao->visita_auditor_prof_enf);
        $stmt->bindParam(":visita_enf_int", $internacao->visita_enf_int);
        $stmt->bindParam(":visita_med_int", $internacao->visita_med_int);
        $stmt->bindParam(":senha_int", $internacao->senha_int);
        $stmt->bindParam(":int_pertinente_int", $internacao->int_pertinente_int);
        $stmt->bindParam(":rel_pertinente_int", $internacao->rel_pertinente_int);
        $stmt->bindParam(":origem_int", $internacao->origem_int);
        $stmt->bindParam(":hora_intern_int", $internacao->hora_intern_int);

        $stmt->bindParam(":id_internacao", $internacao->id_internacao);
        $stmt->execute();

        // $this->message->setMessage("internacao adicionado com sucesso!", "success", "list_internacao.php");
    }
    public function updateAlta(internacao $internacao)
    {

        $stmt = $this->conn->prepare("UPDATE tb_internacao SET
        internado_int = :internado_int
     
        WHERE id_internacao = :id_internacao 
      ");
        $stmt->bindParam(":internado_int", $internacao->internado_int);
        $stmt->bindParam(":id_internacao", $internacao->id_internacao);

        $stmt->execute();
    }

    public function destroy($id_internacao)
    {
        $stmt = $this->conn->prepare("DELETE FROM tb_internacao WHERE id_internacao = :id_internacao");

        $stmt->bindParam(":id_internacao", $id_internacao);

        $stmt->execute();

        // Mensagem de sucesso por remover filme
        $this->message->setMessage("internação removida com sucesso!", "success", "list_internacao.php");
    }


    public function findGeral()
    {

        $internacao = [];

        $stmt = $this->conn->query("SELECT * FROM tb_internacao ORDER BY id_internacao asc");

        $stmt->execute();

        $internacao = $stmt->fetchAll();

        return $internacao;
    }

    public function findLast($lastInternacao)
    {

        $internacao = [];

        $stmt = $this->conn->query("SELECT ac.id_internacao, 
        ac.acoes_int,  
        ac.internado_int, 
        ac.fk_patologia_int, 
        ac.data_intern_int, 
        ac.hora_intern_int,
        ac.rel_int, 
        ac.fk_paciente_int, 
        ac.acomodacao_int, 
        pa.id_paciente, 
        pa.nome_pac, 
        ac.usuario_create_int, 
        ac.fk_hospital_int, 
        ac.modo_internacao_int, 
        ac.tipo_admissao_int, 
        ho.id_hospital, ho.nome_hosp, 
        ac.especialidade_int, 
        ac.titular_int, 
        ac.data_visita_int, 
        ac.grupo_patologia_int, 
        ac.acomodacao_int, 
        ac.fk_patologia_int, 
        ad.fk_hospital, 
        ad.valor_aco, 
        ad.acomodacao_aco
        FROM tb_internacao ac 

        iNNER JOIN tb_hospital as ho On  
        ac.fk_hospital_int = ho.id_hospital

        left join tb_paciente as pa on
        ac.fk_paciente_int = pa.id_paciente

        left join tb_acomodacao as ad on
        ho.id_hospital = ad.fk_hospital

        WHERE id_internacao = $lastInternacao");

        $stmt->execute();

        $internacao = $stmt->fetchAll();

        return $internacao;
    }

    public function findLastId()
    {
        $stmt = $this->conn->query("SELECT coalesce(max(id_internacao),1) as id_intern from tb_internacao");

        $stmt->execute();

        $internacaoID = $stmt->fetchAll();

        return $internacaoID;
    }
    public function joininternacaoHospitalshow($id_visita)
    {
        $stmt = $this->conn->query("SELECT 
            ac.id_internacao, 
            ac.acoes_int, 
            ac.data_intern_int,
            ac.hora_intern_int, 
            ac.data_visita_int, 
            ac.rel_int, 
            ac.fk_paciente_int, 
            ac.usuario_create_int, 
            ac.fk_hospital_int, 
            ac.modo_internacao_int, 
            ac.tipo_admissao_int,
            ac.especialidade_int, 
            ac.titular_int, 
            ac.crm_int, 
            ac.senha_int, 
            ac.grupo_patologia_int, 
            ac.acomodacao_int, 
            ac.origem_int, 
            ac.fk_patologia_int, 
            ac.fk_patologia2, 
            ac.internado_int,
            ac.visita_no_int,
            ac.primeira_vis_int,
            ac.origem_int,
            pa.id_paciente,
            pa.nome_pac,
            pat.id_patologia,
            pat.patologia_pat,
            an.id_antecedente,
            an.antecedente_ant,
            vi.fk_internacao_vis, 
            vi.rel_visita_vis, 
            vi.acoes_int_vis, 
            vi.usuario_create,
            vi.visita_auditor_prof_med,
            vi.visita_auditor_prof_enf,
            vi.visita_med_vis,
            vi.visita_enf_vis,
            vi.visita_no_vis,
            vi.fk_usuario_vis,
            vi.data_visita_vis,
            ho.id_hospital,
            al.fk_id_int_alt,
            al.id_alta,
            al.internado_alt,
            ut.fk_internacao_uti,
            ut.internacao_uti,
            ut.internado_uti,
            ut.id_uti,
            ho.nome_hosp 
   
       FROM tb_internacao ac 
   
           left JOIN tb_hospital as ho On  
           ac.fk_hospital_int = ho.id_hospital
   
           left JOIN tb_uti as ut On  
           ac.id_internacao = ut.fk_internacao_uti
   
           left JOIN tb_visita as vi On  
           ac.id_internacao = vi.fk_internacao_vis
   
           left JOIN tb_patologia as pat On  
           ac.fk_patologia_int = pat.id_patologia
   
           left JOIN tb_antecedente as an On  
           ac.fk_patologia2 = an.id_antecedente
   
           left JOIN tb_alta as al On  
           ac.id_internacao = al.fk_id_int_alt
   
           left join tb_paciente as pa on
           ac.fk_paciente_int = pa.id_paciente

        WHERE vi.id_visita = '.$id_visita.'
         
         ");

        $stmt->execute();

        $internacao = $stmt->fetch();
        return $internacao;
    }
    public function findInternByInternado($where, $ativo, $limite, $inicio)
    {

        $stmt = $this->conn->query("SELECT 
        ac.id_internacao, 
        ac.acoes_int, 
        ac.data_intern_int,
        ac.hora_intern_int, 
        ac.data_visita_int, 
        ac.rel_int, 
        ac.fk_paciente_int, 
        ac.usuario_create_int, 
        ac.fk_hospital_int, 
        ac.modo_internacao_int, 
        ac.tipo_admissao_int,
        ac.tipo_alta_int,
        ac.especialidade_int, 
        ac.titular_int, 
        ac.grupo_patologia_int, 
        ac.acomodacao_int, 
        ac.primeira_vis_int, 
        ac.visita_no_int, 
        ac.fk_patologia_int, 
        ac.fk_patologia2, 
        ac.internado_int,
        pa.id_paciente,
        pa.nome_pac,
        ho.id_hospital, 
        ho.nome_hosp

        FROM tb_internacao ac 

        iNNER JOIN tb_hospital as ho On  
        ac.fk_hospital_int = ho.id_hospital

        left join tb_paciente as pa on
        ac.fk_paciente_int = pa.id_paciente

        WHERE nome_hosp LIKE '$where' AND internado_int = '$ativo' LIMIT $inicio, $limite");

        $stmt->execute();

        $internacao = $stmt->fetchAll();

        return $internacao;
    }

    public function alta($id_internacao)
    {

        $internacao = [];

        $stmt = $this->conn->prepare("SELECT * FROM tb_internacao
                                    WHERE id_internacao = :id_internacao");

        $stmt->bindValue(":id_internacao", $id_internacao);

        $stmt->execute();

        $internacao = $stmt->fetch();

        return $internacao;
    }


    public function findByIdUpdate($id_internacao)
    {

        $internacao = [];

        $stmt = $this->conn->prepare("SELECT * FROM tb_internacao
                                    WHERE id_internacao = :id_internacao");

        $stmt->bindValue(":id_internacao", $id_internacao);

        $stmt->execute();

        if ($stmt->rowCount() > 0) {

            $internacaoArray = $stmt->fetchAll();

            foreach ($internacaoArray as $internacao) {
                $internacao[] = $this->buildinternacao($internacao);
            }
        }
        return $internacao;
    }




    public function findByHospital($pesquisa_hosp, $limite, $inicio)
    {
        $stmt = $this->conn->query("SELECT 
        ac.id_internacao, 
        ac.acoes_int, 
        ac.data_intern_int,
        ac.hora_intern_int, 
        ac.data_visita_int, 
        ac.rel_int, 
        ac.fk_paciente_int, 
        ac.usuario_create_int, 
        ac.fk_hospital_int, 
        ac.modo_internacao_int, 
        ac.tipo_admissao_int,
        ac.especialidade_int, 
        ac.titular_int, 
        ac.grupo_patologia_int, 
        ac.acomodacao_int, 
        ac.fk_patologia_int, 
        ac.fk_patologia2, 
        ac.internado_int,
        ac.visita_no_int,
        ac.primeira_vis_int,
        pa.id_paciente,
        pa.nome_pac,
        ho.id_hospital, 
        ho.nome_hosp

        FROM tb_internacao ac 

        iNNER JOIN tb_hospital as ho On  
        ac.fk_hospital_int = ho.id_hospital

        left join tb_paciente as pa on
        ac.fk_paciente_int = pa.id_paciente

        WHERE nome_hosp like '%" . $pesquisa_hosp . "%' LIMIT $inicio, $limite");

        $stmt->execute();

        $internacao = $stmt->fetchAll();

        return $internacao;
    }
    // public 2 -> selecao de internados
    public function findByInternado($ativo, $limite, $inicio)
    {
        $stmt = $this->conn->query("SELECT 
        ac.id_internacao, 
        ac.acoes_int, 
        ac.data_intern_int,
        ac.hora_intern_int, 
        ac.data_visita_int, 
        ac.rel_int, 
        ac.fk_paciente_int, 
        ac.usuario_create_int, 
        ac.fk_hospital_int, 
        ac.modo_internacao_int, 
        ac.tipo_admissao_int,
        ac.tipo_alta_int,
        ac.especialidade_int, 
        ac.titular_int, 
        ac.grupo_patologia_int, 
        ac.acomodacao_int, 
        ac.fk_patologia_int, 
        ac.fk_patologia2, 
        ac.internado_int,
        ac.visita_no_int,
        ac.primeira_vis_int,
        pa.id_paciente,
        pa.nome_pac,
        ho.id_hospital, 
        ho.nome_hosp

        FROM tb_internacao ac 

        iNNER JOIN tb_hospital as ho On  
        ac.fk_hospital_int = ho.id_hospital

        left join tb_paciente as pa on
        ac.fk_paciente_int = pa.id_paciente

        WHERE internado_int = '$ativo' LIMIT $inicio, $limite");

        $stmt->execute();

        $internacao = $stmt->fetchAll();

        return $internacao;
    }
    // public 3 -> selecao de ambos filtros
    public function findByAmbos($pesquisa_hosp, $ativo, $limite, $inicio) {}

    // public 4 -> selecao sem filtros
    public function findByAll($limite, $inicio)
    {
        $internacao = [];
        $stmt = $this->conn->query("SELECT ac.id_internacao, 
        ac.acoes_int,  
        ac.internado_int, 
        ac.fk_patologia_int, 
        ac.data_intern_int,
        ac.hora_intern_int, 
        ac.rel_int, 
        ac.fk_paciente_int, 
        ac.acomodacao_int, 
        pa.id_paciente, 
        pa.nome_pac, 
        ac.usuario_create_int, 
        ac.fk_hospital_int, 
        ac.modo_internacao_int, 
        ac.tipo_alta_int, 
        ac.tipo_admissao_int, 
        ho.id_hospital, 
        ho.nome_hosp, 
        ac.especialidade_int, 
        ac.titular_int, 
        ac.data_visita_int, 
        ac.grupo_patologia_int, 
        ac.acomodacao_int, 
        ac.fk_patologia_int
        FROM tb_internacao ac 

        iNNER JOIN tb_hospital as ho On  
        ac.fk_hospital_int = ho.id_hospital

        left join tb_paciente as pa on
        ac.fk_paciente_int = pa.id_paciente ORDER BY id_internacao asc limit $inicio, $limite");

        $stmt->execute();

        $internacao = $stmt->fetchAll();
        return $internacao;
    }
    public function findTotal()
    {
        $internacao = [];
        $stmt = $this->conn->query("SELECT COUNT(id_internacao) FROM tb_internacao");

        $stmt->execute();

        $QtdTotal = $stmt->fetchAll();

        return $QtdTotal;
    }

    // MODELO DE FILTRO COM SELECT ATUAL COM FILTROS E PAGINACAO
    public function selectAllInternacao($where = null, $order = null, $limit = null)
    {
        //DADOS DA QUERY
        $where = strlen($where) ? 'WHERE ' . $where : '';
        $order = strlen($order) ? 'ORDER BY ' . $order : '';
        $limit = strlen($limit) ? 'LIMIT ' . $limit : '';
        $group = ' GROUP BY ac.id_internacao ';

        //MONTA A QUERY
        $query = $this->conn->query('SELECT 
        ac.id_internacao, 
        ac.acoes_int, 
        ac.data_intern_int,
        ac.hora_intern_int, 
        ac.data_visita_int, 
        ac.rel_int, 
        ac.fk_paciente_int, 
        ac.usuario_create_int, 
        ac.fk_hospital_int, 
        ac.modo_internacao_int, 
        ac.tipo_admissao_int,
        ac.especialidade_int, 
        ac.titular_int, 
        ac.crm_int, 
        ac.origem_int, 
        ac.grupo_patologia_int, 
        ac.acomodacao_int, 
        ac.fk_patologia_int, 
        ac.fk_patologia2, 
        ac.internado_int,
        ac.internado_uti_int,
        ac.internacao_uti_int,
        ac.visita_no_int,
        ac.primeira_vis_int,
        ac.censo_int,
        ac.senha_int,
        pa.id_paciente,
        pa.nome_pac,
        pat.id_patologia,
        pat.patologia_pat,
        an.id_antecedente,
        an.antecedente_ant,
        vi.fk_internacao_vis, 
        vi.rel_visita_vis, 
        vi.acoes_int_vis, 
        vi.usuario_create,
        vi.visita_auditor_prof_med,
        vi.visita_auditor_prof_enf,
        vi.visita_med_vis,
        vi.visita_enf_vis,
        vi.visita_no_vis,
        vi.fk_usuario_vis,
        vi.data_visita_vis,
        ho.id_hospital,
        al.fk_id_int_alt,
        al.id_alta,
        al.internado_alt,
        ut.fk_internacao_uti,
        ut.internacao_uti,
        ut.internado_uti,
        ut.id_uti,
        ho.nome_hosp 

    FROM tb_internacao ac 

        left JOIN tb_hospital as ho On  
        ac.fk_hospital_int = ho.id_hospital

        left JOIN tb_uti as ut On  
        ac.id_internacao = ut.fk_internacao_uti

        left JOIN tb_visita as vi On  
        ac.id_internacao = vi.fk_internacao_vis

        left JOIN tb_patologia as pat On  
        ac.fk_patologia_int = pat.id_patologia

        left JOIN tb_antecedente as an On  
        ac.fk_patologia2 = an.id_antecedente

        left JOIN tb_alta as al On  
        ac.id_internacao = al.fk_id_int_alt

        left join tb_paciente as pa on
        ac.fk_paciente_int = pa.id_paciente '

            . $where . ' ' . $group . ' ' . $order . ' ' . $limit);

        $query->execute();

        $hospital = $query->fetchAll();

        return $hospital;
    }

    public function selectAllInternacaoCountVis($where = null, $order = null, $limit = null)
    {
        //DADOS DA QUERY
        $where = strlen($where) ? 'WHERE ' . $where : '';
        $order = strlen($order) ? 'ORDER BY ' . $order : '';
        $limit = strlen($limit) ? 'LIMIT ' . $limit : '';
        $group = ' GROUP BY ac.id_internacao ';

        //MONTA A QUERY
        $query = $this->conn->query('SELECT 
            ac.id_internacao, 
            ac.acoes_int, 
            ac.data_intern_int, 
            ac.data_visita_int,
            ac.hora_intern_int, 
            ac.rel_int, 
            ac.fk_paciente_int, 
            ac.usuario_create_int, 
            ac.fk_hospital_int, 
            ac.modo_internacao_int, 
            ac.tipo_admissao_int,
            ac.especialidade_int, 
            ac.titular_int, 
            ac.crm_int, 
            ac.senha_int, 
            ac.grupo_patologia_int, 
            ac.acomodacao_int, 
            ac.fk_patologia_int, 
            ac.fk_patologia2, 
            ac.internado_int,
            ac.visita_no_int,
            ac.primeira_vis_int,
            ac.censo_int,
            pa.id_paciente,
            pa.nome_pac,
            pat.id_patologia,
            pat.patologia_pat,
            an.id_antecedente,
            an.antecedente_ant,
            vi.fk_internacao_vis, 
            vi.rel_visita_vis, 
            vi.acoes_int_vis, 
            vi.usuario_create,
            vi.visita_auditor_prof_med,
            vi.visita_auditor_prof_enf,
            vi.visita_med_vis,
            vi.visita_enf_vis,
            vi.visita_no_vis,
            vi.fk_usuario_vis,
            vi.data_visita_vis,
            vi.id_visita,
            ho.id_hospital,
            al.fk_id_int_alt,
            al.id_alta,
            al.internado_alt,
            ut.fk_internacao_uti,
            ut.internacao_uti,
            ut.internado_uti,
            ut.id_uti,
            ho.nome_hosp,
            tu.fk_int_tuss,
            tu.tuss_solicitado,
            tu.data_realizacao_tuss,
            COUNT(DISTINCT vi.id_visita) AS numero_de_id_visita

            FROM tb_internacao ac 

                left JOIN tb_hospital as ho On  
                ac.fk_hospital_int = ho.id_hospital

                left JOIN tb_uti as ut On  
                ac.id_internacao = ut.fk_internacao_uti

                left JOIN tb_visita as vi On  
                ac.id_internacao = vi.fk_internacao_vis

                left JOIN tb_patologia as pat On  
                ac.fk_patologia_int = pat.id_patologia

                left JOIN tb_antecedente as an On  
                ac.fk_patologia2 = an.id_antecedente

                left JOIN tb_alta as al On  
                ac.id_internacao = al.fk_id_int_alt

                left join tb_paciente as pa on
                ac.fk_paciente_int = pa.id_paciente

                left join tb_tuss as tu on
                ac.id_internacao = tu.fk_int_tuss

            ' . $where . ' ' . $group . ' ' . $order . ' ' . $limit);

        $query->execute();

        $hospital = $query->fetchAll();

        return $hospital;
    }

    public function QtdInternacao($where = null, $order = null, $limit = null)
    {
        $internacao = [];
        //DADOS DA QUERY
        $where = strlen($where) ? 'WHERE ' . $where : '';
        $order = strlen($order) ? 'ORDER BY ' . $order : '';
        $limit = strlen($limit) ? 'LIMIT ' . $limit : '';
        $group = ' GROUP BY ac.id_internacao ';

        $stmt = $this->conn->query('SELECT COUNT(id_internacao) as qtd, 
        ho.id_hospital, 
        ac.id_internacao, 
        ac.acoes_int, 
        ac.data_intern_int,
        ac.hora_intern_int, 
        ac.data_visita_int, 
        ac.rel_int, 
        ac.fk_paciente_int, 
        ac.usuario_create_int, 
        ac.fk_hospital_int, 
        ac.modo_internacao_int, 
        ac.tipo_admissao_int,
        ac.internado_uti_int,
        ac.internacao_uti_int,
        ac.especialidade_int, 
        ac.titular_int, 
        ac.crm_int, 
        ac.grupo_patologia_int, 
        ac.acomodacao_int, 
        ac.fk_patologia_int, 
        ac.fk_patologia2, 
        ac.internado_int,
        ac.visita_no_int,
        ac.primeira_vis_int,
        pa.id_paciente,
        pa.nome_pac,
        ho.id_hospital,
        al.fk_id_int_alt,
        al.id_alta,
        al.internado_alt, 
        ut.fk_internacao_uti,
        ut.internacao_uti,
        ut.internado_uti,
        ut.id_uti,
        ho.nome_hosp 

        FROM tb_internacao ac 

        left JOIN tb_uti as ut On  
        ac.id_internacao = ut.fk_internacao_uti

        left join tb_paciente as pa on
        ac.fk_paciente_int = pa.id_paciente
        
        iNNER JOIN tb_hospital as ho On  
        ac.fk_hospital_int = ho.id_hospital 
        
        left JOIN tb_alta as al On  
        ac.id_internacao = al.fk_id_int_alt 

            ' . $where . '  ' . $order . ' ' . $limit);

        $stmt->execute();

        $QtdTotalInt = $stmt->fetch();

        return $QtdTotalInt;
    }
    // MODELO DE FILTRO COM SELECT ATUAL COM FILTROS E PAGINACAO
    public function selectAllInternacaoList($where = null, $order = null, $limit = null)
    {
        //DADOS DA QUERY
        $where = strlen($where) ? 'WHERE ' . $where : '';
        $order = strlen($order) ? 'ORDER BY ' . $order : '';
        $limit = strlen($limit) ? 'LIMIT ' . $limit : '';

        $group = ' GROUP BY ac.id_internacao ';

        //MONTA A QUERY
        $query = $this->conn->query('SELECT 
        ac.id_internacao, 
        ac.acoes_int, 
        ac.data_intern_int,
        ac.hora_intern_int, 
        ac.data_visita_int, 
        ac.rel_int, 
        ac.fk_paciente_int, 
        ac.usuario_create_int, 
        ac.fk_hospital_int, 
        ac.modo_internacao_int, 
        ac.tipo_admissao_int,
        ac.especialidade_int, 
        ac.titular_int, 
        ac.crm_int, 
        ac.grupo_patologia_int, 
        ac.acomodacao_int, 
        ac.fk_patologia_int, 
        ac.fk_patologia2, 
        ac.internado_int,
        ac.visita_no_int,
        ac.origem_int,
        ac.primeira_vis_int,
        ac.censo_int,
        ac.senha_int,
        pa.id_paciente,
        pa.nome_pac,
        ho.id_hospital, 
        ut.fk_internacao_uti,
        ut.internacao_uti,
        ut.internado_uti,
        ut.id_uti,
        vi.fk_internacao_vis,
        vi.rel_visita_vis,
        vi.acoes_int_vis,
        vi.visita_no_vis,
        vi.visita_auditor_prof_med,
        vi.visita_auditor_prof_enf,
        vi.visita_med_vis,
        vi.visita_enf_vis,
        vi.data_visita_vis,
        ho.id_hospital,
        ho.nome_hosp,
        hos.fk_hospital_user,
        hos.fk_usuario_hosp,
        se.id_usuario,
        se.usuario_user,
        se.cargo_user,
        ca.fk_int_capeante,
        ca.parcial_capeante,
        ca.parcial_num,
        ca.valor_apresentado_capeante,
        ca.valor_final_capeante,
        ca.senha_finalizada,
        an.intern_antec_ant_int,
        an.id_intern_antec
    
        FROM tb_internacao ac 
    
            LEFT JOIN tb_hospital AS ho ON  
            ac.fk_hospital_int = ho.id_hospital
            
			LEFT JOIN tb_hospitalUser AS hos ON
            hos.fk_hospital_user = ho.id_hospital
            
			LEFT JOIN tb_user AS se ON  
            se.id_usuario = hos.fk_usuario_hosp
            
            LEFT JOIN tb_uti AS ut ON  
            ac.id_internacao = ut.fk_internacao_uti
    
            LEFT JOIN tb_paciente AS pa ON
            ac.fk_paciente_int = pa.id_paciente 

            LEFT join tb_visita as vi on
            ac.id_internacao = vi.fk_internacao_vis
    
            LEFT JOIN tb_capeante AS ca on
            ac.id_internacao = ca.fk_int_capeante

            LEFT JOIN tb_intern_antec AS an on
            ac.id_internacao = fK_internacao_ant_int
            
             ' . $where . ' ' . $group . ' ' . $order . ' ' . $limit);

        $query->execute();

        $hospital = $query->fetchAll();

        return $hospital;
    }

    public function QtdInternacaoList($where = null, $order = null, $limit = null)
    {
        $internacao = [];
        //DADOS DA QUERY
        $where = strlen($where) ? 'WHERE ' . $where : '';
        $order = strlen($order) ? 'ORDER BY ' . $order : '';
        $limit = strlen($limit) ? 'LIMIT ' . $limit : '';
        $group = ' GROUP BY ac.id_internacao ';

        $stmt = $this->conn->query('SELECT COUNT(ac.id_internacao) as qtd, 
        ac.id_internacao, 
        ac.data_intern_int, 
        ac.data_visita_int, 
        ac.fk_paciente_int, 
        ac.fk_hospital_int, 
        ac.fk_patologia_int, 
        ac.fk_patologia2, 
        ac.internado_int,
        ac.censo_int,
        pa.id_paciente,
        pa.nome_pac,
        ho.id_hospital, 
        ho.nome_hosp,
        hos.fk_hospital_user,
        hos.fk_usuario_hosp,
        se.id_usuario,
        se.usuario_user,
        al.fk_id_int_alt,
        al.id_alta,
        al.internado_alt
    
        FROM tb_internacao ac 
    
            LEFT JOIN tb_hospital AS ho ON  
            ac.fk_hospital_int = ho.id_hospital
            
			LEFT JOIN tb_hospitalUser AS hos ON
            hos.fk_hospital_user = ho.id_hospital
            
			left JOIN tb_user AS se ON  
            se.id_usuario = hos.fk_usuario_hosp
            
            LEFT JOIN tb_paciente AS pa ON
            ac.fk_paciente_int = pa.id_paciente 

            LEFT JOIN tb_alta AS al ON
            ac.fk_paciente_int = al.fk_id_int_alt 
    
            ' . $where);

        $stmt->execute();

        $QtdTotalInt = $stmt->fetch();

        return $QtdTotalInt;
    }


    public function PreditivoIntPatologAntec($where = null)
    {
        $internacao = [];
        //DADOS DA QUERY
        $where = strlen($where) ? 'WHERE ' . $where : '';

        $stmt = $this->conn->query('SELECT 
            ac.id_internacao,
            ac.fk_patologia_int AS patologia,
            FLOOR(DATEDIFF(CURDATE(), pa.data_nasc_pac) / 365 / 5) * 5 AS faixa_etaria,
            an.id_intern_antec,
            AVG(
                CASE 
                    WHEN ac.internado_int = "n" THEN DATEDIFF(alt.data_alta_alt, ac.data_intern_int)
                    ELSE DATEDIFF(CURDATE(), ac.data_intern_int)
                END
            ) AS tempo_medio_internacao
        FROM tb_internacao ac

        LEFT JOIN tb_paciente AS pa ON
            ac.fk_paciente_int = pa.id_paciente

        LEFT JOIN tb_intern_antec AS an ON
            ac.id_internacao = an.fk_internacao_ant_int

        LEFT JOIN tb_alta AS alt ON
            ac.id_internacao = alt.fk_id_int_alt

        ' . $where . '

        GROUP BY 
            ac.fk_patologia_int,
            faixa_etaria,
            an.id_intern_antec; 
        ');


        $stmt->execute();

        $Preditivos = $stmt->fetch();

        return $Preditivos;
    }



    // ********* \\ ********
    // ********* MODELO DE FILTRO COM SELECT ATUAL COM FILTROS E PAGINACAO CAPEANTE ********
    // ********* \\ ********
    public function selectAllInternacaoCap($where = null, $order = null, $limit = null)
    {
        //DADOS DA QUERY
        $where = strlen($where) ? 'WHERE ' . $where : '';
        $order = strlen($order) ? 'ORDER BY ' . $order : '';
        $limit = strlen($limit) ? 'LIMIT ' . $limit : '';

        //MONTA A QUERY
        $query = $this->conn->query(
            'SELECT 
    ac.id_internacao, 
    ac.acoes_int, 
    ac.data_intern_int,
    ac.hora_intern_int, 
    ac.data_visita_int, 
    ac.rel_int, 
    ac.fk_paciente_int, 
    ac.usuario_create_int, 
    ac.fk_hospital_int, 
    ac.modo_internacao_int, 
    ac.tipo_admissao_int,
    ac.especialidade_int, 
    ac.titular_int, 
    ac.crm_int, 
    ac.grupo_patologia_int, 
    ac.acomodacao_int, 
    ac.fk_patologia_int, 
    ac.fk_patologia2, 
    ac.internado_int,
    ac.visita_no_int,
    ac.primeira_vis_int,
    ac.origem_int,
    pa.id_paciente,
    pa.nome_pac,
    ho.id_hospital,
    ho.nome_hosp, 
    hos.fk_hospital_user,
    hos.fk_usuario_hosp,
    se.id_usuario,
    se.usuario_user,
    ut.fk_internacao_uti,
    ut.id_uti,
    ca.id_capeante,
    ca.data_inicial_capeante,
    ca.data_final_capeante,
    ca.diarias_capeante,
    ca.lote_cap,
    ca.fk_int_capeante,
    ca.glosa_diaria,
    ca.glosa_honorarios,
    ca.glosa_matmed,
    ca.glosa_oxig,
    ca.glosa_sadt,
    ca.glosa_taxas,
    ca.glosa_opme,
    ca.pacote,
    ca.parcial_capeante,
    ca.parcial_num,
    ca.valor_diarias,
    ca.valor_glosa_enf,
    ca.valor_glosa_med,
    ca.valor_glosa_total,
    ca.valor_honorarios,
    ca.valor_matmed,
    ca.valor_oxig,
    ca.valor_sadt,
    ca.valor_taxa,
    ca.valor_opme,
    ca.senha_finalizada,
    ca.glosa_total,
    ca.valor_apresentado_capeante,
    ca.valor_final_capeante,
    ca.adm_check,
    ca.med_check,
    ca.enfer_check,
    ca.aberto_cap,
    ca.em_auditoria_cap,
    ca.encerrado_cap,
    ca.negociado_desconto_cap,
    ca.desconto_valor_cap,
    ca.conta_parada_cap,
    ca.parada_motivo_cap,
    ca.fk_id_aud_enf,
    ca.fk_id_aud_med,
    ca.fk_id_aud_adm,
    ca.fk_id_aud_hosp


    FROM tb_internacao ac 

        LEFT JOIN tb_hospital AS ho ON  
        ac.fk_hospital_int = ho.id_hospital

        LEFT JOIN tb_hospitalUser AS hos ON
        hos.fk_hospital_user = ho.id_hospital
            
		LEFT JOIN tb_user AS se ON  
        se.id_usuario = hos.fk_usuario_hosp

        LEFT JOIN tb_uti AS ut ON  
        ac.id_internacao = ut.fk_internacao_uti

        LEFT join tb_paciente AS pa ON
        ac.fk_paciente_int = pa.id_paciente 

        left join tb_capeante AS ca ON
        ac.id_internacao = ca.fk_int_capeante 
        
        ' . $where . '' . $order . ' ' . $limit
        );
        // print_r($query);
        // exit;
        $query->execute();

        $hospital = $query->fetchAll();

        return $hospital;
    }


    // ********* \\ ********
    // ********* MODELO PARA CRIACAO DE CAPEANTE PARCIAL ********
    // ********* \\ ********
    public function selectAllInternacaoNewCap($where = null, $order = null, $limit = null)
    {
        //DADOS DA QUERY
        $where = strlen($where) ? 'WHERE ' . $where : '';
        $order = strlen($order) ? 'ORDER BY ' . $order : '';
        $limit = strlen($limit) ? 'LIMIT ' . $limit : '';

        //MONTA A QUERY
        $query = $this->conn->query(
            'SELECT 
    ac.id_internacao, 
    ac.acoes_int, 
    ac.data_intern_int, 
    ac.data_visita_int, 
    ac.rel_int, 
    ac.fk_paciente_int, 
    ac.usuario_create_int, 
    ac.fk_hospital_int, 
    ac.modo_internacao_int, 
    ac.tipo_admissao_int,
    ac.especialidade_int, 
    ac.titular_int, 
    ac.crm_int, 
    ac.grupo_patologia_int, 
    ac.acomodacao_int, 
    ac.fk_patologia_int, 
    ac.fk_patologia2, 
    ac.internado_int,
    ac.visita_no_int,
    ac.primeira_vis_int,
    ac.origem_int,
    pa.id_paciente,
    pa.nome_pac,
    ho.id_hospital,
    ho.nome_hosp, 
    hos.fk_hospital_user,
    hos.fk_usuario_hosp,
    se.id_usuario,
    se.usuario_user,
    ut.fk_internacao_uti,
    ut.id_uti,
    ca.id_capeante,
    ca.data_inicial_capeante,
    ca.data_final_capeante,
    ca.diarias_capeante,
    ca.fk_int_capeante,
    ca.glosa_diaria,
    ca.glosa_honorarios,
    ca.glosa_matmed,
    ca.glosa_oxig,
    ca.glosa_sadt,
    ca.glosa_taxas,
    ca.pacote,
    ca.parcial_capeante,
    ca.parcial_num,
    ca.valor_diarias,
    ca.valor_glosa_enf,
    ca.valor_glosa_med,
    ca.valor_glosa_total,
    ca.valor_honorarios,
    ca.valor_matmed,
    ca.valor_oxig,
    ca.valor_sadt,
    ca.valor_taxa,
    ca.senha_finalizada,
    ca.glosa_total,
    ca.valor_apresentado_capeante,
    ca.valor_final_capeante,
    ca.adm_check,
    ca.med_check,
    ca.enfer_check,
    ca.aberto_cap,
    ca.em_auditoria_cap,
    ca.encerrado_cap,
    ca.negociado_desconto_cap,
    ca.desconto_valor_cap,
    ca.conta_parada_cap,
    ca.parada_motivo_cap,
    ca.fk_id_aud_enf,
    ca.fk_id_aud_med,
    ca.fk_id_aud_adm,
    ca.fk_id_aud_hosp

    FROM tb_internacao ac 

        LEFT JOIN tb_hospital AS ho ON  
        ac.fk_hospital_int = ho.id_hospital

        LEFT JOIN tb_hospitalUser AS hos ON
        hos.fk_hospital_user = ho.id_hospital
            
		LEFT JOIN tb_user AS se ON  
        se.id_usuario = hos.fk_usuario_hosp

        LEFT JOIN tb_uti AS ut ON  
        ac.id_internacao = ut.fk_internacao_uti

        LEFT join tb_paciente AS pa ON
        ac.fk_paciente_int = pa.id_paciente 

        left join tb_capeante AS ca ON
        NULL = ca.fk_int_capeante 
        
        ' . $where . '' . $order . ' ' . $limit
        );
        // print_r($query);
        // exit;
        $query->execute();

        $hospital = $query->fetchAll();

        return $hospital;
    }

    public function QtdInternacaoCap($where = null, $order = null, $limit = null)
    {
        $internacao = [];
        //DADOS DA QUERY
        $where = strlen($where) ? 'WHERE ' . $where : '';
        $order = strlen($order) ? 'ORDER BY ' . $order : '';
        $limit = strlen($limit) ? 'LIMIT ' . $limit : '';
        $group = ' GROUP BY ac.id_internacao ';

        $stmt = $this->conn->query('SELECT COUNT(id_internacao) as qtd, 
        ac.id_internacao, 
        ac.acoes_int, 
        ac.data_intern_int, 
        ac.data_visita_int, 
        ac.rel_int, 
        ac.fk_paciente_int, 
        ac.usuario_create_int, 
        ac.fk_hospital_int, 
        ac.modo_internacao_int, 
        ac.tipo_admissao_int,
        ac.especialidade_int, 
        ac.titular_int, 
        ac.crm_int, 
        ac.grupo_patologia_int, 
        ac.origem_int, 
        ac.acomodacao_int, 
        ac.fk_patologia_int, 
        ac.fk_patologia2, 
        ac.internado_int,
        ac.visita_no_int,
        ac.primeira_vis_int,
        pa.id_paciente,
        pa.nome_pac,
        ho.id_hospital,
        ho.nome_hosp, 
        hos.fk_hospital_user,
        hos.fk_usuario_hosp,
        se.id_usuario,
        se.usuario_user,
        ut.fk_internacao_uti,
        ut.id_uti,
        ca.id_capeante,
        ca.data_inicial_capeante,
        ca.data_final_capeante,
        ca.diarias_capeante,
        ca.fk_int_capeante,
        ca.glosa_diaria,
        ca.glosa_honorarios,
        ca.glosa_matmed,
        ca.glosa_oxig,
        ca.glosa_sadt,
        ca.glosa_taxas,
        ca.pacote,
        ca.parcial_capeante,
        ca.parcial_num,
        ca.valor_diarias,
        ca.valor_glosa_enf,
        ca.valor_glosa_med,
        ca.valor_glosa_total,
        ca.valor_honorarios,
        ca.valor_matmed,
        ca.valor_oxig,
        ca.valor_sadt,
        ca.valor_taxa,
        ca.senha_finalizada,
        ca.glosa_total,
        ca.valor_apresentado_capeante,
        ca.valor_final_capeante,
        ca.adm_check,
        ca.med_check,
        ca.enfer_check,
        ca.parcial_num,
        ca.parcial_capeante,
        ca.senha_finalizada,
        ca.aberto_cap,
        ca.em_auditoria_cap,
        ca.encerrado_cap,
        ca.negociado_desconto_cap,
        ca.desconto_valor_cap,
        ca.conta_parada_cap,
        ca.parada_motivo_cap

    FROM tb_internacao ac 

        LEFT JOIN tb_hospital AS ho ON  
        ac.fk_hospital_int = ho.id_hospital

        LEFT JOIN tb_hospitalUser AS hos ON
        hos.fk_hospital_user = ho.id_hospital
            
		LEFT JOIN tb_user AS se ON  
        se.id_usuario = hos.fk_usuario_hosp

        LEFT JOIN tb_uti AS ut ON  
        ac.id_internacao = ut.fk_internacao_uti

        LEFT join tb_paciente AS pa ON
        ac.fk_paciente_int = pa.id_paciente 

        LEFT join tb_capeante AS ca ON
        ac.id_internacao = ca.fk_int_capeante 
        
        ' . $where . '  ' . $order . ' ' . $limit);

        $stmt->execute();

        $QtdTotalInt = $stmt->fetch();

        return $QtdTotalInt;
    }
    // ********* \\ ********
    // ********* MODELO DE FILTRO COM SELECT ATUAL COM FILTROS E PAGINACAO CAPEANTE ********
    // ********* \\ ********
    public function selectAllInternacaoCapList($where = null, $order = null, $limit = null)
    {
        //DADOS DA QUERY
        $where = strlen($where) ? 'WHERE ' . $where : '';
        $order = strlen($order) ? 'ORDER BY ' . $order : '';
        $limit = strlen($limit) ? 'LIMIT ' . $limit : '';
        $group = ' GROUP BY ca.id_capeante ';

        //MONTA A QUERY
        $query = $this->conn->query('SELECT 
        ac.id_internacao, 
        ac.acoes_int, 
        ac.data_intern_int, 
        ac.data_visita_int, 
        ac.rel_int, 
        ac.fk_paciente_int, 
        ac.usuario_create_int, 
        ac.fk_hospital_int, 
        ac.modo_internacao_int, 
        ac.tipo_admissao_int,
        ac.especialidade_int, 
        ac.titular_int, 
        ac.crm_int, 
        ac.senha_int, 
        ac.origem_int, 
        ac.grupo_patologia_int, 
        ac.acomodacao_int, 
        ac.fk_patologia_int, 
        ac.fk_patologia2, 
        ac.internado_int,
        ac.visita_no_int,
        ac.primeira_vis_int,
        pa.id_paciente,
        pa.nome_pac,
        ho.id_hospital, 
        ut.fk_internacao_uti,
        ut.id_uti,
        ho.nome_hosp,
        hos.fk_hospital_user,
        hos.fk_usuario_hosp,
        se.id_usuario,
        se.usuario_user,
        ca.id_capeante,
        ca.fk_int_capeante,
        ca.valor_apresentado_capeante,
        ca.valor_final_capeante,
        ca.adm_check,
        ca.med_check,
        ca.encerrado_cap,
        ca.aberto_cap,
        ca.enfer_check,
        ca.parcial_num,
        ca.parcial_capeante,
        ca.em_auditoria_cap,    
        ca.senha_finalizada,
        ca.conta_parada_cap,
        ca.parada_motivo_cap,
        ca.lote_cap

        FROM tb_internacao ac 

            LEFT JOIN tb_hospital AS ho ON  
            ac.fk_hospital_int = ho.id_hospital

            LEFT JOIN tb_hospitalUser AS hos ON
            hos.fk_hospital_user = ho.id_hospital

            left JOIN tb_user AS se ON  
            se.id_usuario = hos.fk_usuario_hosp

            left JOIN tb_uti as ut On  
            ac.id_internacao = ut.fk_internacao_uti

            left join tb_paciente as pa on
            ac.fk_paciente_int = pa.id_paciente 

            left join tb_capeante as ca on
            ac.id_internacao = ca.fk_int_capeante 
        
        ' . $where . ' ' . $group . '' . $order . ' ' . $limit);

        $query->execute();

        $hospital = $query->fetchAll();

        return $hospital;
    }


    public function QtdInternacaoCapList($where = null, $order = null, $limit = null)
    {
        $internacao = [];
        //DADOS DA QUERY
        $where = strlen($where) ? 'WHERE ' . $where : '';
        $order = strlen($order) ? 'ORDER BY ' . $order : '';
        $limit = strlen($limit) ? 'LIMIT ' . $limit : '';
        $group = ' GROUP BY ca.fk_int_capeante ';

        $stmt = $this->conn->query('SELECT COUNT(id_internacao) as qtd, 
        ho.id_hospital, 
        ac.id_internacao, 
        ac.acoes_int, 
        ac.data_intern_int, 
        ac.data_visita_int, 
        ac.rel_int, 
        ac.fk_paciente_int, 
        ac.usuario_create_int, 
        ac.fk_hospital_int, 
        ac.modo_internacao_int, 
        ac.tipo_admissao_int,
        ac.especialidade_int, 
        ac.titular_int, 
        ac.crm_int,
        ac.senha_int, 
        ac.grupo_patologia_int, 
        ac.acomodacao_int, 
        ac.fk_patologia_int, 
        ac.fk_patologia2, 
        ac.internado_int,
        ac.visita_no_int,
        ac.primeira_vis_int,
        pa.id_paciente,
        pa.nome_pac,
        ho.id_hospital, 
        hos.fk_hospital_user,
        hos.fk_usuario_hosp,
        se.id_usuario,
        se.usuario_user,
        ut.fk_internacao_uti,
        ut.internacao_uti,
        ut.internado_uti,
        ut.id_uti,
        ho.nome_hosp, 
        ca.fk_int_capeante,
        ca.valor_apresentado_capeante,
        ca.valor_final_capeante,
        ca.adm_check,
        ca.med_check,
        ca.enfer_check,
        ca.parcial_num,
        ca.parcial_capeante,
        ca.senha_finalizada,
        ca.conta_parada_cap,
        ca.parada_motivo_cap,
        ca.lote_cap

        FROM tb_internacao ac 

        LEFT JOIN tb_hospital AS ho ON  
        ac.fk_hospital_int = ho.id_hospital

        LEFT JOIN tb_hospitalUser AS hos ON
        hos.fk_hospital_user = ho.id_hospital

        left JOIN tb_user AS se ON  
        se.id_usuario = hos.fk_usuario_hosp

        left JOIN tb_uti as ut On  
        ac.id_internacao = ut.fk_internacao_uti

        left join tb_paciente as pa on
        ac.fk_paciente_int = pa.id_paciente 

        left join tb_capeante as ca on
        ac.id_internacao = ca.fk_int_capeante 
        
        ' . $where . '  ' . $group . '' . $order . ' ' . $limit);

        $stmt->execute();

        $QtdTotalInt = $stmt->fetch();

        return $QtdTotalInt;
    }

    // METODO PARA LOCALIZAR INTERNACAO PELO ID - UTILIZADO NA ALTA.
    public function findById($id_internacao)
    {
        $internacao = [];
        $stmt = $this->conn->prepare("SELECT * FROM tb_internacao
                                    WHERE id_internacao = :id_internacao");
        $stmt->bindParam(":id_internacao", $id_internacao);
        $stmt->execute();

        $data = $stmt->fetch();


        // var_dump($data);
        $internacao = $this->buildinternacao($data);

        return $internacao;
    }


    // METODO PARA LOCALIZAR INTERNACAO PELO ID - UTILIZADO NA ALTA.
    public function findByIdArray($id_internacao)
    {
        $internacao = [];
        $stmt = $this->conn->prepare("SELECT * FROM tb_internacao
                                    WHERE id_internacao = :id_internacao");
        $stmt->bindParam(":id_internacao", $id_internacao);
        $stmt->execute();

        $internacao = $stmt->fetchAll();

        return $internacao;
    }



    // ********* \     VISITA    \ ********
    // ********* MODELO DE FILTRO COM SELECT ATUAL COM FILTROS E PAGINACAO VISITA ********
    // ********* \                \ ********
    public function selectAllInternacaoVis($where = null, $order = null, $limit = null)
    {
        //DADOS DA QUERY
        $where = strlen($where) ? 'WHERE ' . $where : '';
        $order = strlen($order) ? 'ORDER BY ' . $order : '';
        $limit = strlen($limit) ? 'LIMIT ' . $limit : '';
        $group = ' GROUP BY ac.id_internacao ';

        //MONTA A QUERY
        $query = $this->conn->query('SELECT    
    ac.id_internacao, 
    ac.acoes_int, 
    ac.data_intern_int, 
    ac.data_visita_int, 
    ac.rel_int, 
    ac.fk_paciente_int, 
    ac.usuario_create_int, 
    ac.fk_hospital_int, 
    ac.modo_internacao_int, 
    ac.tipo_admissao_int,
    ac.especialidade_int, 
    ac.titular_int, 
    ac.crm_int, 
    ac.grupo_patologia_int, 
    ac.acomodacao_int, 
    ac.fk_patologia_int, 
    ac.fk_patologia2, 
    ac.internado_int,
    ac.visita_no_int,
    ac.primeira_vis_int,
    pa.id_paciente,
    pa.nome_pac,
    ho.id_hospital, 
    ho.nome_hosp, 
    vi.fk_internacao_vis,
    vi.rel_visita_vis,
    vi.acoes_int_vis,
    vi.visita_no_vis,
    vi.visita_auditor_prof_med,
    vi.visita_auditor_prof_enf,
    vi.visita_med_vis,
    vi.visita_enf_vis,
    vi.data_visita_vis
    -- tu.fk_int_tuss
    
    FROM tb_internacao ac 

        LEFT JOIN tb_hospital as ho On  
        ac.fk_hospital_int = ho.id_hospital

        LEFT join tb_paciente as pa on
        ac.fk_paciente_int = pa.id_paciente 

        left join tb_visita as vi on
        ac.id_internacao = vi.fk_internacao_vis 

        -- left join tb_tuss as tu on
        -- ac.id_internacao = tu.fk_int_tuss 
        
        ' . $where . ' ' . $group . ' ' . $order . ' ' . $limit);

        $query->execute();

        $hospital = $query->fetchAll();

        return $hospital;
    }




    public function selectInternVis($id_internacao2)
    {
        $internacao = [];
        //DADOS DA QUERY
        $id_internacao2 = strlen($id_internacao2) ? 'WHERE ' . $id_internacao2 : '';
        $group = ' GROUP BY vi.fk_internacao_vis ';

        //MONTA A QUERY
        $query = $this->conn->query(
            "SELECT
            count(vi.fk_internacao_vis) as num_visitas,
            ac.id_internacao, 
            ac.data_visita_int,
            ac.fk_hospital_int,
            vi.id_visita,
            vi.fk_internacao_vis,
            vi.visita_no_vis,
            vi.data_visita_vis,
            ho.id_hospital,
            hos.fk_hospital_user,
            hos.fk_usuario_hosp,
            se.id_usuario,
            se.cargo_user
            
        FROM tb_internacao ac 
        
        INNER JOIN tb_visita AS vi ON
            ac.id_internacao = vi.fk_internacao_vis
                
        LEFT JOIN tb_hospital AS ho ON  
            ac.fk_hospital_int = ho.id_hospital
        
        LEFT JOIN tb_hospitalUser AS hos ON
            hos.fk_hospital_user = ho.id_hospital
        
        LEFT JOIN tb_user AS se ON  
            se.id_usuario = hos.fk_usuario_hosp
        
            ' . $id_internacao2 . '  ' . $group .'"
        );
        $query->execute();

        $visitas = $query->fetchAll();

        return $visitas;
    }
    public function selectInternVisCargo($wherevisitaCargo)
    {
        $internacao = [];
        //DADOS DA QUERY
        $wherevisitaCargo = strlen($wherevisitaCargo) ? 'WHERE ' . $wherevisitaCargo : '';

        //MONTA A QUERY
        $query = $this->conn->query(
            "SELECT
            ac.id_internacao, 
            ac.data_visita_int,
            ac.fk_hospital_int,
            vi.id_visita,
            vi.fk_internacao_vis,
            vi.visita_no_vis,
            vi.data_visita_vis,
            ho.id_hospital,
            hos.fk_hospital_user,
            hos.fk_usuario_hosp,
            se.id_usuario,
            se.cargo_user
            
        FROM tb_internacao ac 
        
        INNER JOIN tb_visita AS vi ON
            ac.id_internacao = vi.fk_internacao_vis
                
        LEFT JOIN tb_hospital AS ho ON  
            ac.fk_hospital_int = ho.id_hospital
        
        LEFT JOIN tb_hospitalUser AS hos ON
            hos.fk_hospital_user = ho.id_hospital
        
        LEFT JOIN tb_user AS se ON  
            se.id_usuario = hos.fk_usuario_hosp
        
        $wherevisitaCargo"
        );
        $query->execute();

        $visitas = $query->fetchAll();

        return $visitas;
    }
    public function selectInternVisita($id_internacao2)
    {
        $internacao = [];
        //DADOS DA QUERY

        $query = $this->conn->query(
            "SELECT
                count(vi.fk_internacao_vis) as num_visitas,
                ac.id_internacao, 
                ac.data_visita_int,
                ac.fk_hospital_int,
                vi.id_visita,
                vi.fk_internacao_vis,
                vi.visita_no_vis,
                vi.data_visita_vis,
                ho.id_hospital,
                hos.fk_hospital_user,
                hos.fk_usuario_hosp,
                se.id_usuario,
                se.cargo_user
                
            FROM tb_internacao ac 
            
            INNER JOIN tb_visita AS vi ON
                ac.id_internacao = vi.fk_internacao_vis
                    
            LEFT JOIN tb_hospital AS ho ON  
                ac.fk_hospital_int = ho.id_hospital
            
            LEFT JOIN tb_hospitalUser AS hos ON
                hos.fk_hospital_user = ho.id_hospital
            
            LEFT JOIN tb_user AS se ON  
                se.id_usuario = hos.fk_usuario_hosp
            
            WHERE fk_internacao_vis = $id_internacao2 "
        );
        $query->execute();

        $hospital = $query->fetchAll();

        return $hospital;
    }

    public function selectInternVisitaCargo($wherevisita)
    {
        $internacao = [];
        //DADOS DA QUERY
        $wherevisita = strlen($wherevisita) ? 'WHERE ' . $wherevisita : '';

        $query = $this->conn->query(
            "SELECT
                ac.id_internacao, 
                ac.data_visita_int,
                ac.fk_hospital_int,
                vi.id_visita,
                vi.fk_internacao_vis,
                vi.visita_no_vis,
                vi.data_visita_vis,
                ho.id_hospital,
                hos.fk_hospital_user,
                hos.fk_usuario_hosp,
                se.id_usuario,
                se.cargo_user
                
            FROM tb_internacao ac 
            
            INNER JOIN tb_visita AS vi ON
                ac.id_internacao = vi.fk_internacao_vis
                    
            LEFT JOIN tb_hospital AS ho ON  
                ac.fk_hospital_int = ho.id_hospital
            
            LEFT JOIN tb_hospitalUser AS hos ON
                hos.fk_hospital_user = ho.id_hospital
            
            LEFT JOIN tb_user AS se ON  
                se.id_usuario = hos.fk_usuario_hosp
            
                $wherevisita"
        );
        $query->execute();

        $visita = $query->fetchAll();

        return $visita;
    }
    public function selectInternVisitaCargoMax($wherevisita)
    {
        $internacao = [];
        //DADOS DA QUERY
        $wherevisita = strlen($wherevisita) ? 'WHERE ' . $wherevisita : '';

        $query = $this->conn->query(
            "SELECT
                ac.id_internacao, 
                ac.data_visita_int,
                ac.fk_hospital_int,
                vi.id_visita,
                vi.fk_internacao_vis,
                vi.visita_no_vis,
                vi.data_visita_vis,
                ho.id_hospital,
                hos.fk_hospital_user,
                hos.fk_usuario_hosp,
                se.id_usuario,
                se.cargo_user
                
            FROM tb_internacao ac 
            
            INNER JOIN tb_visita AS vi ON
                ac.id_internacao = vi.fk_internacao_vis
                    
            LEFT JOIN tb_hospital AS ho ON  
                ac.fk_hospital_int = ho.id_hospital
            
            LEFT JOIN tb_hospitalUser AS hos ON
                hos.fk_hospital_user = ho.id_hospital
            
            LEFT JOIN tb_user AS se ON  
                se.id_usuario = hos.fk_usuario_hosp
            
                $wherevisita AND vi.id_visita = (
                    SELECT MAX(id_visita)
                    FROM tb_visita
                    WHERE fk_internacao_vis = 24"
        );
        $query->execute();

        $visitaMax = $query->fetchAll();

        return $visitaMax;
    }

    public function QtdInternacaoVis($where = null, $order = null, $limit = null)
    {
        $internacao = [];
        //DADOS DA QUERY
        $where = strlen($where) ? 'WHERE ' . $where : '';
        $order = strlen($order) ? 'ORDER BY ' . $order : '';
        $limit = strlen($limit) ? 'LIMIT ' . $limit : '';
        $group = ' GROUP BY ac.id_internacao ';


        $stmt = $this->conn->query('SELECT COUNT(id_internacao) as qtd, 
        ho.id_hospital, 
        ac.id_internacao, 
        ac.acoes_int, 
        ac.data_intern_int, 
        ac.data_visita_int, 
        ac.rel_int, 
        ac.fk_paciente_int, 
        ac.usuario_create_int, 
        ac.fk_hospital_int, 
        ac.modo_internacao_int, 
        ac.tipo_admissao_int,
        ac.especialidade_int, 
        ac.titular_int,
        ac.crm_int, 
        ac.grupo_patologia_int, 
        ac.acomodacao_int, 
        ac.fk_patologia_int, 
        ac.fk_patologia2, 
        ac.internado_int,
        ac.visita_no_int,
        ac.primeira_vis_int,
        pa.id_paciente,
        pa.nome_pac,
        ho.id_hospital, 
        ho.nome_hosp, 
        vi.fk_internacao_vis,
        vi.acoes_int_vis,
        vi.rel_visita_vis,
        vi.visita_no_vis,
        vi.visita_auditor_prof_med,
        vi.visita_auditor_prof_enf,
        vi.visita_med_vis,
        vi.visita_enf_vis,
        vi.data_visita_vis

        FROM tb_internacao ac 

        left join tb_paciente as pa on
        ac.fk_paciente_int = pa.id_paciente
        
        left JOIN tb_hospital as ho On  
        ac.fk_hospital_int = ho.id_hospital 
        
        left join tb_visita as vi on
        ac.id_internacao = vi.fk_internacao_vis 
        
        
        ' . $where . ' ' . $group . ' ' . $order . ' ' . $limit);

        $stmt->execute();

        $QtdTotalInt = $stmt->fetch();

        return $QtdTotalInt;
    }

    public function selectInternVisLast()
    {

        //MONTA A QUERY
        $query = $this->conn->query(
            "SELECT
                ac.id_internacao, 
                ac.data_visita_int,
                vi.id_visita,
                vi.fk_internacao_vis,
                vi.visita_no_vis,
                vi.data_visita_vis,
                pa.nome_pac
                
                FROM tb_internacao ac 

                left join tb_visita as vi on
                ac.id_internacao = vi.fk_internacao_vis

                inner join tb_paciente as pa on
                ac.fk_paciente_int = pa.id_paciente

                WHERE (vi.id_visita = (SELECT MAX(vi2.id_visita) FROM tb_visita vi2 WHERE vi2.fk_internacao_vis = ac.id_internacao)  
                or vi.id_visita IS NULL)
                and ac.internado_int = 's'
                order by vi.id_visita DESC"

        );

        $query->execute();

        $hospital = $query->fetchAll();

        return $hospital;
    }

    public function selectInternVisLastWhere($where = null)
    {
        //DADOS DA QUERY
        $where = strlen($where) ? 'WHERE ' . $where : '';
        //MONTA A QUERY
        $query = $this->conn->query(
            "SELECT
                ac.id_internacao, 
                ac.data_visita_int,
                vi.id_visita,
                vi.fk_internacao_vis,
                vi.visita_no_vis,
                vi.data_visita_vis,
                pa.nome_pac,
                hos.nome_hosp
                
                FROM tb_internacao ac 

                left join tb_visita as vi on
                ac.id_internacao = vi.fk_internacao_vis

                left join tb_hospital as hos on 
                ac.fk_hospital_int = hos.id_hospital

                inner join tb_paciente as pa on
                ac.fk_paciente_int = pa.id_paciente " . $where
        );

        $query->execute();

        $hospital = $query->fetchAll();

        return $hospital;
    }


    // ********* \     PATOLOGIA    \ ********
    // ********* MODELO DE FILTRO COM SELECT ATUAL COM FILTROS E PAGINACAO POR PATOLOGIA ********
    // ********* \                   \ ********
    public function selectAllInternacaoPato($where = null, $order = null, $limit = null)
    {
        //DADOS DA QUERY
        $where = strlen($where) ? 'WHERE ' . $where : '';
        $order = strlen($order) ? 'ORDER BY ' . $order : '';
        $limit = strlen($limit) ? 'LIMIT ' . $limit : '';
        $group = ' GROUP BY ac.id_internacao ';

        //MONTA A QUERY
        $query = $this->conn->query('SELECT    
    ac.id_internacao, 
    ac.acoes_int, 
    ac.data_intern_int, 
    ac.data_visita_int, 
    ac.rel_int, 
    ac.fk_paciente_int, 
    ac.usuario_create_int, 
    ac.fk_hospital_int, 
    ac.modo_internacao_int, 
    ac.tipo_admissao_int,
    ac.especialidade_int, 
    ac.titular_int, 
    ac.crm_int, 
    ac.grupo_patologia_int, 
    ac.acomodacao_int, 
    ac.fk_patologia_int, 
    ac.fk_patologia2, 
    ac.internado_int,
    ac.censo_int,
    ac.visita_no_int,
    ac.fk_patologia_int,
    ac.primeira_vis_int,
    pa.id_paciente,
    pa.nome_pac,
    ho.id_hospital, 
    ho.nome_hosp,
    hos.fk_hospital_user,
    hos.fk_usuario_hosp,
    se.id_usuario,
    se.usuario_user, 
    vi.fk_internacao_vis,
    vi.rel_visita_vis,
    vi.acoes_int_vis,
    vi.visita_no_vis,
    vi.visita_auditor_prof_med,
    vi.visita_auditor_prof_enf,
    vi.visita_med_vis,
    vi.visita_enf_vis,
    vi.data_visita_vis,
    pt.id_patologia,
    pt.patologia_pat,
    pt.dias_pato
    
    FROM tb_internacao ac 

        LEFT JOIN tb_hospital AS ho ON  
        ac.fk_hospital_int = ho.id_hospital

        LEFT JOIN tb_hospitalUser AS hos ON
        hos.fk_hospital_user = ho.id_hospital

        left JOIN tb_user AS se ON  
        se.id_usuario = hos.fk_usuario_hosp

        LEFT join tb_paciente as pa on
        ac.fk_paciente_int = pa.id_paciente 

        left join tb_visita as vi on
        ac.id_internacao = vi.fk_internacao_vis 

        left join tb_patologia as pt on
        ac.fk_patologia_int = pt.id_patologia 
        
        ' . $where . ' ' . $group . ' ' . $order . ' ' . $limit);

        $query->execute();

        $hospital = $query->fetchAll();

        return $hospital;
    }
    // ********* \     PATOLOGIA    \ ********
    // ********* MODELO DE FILTRO COM SELECT ATUAL COM FILTROS E PAGINACAO POR PATOLOGIA ********
    // ********* \                   \ ********
    public function selectAllInternacaoPatoList($where = null, $order = null, $limit = null)
    {
        //DADOS DA QUERY
        $where = strlen($where) ? 'WHERE ' . $where : '';
        $order = strlen($order) ? 'ORDER BY ' . $order : '';
        $limit = strlen($limit) ? 'LIMIT ' . $limit : '';
        $group = ' GROUP BY ac.id_internacao ';

        //MONTA A QUERY
        $query = $this->conn->query('SELECT    
    ac.id_internacao, 
    ac.acoes_int, 
    ac.data_intern_int, 
    ac.data_visita_int, 
    ac.rel_int, 
    ac.fk_paciente_int, 
    ac.usuario_create_int, 
    ac.fk_hospital_int, 
    ac.modo_internacao_int, 
    ac.tipo_admissao_int,
    ac.especialidade_int, 
    ac.titular_int,
    ac.crm_int, 
    ac.senha_int, 
    ac.grupo_patologia_int, 
    ac.acomodacao_int, 
    ac.fk_patologia_int, 
    ac.fk_patologia2, 
    ac.internado_int,
    ac.visita_no_int,
    ac.censo_int,
    ac.fk_patologia_int,
    ac.primeira_vis_int,
    pa.id_paciente,
    pa.nome_pac,
    ho.id_hospital, 
    ho.nome_hosp, 
    vi.fk_internacao_vis,
    vi.rel_visita_vis,
    vi.acoes_int_vis,
    vi.visita_no_vis,
    vi.visita_auditor_prof_med,
    vi.visita_auditor_prof_enf,
    vi.visita_med_vis,
    vi.visita_enf_vis,
    vi.data_visita_vis,
    pt.id_patologia,
    pt.patologia_pat,
    pt.dias_pato,
    an.intern_antec_ant_int,
    an.id_intern_antec
    
    FROM tb_internacao ac 

        LEFT JOIN tb_hospital as ho On  
        ac.fk_hospital_int = ho.id_hospital

        LEFT JOIN tb_hospitalUser AS hos ON
        hos.fk_hospital_user = ho.id_hospital

        left JOIN tb_user AS se ON  
        se.id_usuario = hos.fk_usuario_hosp

        LEFT join tb_paciente as pa on
        ac.fk_paciente_int = pa.id_paciente 

        left join tb_visita as vi on
        ac.id_internacao = vi.fk_internacao_vis 

        left join tb_patologia as pt on
        ac.fk_patologia_int = pt.id_patologia 
        
        LEFT JOIN tb_intern_antec AS an on
        ac.id_internacao = fK_internacao_ant_int
            
        ' . $where . ' ' . $group . ' ' . $order . ' ' . $limit);

        $query->execute();

        $hospital = $query->fetchAll();

        return $hospital;
    }


    public function QtdInternacaoPatoList($where = null, $order = null, $limit = null)
    {
        //DADOS DA QUERY
        $where = strlen($where) ? 'WHERE ' . $where : '';
        $order = strlen($order) ? 'ORDER BY ' . $order : '';
        $limit = strlen($limit) ? 'LIMIT ' . $limit : '';
        $group = ' GROUP BY ac.id_internacao ';

        //MONTA A QUERY
        $query = $this->conn->query('SELECT COUNT(id_internacao) as qtd,   
        ac.id_internacao, 
        ac.acoes_int, 
        ac.data_intern_int, 
        ac.data_visita_int, 
        ac.rel_int, 
        ac.fk_paciente_int, 
        ac.usuario_create_int, 
        ac.fk_hospital_int, 
        ac.modo_internacao_int, 
        ac.tipo_admissao_int,
        ac.especialidade_int, 
        ac.titular_int,
        ac.crm_int,  
        ac.grupo_patologia_int, 
        ac.acomodacao_int, 
        ac.fk_patologia_int, 
        ac.fk_patologia2, 
        ac.internado_int,
        ac.visita_no_int,
        ac.fk_patologia_int,
        ac.primeira_vis_int,
        pa.id_paciente,
        pa.nome_pac,
        ho.id_hospital, 
        ho.nome_hosp, 
        vi.fk_internacao_vis,
        vi.rel_visita_vis,
        vi.acoes_int_vis,
        vi.visita_no_vis,
        vi.visita_auditor_prof_med,
        vi.visita_auditor_prof_enf,
        vi.visita_med_vis,
        vi.visita_enf_vis,
        vi.data_visita_vis,
        pt.id_patologia,
        pt.patologia_pat,
        pt.dias_pato
        
        FROM tb_internacao ac 

        LEFT JOIN tb_hospital as ho On  
        ac.fk_hospital_int = ho.id_hospital

        LEFT JOIN tb_hospitalUser AS hos ON
        hos.fk_hospital_user = ho.id_hospital

        left JOIN tb_user AS se ON  
        se.id_usuario = hos.fk_usuario_hosp

        LEFT join tb_paciente as pa on
        ac.fk_paciente_int = pa.id_paciente 

        left join tb_visita as vi on
        ac.id_internacao = vi.fk_internacao_vis 

        left join tb_patologia as pt on
        ac.fk_patologia_int = pt.id_patologia 
        
        ' . $where . ' ' . $group . ' ' . $order . ' ' . $limit);

        $query->execute();

        $hospital = $query->fetchAll();

        return $hospital;
    }
    public function selectInternPato($id_internacao)
    {

        //MONTA A QUERY
        $query = $this->conn->query(
            "SELECT
    ac.id_internacao, 
    ac.data_visita_int,
    vi.id_visita,
    vi.fk_internacao_vis,
    vi.visita_no_vis,
    vi.data_visita_vis
    
    FROM tb_internacao ac 

        inner join tb_visita as vi on
        ac.id_internacao = vi.fk_internacao_vis

        WHERE fk_internacao_vis = $id_internacao order by id_visita DESC LIMIT 1"

        );

        $query->execute();

        $hospital = $query->fetchAll();

        return $hospital;
    }

    public function QtdInternacaoPato($where = null, $order = null, $limit = null)
    {
        $internacao = [];
        //DADOS DA QUERY
        $where = strlen($where) ? 'WHERE ' . $where : '';
        $order = strlen($order) ? 'ORDER BY ' . $order : '';
        $limit = strlen($limit) ? 'LIMIT ' . $limit : '';
        $group = ' GROUP BY ac.id_internacao ';


        $stmt = $this->conn->query('SELECT COUNT(id_internacao) as qtd, 
        ho.id_hospital, 
        ac.id_internacao, 
        ac.acoes_int, 
        ac.data_intern_int, 
        ac.data_visita_int, 
        ac.rel_int, 
        ac.fk_paciente_int, 
        ac.usuario_create_int, 
        ac.fk_hospital_int, 
        ac.modo_internacao_int, 
        ac.tipo_admissao_int,
        ac.especialidade_int, 
        ac.titular_int, 
        ac.crm_int, 
        ac.grupo_patologia_int, 
        ac.acomodacao_int, 
        ac.fk_patologia_int, 
        ac.fk_patologia2, 
        ac.internado_int,
        ac.visita_no_int,
        ac.primeira_vis_int,
        pa.id_paciente,
        pa.nome_pac,
        ho.id_hospital, 
        ho.nome_hosp, 
        vi.fk_internacao_vis,
        vi.acoes_int_vis,
        vi.rel_visita_vis,
        vi.visita_no_vis,
        vi.visita_auditor_prof_med,
        vi.visita_auditor_prof_enf,
        vi.visita_med_vis,
        vi.visita_enf_vis,
        vi.data_visita_vis.,
        pt.id_patologia,
        pt.patologia_pat,
        pt.dias_pato

        FROM tb_internacao ac 

        left join tb_paciente as pa on
        ac.fk_paciente_int = pa.id_paciente
        
        left JOIN tb_hospital as ho On  
        ac.fk_hospital_int = ho.id_hospital 
        
        left join tb_visita as vi on
        ac.id_internacao = vi.fk_internacao_vis

        left join tb_patologia as pt on
        ac.fk_patologia_int = pt.id_patologia 
        
        
        ' . $where . ' ' . $group . ' ' . $order . ' ' . $limit);

        $stmt->execute();

        $QtdTotalInt = $stmt->fetch();

        return $QtdTotalInt;
    }

    public function QtdInternacaoListPag($where = null, $order = null, $limit = null)
    {
        $internacao = [];
        //DADOS DA QUERY
        $where = strlen($where) ? 'WHERE ' . $where : '';
        $order = strlen($order) ? 'ORDER BY ' . $order : '';
        $limit = strlen($limit) ? 'LIMIT ' . $limit : '';
        $group = ' GROUP BY ac.id_internacao ';

        $stmt = $this->conn->query('SELECT COUNT(id_internacao) as qtd,
        ac.id_internacao
    
        FROM tb_internacao ac 

            ' . $where . '' . $order . ' ' . $limit);

        $stmt->execute();

        $QtdTotalInt = $stmt->fetch();

        return $QtdTotalInt;
    }

    public function findMaxInt()
    {

        $gestao = [];

        $stmt = $this->conn->query("SELECT max(id_internacao) as ultimoReg from tb_internacao");

        $stmt->execute();

        $findMaxGesInt = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $findMaxGesInt;
    }

    public function checkInternAtiva($id_paciente)
    {

        $internacao = null;

        $stmt = $this->conn->prepare("SELECT id_internacao FROM tb_internacao WHERE internado_int = 's' and fk_paciente_int = :id_paciente");

        $stmt->bindValue(":id_paciente", $id_paciente);

        $stmt->execute();

        $internacao = $stmt->fetch();

        if ($internacao) {
            return $internacao['0'];
        }
    }


    public function findTotalByPacId($pac_id)
    {
        $internacao = [];

        $stmt = $this->conn->prepare("SELECT coalesce(round(sum(c.valor_apresentado_capeante),2),0) as total_capeante,
                                    sum(diarias_capeante) as total_diarias 
                                    FROM tb_internacao ac 
                                    join tb_capeante c on c.fk_int_capeante = ac.id_internacao
                                    WHERE fk_paciente_int = :pac_id ");

        $stmt->bindValue(":pac_id", $pac_id);

        $stmt->execute();

        $internacao = $stmt->fetchAll();
        return $internacao;
    }

    public function findTotalDiariasByPacId($pac_id)
    {
        $internacao = [];

        $stmt = $this->conn->prepare("SELECT coalesce(SUM(total_diarias),0) as total_diarias FROM (
                                    SELECT DATEDIFF(coalesce(al.data_alta_alt, current_date()), ac.data_intern_int) as total_diarias
                                    FROM tb_internacao ac 
                                    LEFT JOIN tb_alta al ON ac.id_internacao = al.fk_id_int_alt
                                    WHERE ac.fk_paciente_int = :pac_id) AS interns");

        $stmt->bindValue(":pac_id", $pac_id);

        $stmt->execute();

        $internacao = $stmt->fetchAll();
        return $internacao;
    }

    public function insertFiles($id_internacao, $nome_arquivo, $arquivo_blob)
    {
        // Prepare SQL statement
        $stmt = $this->conn->prepare("
        INSERT INTO tb_internacao_arquivo (id_internacao, nome_arquivo, arquivo)
        VALUES (:id_internacao, :nome_arquivo, ':arquivo')
    ");

        // Bind parameters;
        // Use the parameters passed to the function
        $stmt->bindParam(':id_internacao', $id_internacao, PDO::PARAM_INT);  // Use the actual $id_internacao passed
        $stmt->bindParam(':nome_arquivo', $nome_arquivo, PDO::PARAM_STR);
        $stmt->bindParam(':arquivo', $arquivo_blob, PDO::PARAM_LOB);  // LOB for binary data

        // Execute the query
        try {
            $stmt->execute();
            return true;  // Return true if insertion is successful
        } catch (PDOException $e) {
            return false;  // Return false if there was an error
        }
    }


    public function findTotalDiariasUtiByPacId($pac_id)
    {
        $internacao = [];

        $stmt = $this->conn->prepare("SELECT coalesce(SUM(total_diarias),0) as total_diarias FROM (
                                    SELECT DATEDIFF(coalesce(data_alta_uti, current_date()), data_internacao_uti) as total_diarias
                                    FROM 
                                    tb_internacao ac join
                                    tb_uti  u on ac.id_internacao = u.fk_internacao_uti
                                    WHERE ac.fk_paciente_int = :pac_id) AS interns");

        $stmt->bindValue(":pac_id", $pac_id);

        $stmt->execute();

        $internacao = $stmt->fetchAll();
        return $internacao;
    }

    public function reinternacao($where = null, $order = null, $limit = null)
    {

        $where = strlen($where) ? 'WHERE ' . $where : '';
        $order = strlen($order) ? 'ORDER BY ' . $order : '';
        $limit = strlen($limit) ? 'LIMIT ' . $limit : '';

        $stmt = $this->conn->query('SELECT 
    ac.id_internacao AS id_internacao_atual, 
    ac.fk_hospital_int,
    pa.nome_pac,
    ho.nome_hosp,
    ac_anterior.data_intern_int AS data_internacao_anterior,
    al_anterior.data_alta_alt AS data_alta_anterior,
    ac.data_intern_int AS data_internacao_atual,
    DATEDIFF(ac.data_intern_int, al_anterior.data_alta_alt) AS dias_reinternacao
FROM 
    tb_internacao ac
INNER JOIN 
    tb_hospital ho ON ac.fk_hospital_int = ho.id_hospital
INNER JOIN 
    tb_paciente pa ON ac.fk_paciente_int = pa.id_paciente
INNER JOIN 
    tb_internacao ac_anterior ON ac_anterior.fk_paciente_int = ac.fk_paciente_int 
        AND ac_anterior.fk_hospital_int = ac.fk_hospital_int 
        AND ac_anterior.data_intern_int < ac.data_intern_int
INNER JOIN 
    tb_alta al_anterior ON ac_anterior.id_internacao = al_anterior.fk_id_int_alt
WHERE 
    DATEDIFF(ac.data_intern_int, al_anterior.data_alta_alt) <= 2
    AND al_anterior.data_alta_alt IS NOT NULL
    AND ac.data_intern_int > al_anterior.data_alta_alt -- Internação ocorre após a alta anterior
ORDER BY 
    pa.nome_pac, ac.data_intern_int;
        
        ' . $where . '  ' . $order . ' ' . $limit);

        $stmt->execute();

        $reinternacao = $stmt->fetch();

        return $reinternacao;
    }
    public function reinternacaoNova($where_gerais_reint)
    {
        $where_gerais_reint = strlen($where_gerais_reint) ? ' AND ' . $where_gerais_reint : '';

        $stmt = $this->conn->query(
            'SELECT 
    ac.id_internacao AS id_internacao_atual, 
    ac.fk_hospital_int,
    pa.nome_pac,
    ho.nome_hosp,
    ac_anterior.data_intern_int AS data_internacao_anterior,
    al_anterior.data_alta_alt AS data_alta_anterior,
    ac.data_intern_int AS data_internacao_atual,
    DATEDIFF(ac.data_intern_int, al_anterior.data_alta_alt) AS dias_reinternacao
FROM 
    tb_internacao ac
INNER JOIN 
    tb_hospital ho ON ac.fk_hospital_int = ho.id_hospital
INNER JOIN 
    tb_paciente pa ON ac.fk_paciente_int = pa.id_paciente
INNER JOIN 
    tb_internacao ac_anterior ON ac_anterior.fk_paciente_int = ac.fk_paciente_int 
        AND ac_anterior.fk_hospital_int = ac.fk_hospital_int 
        AND ac_anterior.data_intern_int < ac.data_intern_int
INNER JOIN 
    tb_alta al_anterior ON ac_anterior.id_internacao = al_anterior.fk_id_int_alt
WHERE 
    DATEDIFF(ac.data_intern_int, al_anterior.data_alta_alt) <= 2
    AND al_anterior.data_alta_alt IS NOT NULL
    AND ac.data_intern_int > al_anterior.data_alta_alt'
                . $where_gerais_reint
        );

        $stmt->execute();

        $reinternacao = $stmt->fetchall();

        return $reinternacao;
    }
}
