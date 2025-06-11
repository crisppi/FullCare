<?php

require_once("./models/capeante.php");
require_once("./models/message.php");

// Review DAO

class capeanteDAO implements capeanteDAOInterface
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

    public function buildcapeante($data)
    {
        $capeante = new capeante();

        $capeante->id_capeante = $data["id_capeante"];
        $capeante->adm_capeante = $data["adm_capeante"];
        $capeante->adm_check = $data["adm_check"];
        $capeante->aud_enf_capeante = $data["aud_enf_capeante"];
        $capeante->aud_med_capeante = $data["aud_med_capeante"];
        $capeante->data_fech_capeante = $data["data_fech_capeante"];
        $capeante->data_final_capeante = $data["data_final_capeante"];
        $capeante->data_inicial_capeante = $data["data_inicial_capeante"];
        $capeante->diarias_capeante = $data["diarias_capeante"];
        $capeante->lote_cap = $data["lote_cap"];
        $capeante->glosa_diaria = $data["glosa_diaria"];
        $capeante->glosa_honorarios = $data["glosa_honorarios"];
        $capeante->glosa_matmed = $data["glosa_matmed"];
        $capeante->glosa_oxig = $data["glosa_oxig"];
        $capeante->glosa_sadt = $data["glosa_sadt"];
        $capeante->glosa_taxas = $data["glosa_taxas"];
        $capeante->glosa_opme = $data["glosa_opme"];
        $capeante->med_check = $data["med_check"];
        $capeante->enfer_check = $data["enfer_check"];
        $capeante->pacote = $data["pacote"];
        $capeante->parcial_capeante = $data["parcial_capeante"];
        $capeante->parcial_num = $data["parcial_num"];
        $capeante->fk_int_capeante = $data["fk_int_capeante"];
        $capeante->fk_user_cap = $data["fk_user_cap"];
        $capeante->valor_apresentado_capeante = $data["valor_apresentado_capeante"];
        $capeante->valor_diarias = $data["valor_diarias"];
        $capeante->valor_final_capeante = $data["valor_final_capeante"];
        $capeante->valor_glosa_enf = $data["valor_glosa_enf"];
        $capeante->valor_glosa_med = $data["valor_glosa_med"];
        $capeante->valor_glosa_total = $data["valor_glosa_total"];
        $capeante->valor_honorarios = $data["valor_honorarios"];
        $capeante->valor_matmed = $data["valor_matmed"];
        $capeante->valor_oxig = $data["valor_oxig"];
        $capeante->valor_sadt = $data["valor_sadt"];
        $capeante->valor_taxa = $data["valor_taxa"];
        $capeante->valor_opme = $data["valor_opme"];
        $capeante->desconto_valor_cap = $data["desconto_valor_cap"];
        $capeante->negociado_desconto_cap = $data["negociado_desconto_cap"];
        $capeante->em_auditoria_cap = $data["em_auditoria_cap"];
        $capeante->aberto_cap = $data["aberto_cap"];
        $capeante->encerrado_cap = $data["encerrado_cap"];
        $capeante->senha_finalizada = $data["senha_finalizada"];
        $capeante->conta_parada_cap = $data["conta_parada_cap"];
        $capeante->parada_motivo_cap = $data["parada_motivo_cap"];
        $capeante->fk_id_aud_enf = $data["fk_id_aud_enf"];
        $capeante->fk_id_aud_med = $data["fk_id_aud_med"];
        $capeante->fk_id_aud_adm = $data["fk_id_aud_adm"];
        $capeante->fk_id_aud_hosp = $data["fk_id_aud_hosp"];
        $capeante->impresso_cap = $data["impresso_cap"];

        return $capeante;
    }

    public function findAll()
    {
        $capeante = [];

        $stmt = $this->conn->prepare("SELECT * FROM tb_capeante
        ORDER BY id_capeante asc");

        $stmt->execute();

        $capeante = $stmt->fetchAll();
        return $capeante;
    }

    public function getcapeantesBynome_pac($nome_pac)
    {
        $capeantes = [];

        $stmt = $this->conn->prepare("SELECT * FROM tb_capeante
                                    WHERE nome_pac = :nome_pac
                                    ORDER BY id_capeante asc");

        $stmt->bindParam(":nome_pac", $nome_pac);
        $stmt->execute();
        $capeantesArray = $stmt->fetchAll();
        foreach ($capeantesArray as $capeante) {
            $capeantes[] = $this->buildcapeante($capeante);
        }
        return $capeantes;
    }

    public function findById($id_capeante)
    {
        $capeante = [];
        $stmt = $this->conn->prepare("SELECT * FROM tb_capeante
                                    WHERE id_capeante = :id_capeante");
        $stmt->bindParam(":id_capeante", $id_capeante);
        $stmt->execute();

        $data = $stmt->fetch();
        // var_dump($data);
        $capeante = $this->buildcapeante($data);

        return $capeante;
    }

    public function findByPac($pesquisa_nome, $limite, $inicio)
    {
        $capeante = [];

        $stmt = $this->conn->prepare("SELECT * FROM tb_capeante
                                    WHERE nome_pac LIKE :nome_pac order by nome_pac asc limite $inicio, $limite");

        $stmt->bindValue(":nome_pac", '%' . $pesquisa_nome . '%');

        $stmt->execute();

        $capeante = $stmt->fetchAll();
        return $capeante;
    }

    public function create(capeante $capeante)
    {
        $stmt = $this->conn->prepare("INSERT INTO tb_capeante (
        adm_capeante, 
        adm_check, 
        aud_enf_capeante, 
        aud_med_capeante, 
        data_fech_capeante, 
        data_final_capeante, 
        data_inicial_capeante, 
        diarias_capeante, 
        ca.lote_cap,
        glosa_diaria, 
        glosa_honorarios, 
        glosa_matmed, 
        glosa_oxig, 
        glosa_sadt, 
        glosa_taxas, 
        glosa_opme, 
        med_check,
        enfer_check,
        pacote,
        parcial_capeante,
        parcial_num,
        fk_int_capeante,
        fk_user_cap,
        valor_apresentado_capeante,
        valor_diarias,
        valor_final_capeante,
        valor_glosa_enf,
        valor_glosa_med,
        valor_glosa_total,
        valor_honorarios,
        valor_matmed,
        valor_oxig,
        valor_sadt,
        valor_opme,
        senha_finalizada,
        desconto_valor_cap,
        negociado_desconto_cap,
        em_auditoria_cap,
        aberto_cap,
        encerrado_cap,
        valor_taxa,
        usuario_create_cap,
        data_create_cap,
        conta_parada_cap,
        parada_motivo_cap,
        fk_id_aud_enf,
        fk_id_aud_med,
        fk_id_aud_adm,
        fk_id_aud_hosp
        
    ) VALUES (
        :adm_capeante, 
        :adm_check, 
        :aud_enf_capeante, 
        :aud_med_capeante, 
        :data_fech_capeante, 
        :data_final_capeante, 
        :data_inicial_capeante, 
        :diarias_capeante, 
        :lote_cap,
        :glosa_diaria, 
        :glosa_honorarios, 
        :glosa_matmed, 
        :glosa_oxig, 
        :glosa_sadt, 
        :glosa_taxas, 
        :glosa_opme, 
        :med_check,
        :enfer_check,
        :pacote,
        :parcial_capeante,
        :parcial_num,
        :fk_int_capeante,
        :fk_user_cap,
        :valor_apresentado_capeante,
        :valor_diarias,
        :valor_final_capeante,
        :valor_glosa_enf,
        :valor_glosa_med,
        :valor_glosa_total,
        :valor_honorarios,
        :valor_matmed,
        :valor_oxig,
        :valor_sadt,
        :valor_opme,
        :senha_finalizada,
        :desconto_valor_cap,
        :negociado_desconto_cap,
        :em_auditoria_cap,
        :aberto_cap,
        :encerrado_cap,
        :valor_taxa,
        :usuario_create_cap,
        :data_create_cap,
        :conta_parada_cap,
        :parada_motivo_cap,
        :fk_id_aud_enf,
        :fk_id_aud_med,
        :fk_id_aud_adm,
        :fk_id_aud_hosp
        
    )");

        $stmt->bindParam(":adm_capeante", $capeante->adm_capeante);
        $stmt->bindParam(":adm_check", $capeante->adm_check);
        $stmt->bindParam(":aud_enf_capeante", $capeante->aud_enf_capeante);
        $stmt->bindParam(":aud_med_capeante", $capeante->aud_med_capeante);
        $stmt->bindParam(":data_fech_capeante", $capeante->data_fech_capeante);
        $stmt->bindParam(":data_final_capeante", $capeante->data_final_capeante);
        $stmt->bindParam(":data_inicial_capeante", $capeante->data_inicial_capeante);
        $stmt->bindParam(":diarias_capeante", $capeante->diarias_capeante);
        $stmt->bindParam(":diarias_capeante", $capeante->diarias_capeante);
        $stmt->bindParam(":lote_cap", $capeante->lote_cap);
        $stmt->bindParam(":glosa_honorarios", $capeante->glosa_honorarios);
        $stmt->bindParam(":glosa_matmed", $capeante->glosa_matmed);
        $stmt->bindParam(":glosa_oxig", $capeante->glosa_oxig);
        $stmt->bindParam(":glosa_sadt", $capeante->glosa_sadt);
        $stmt->bindParam(":glosa_taxas", $capeante->glosa_taxas);
        $stmt->bindParam(":glosa_opme", $capeante->glosa_opme);
        $stmt->bindParam(":med_check", $capeante->med_check);
        $stmt->bindParam(":enfer_check", $capeante->enfer_check);
        $stmt->bindParam(":pacote", $capeante->pacote);
        $stmt->bindParam(":parcial_capeante", $capeante->parcial_capeante);
        $stmt->bindParam(":parcial_num", $capeante->parcial_num);
        $stmt->bindParam(":fk_int_capeante", $capeante->fk_int_capeante);
        $stmt->bindParam(":valor_apresentado_capeante", $capeante->valor_apresentado_capeante);
        $stmt->bindParam(":valor_diarias", $capeante->valor_diarias);
        $stmt->bindParam(":valor_final_capeante", $capeante->valor_final_capeante);
        $stmt->bindParam(":valor_glosa_enf", $capeante->valor_glosa_enf);
        $stmt->bindParam(":valor_glosa_med", $capeante->valor_glosa_med);
        $stmt->bindParam(":valor_glosa_total", $capeante->valor_glosa_total);
        $stmt->bindParam(":valor_honorarios", $capeante->valor_honorarios);
        $stmt->bindParam(":valor_matmed", $capeante->valor_matmed);
        $stmt->bindParam(":valor_opme", $capeante->valor_opme);
        $stmt->bindParam(":valor_oxig", $capeante->valor_oxig);
        $stmt->bindParam(":valor_sadt", $capeante->valor_sadt);
        $stmt->bindParam(":valor_taxa", $capeante->valor_taxa);
        $stmt->bindParam(":senha_finalizada", $capeante->senha_finalizada);
        $stmt->bindParam(":desconto_valor_cap", $capeante->desconto_valor_cap);
        $stmt->bindParam(":negociado_desconto_cap", $capeante->negociado_desconto_cap);
        $stmt->bindParam(":em_auditoria_cap", $capeante->em_auditoria_cap);
        $stmt->bindParam(":aberto_cap", $capeante->aberto_cap);
        $stmt->bindParam(":encerrado_cap", $capeante->encerrado_cap);
        $stmt->bindParam(":fk_user_cap", $capeante->fk_user_cap);
        $stmt->bindParam(":usuario_create_cap", $capeante->usuario_create_cap);
        $stmt->bindParam(":data_create_cap", $capeante->data_create_cap);
        $stmt->bindParam(":conta_parada_cap", $capeante->conta_parada_cap); // Remover a vírgula extra aqui
        $stmt->bindParam(":parada_motivo_cap", $capeante->parada_motivo_cap);
        $stmt->bindParam(":fk_id_aud_enf", $capeante->fk_id_aud_enf);
        $stmt->bindParam(":fk_id_aud_med", $capeante->fk_id_aud_med);
        $stmt->bindParam(":fk_id_aud_adm", $capeante->fk_id_aud_adm);
        $stmt->bindParam(":fk_id_aud_hosp", $capeante->fk_id_aud_hosp);

        $stmt->execute();

        $this->message->setMessage("capeante adicionado com sucesso!", "success", "list_internacao_cap.php");
    }


    public function update(capeante $capeante)
    {
        try {
            $sql = "UPDATE tb_capeante SET
                adm_capeante = :adm_capeante, 
                adm_check = :adm_check, 
                aud_enf_capeante = :aud_enf_capeante, 
                aud_med_capeante = :aud_med_capeante, 
                data_fech_capeante = :data_fech_capeante, 
                data_final_capeante = :data_final_capeante, 
                data_inicial_capeante = :data_inicial_capeante, 
                diarias_capeante = :diarias_capeante, 
                glosa_diaria = :glosa_diaria, 
                lote_cap = :lote_cap,
                glosa_honorarios = :glosa_honorarios, 
                glosa_matmed = :glosa_matmed, 
                glosa_oxig = :glosa_oxig, 
                glosa_sadt = :glosa_sadt, 
                glosa_taxas = :glosa_taxas, 
                glosa_opme = :glosa_opme, 
                med_check = :med_check,
                enfer_check = :enfer_check,
                pacote = :pacote,
                parcial_capeante = :parcial_capeante,
                parcial_num = :parcial_num,
                fk_int_capeante = :fk_int_capeante,
                fk_user_cap = :fk_user_cap,
                valor_apresentado_capeante = :valor_apresentado_capeante,
                valor_diarias = :valor_diarias,
                valor_final_capeante = :valor_final_capeante,
                valor_glosa_enf = :valor_glosa_enf,
                valor_glosa_med = :valor_glosa_med,
                valor_glosa_total = :valor_glosa_total,
                valor_honorarios = :valor_honorarios,
                valor_matmed = :valor_matmed,
                valor_oxig = :valor_oxig,
                valor_sadt = :valor_sadt,
                valor_taxa = :valor_taxa,
                valor_opme = :valor_opme,
                senha_finalizada = :senha_finalizada,
                desconto_valor_cap = :desconto_valor_cap,
                negociado_desconto_cap = :negociado_desconto_cap,
                em_auditoria_cap = :em_auditoria_cap,
                aberto_cap = :aberto_cap,
                encerrado_cap = :encerrado_cap,
                usuario_create_cap= :usuario_create_cap,
                data_create_cap= :data_create_cap,
                conta_parada_cap= :conta_parada_cap,
                parada_motivo_cap= :parada_motivo_cap,
                impresso_cap= :impresso_cap,
                fk_id_aud_enf= :fk_id_aud_enf,
                fk_id_aud_med= :fk_id_aud_med,
                fk_id_aud_adm= :fk_id_aud_adm,
                fk_id_aud_hosp= :fk_id_aud_hosp,
                validacao_cap = :validacao_cap
                WHERE id_capeante = :id_capeante 
            ";
            $stmt = $this->conn->prepare("UPDATE tb_capeante SET
                adm_capeante = :adm_capeante, 
                adm_check = :adm_check, 
                aud_enf_capeante = :aud_enf_capeante, 
                aud_med_capeante = :aud_med_capeante, 
                data_fech_capeante = :data_fech_capeante, 
                data_final_capeante = :data_final_capeante, 
                data_inicial_capeante = :data_inicial_capeante, 
                diarias_capeante = :diarias_capeante, 
                glosa_diaria = :glosa_diaria, 
                lote_cap = :lote_cap, 
                glosa_honorarios = :glosa_honorarios, 
                glosa_matmed = :glosa_matmed, 
                glosa_oxig = :glosa_oxig, 
                glosa_sadt = :glosa_sadt, 
                glosa_taxas = :glosa_taxas, 
                glosa_opme = :glosa_opme, 
                med_check = :med_check,
                enfer_check = :enfer_check,
                pacote = :pacote,
                parcial_capeante = :parcial_capeante,
                parcial_num = :parcial_num,
                fk_int_capeante = :fk_int_capeante,
                fk_user_cap = :fk_user_cap,
                valor_apresentado_capeante = :valor_apresentado_capeante,
                valor_diarias = :valor_diarias,
                valor_final_capeante = :valor_final_capeante,
                valor_glosa_enf = :valor_glosa_enf,
                valor_glosa_med = :valor_glosa_med,
                valor_glosa_total = :valor_glosa_total,
                valor_honorarios = :valor_honorarios,
                valor_matmed = :valor_matmed,
                valor_oxig = :valor_oxig,
                valor_sadt = :valor_sadt,
                valor_taxa = :valor_taxa,
                valor_opme = :valor_opme,
                senha_finalizada = :senha_finalizada,
                desconto_valor_cap = :desconto_valor_cap,
                negociado_desconto_cap = :negociado_desconto_cap,
                em_auditoria_cap = :em_auditoria_cap,
                aberto_cap = :aberto_cap,
                encerrado_cap = :encerrado_cap,
                usuario_create_cap= :usuario_create_cap,
                data_create_cap= :data_create_cap,
                conta_parada_cap= :conta_parada_cap,
                parada_motivo_cap= :parada_motivo_cap,
                impresso_cap= :impresso_cap,
                fk_id_aud_enf= :fk_id_aud_enf,
                fk_id_aud_med= :fk_id_aud_med,
                fk_id_aud_adm= :fk_id_aud_adm,
                fk_id_aud_hosp= :fk_id_aud_hosp,
                validacao_cap = :validacao_cap
                WHERE id_capeante = :id_capeante 
            ");

            $stmt->bindParam(":adm_capeante", $capeante->adm_capeante);
            $stmt->bindParam(":adm_check", $capeante->adm_check);
            $stmt->bindParam(":med_check", $capeante->med_check);
            $stmt->bindParam(":enfer_check", $capeante->enfer_check);
            $stmt->bindParam(":aud_enf_capeante", $capeante->aud_enf_capeante);
            $stmt->bindParam(":aud_med_capeante", $capeante->aud_med_capeante);

            $stmt->bindParam(":data_fech_capeante", $capeante->data_fech_capeante);
            $stmt->bindParam(":data_final_capeante", $capeante->data_final_capeante);
            $stmt->bindParam(":data_inicial_capeante", $capeante->data_inicial_capeante);
            $stmt->bindParam(":diarias_capeante", $capeante->diarias_capeante);
            $stmt->bindParam(":diarias_capeante", $capeante->diarias_capeante);
            $stmt->bindParam(":lote_cap", $capeante->lote_cap);

            $stmt->bindParam(":glosa_diaria", $capeante->glosa_diaria);
            $stmt->bindParam(":glosa_honorarios", $capeante->glosa_honorarios);
            $stmt->bindParam(":glosa_matmed", $capeante->glosa_matmed);
            $stmt->bindParam(":glosa_oxig", $capeante->glosa_oxig);
            $stmt->bindParam(":glosa_sadt", $capeante->glosa_sadt);
            $stmt->bindParam(":glosa_taxas", $capeante->glosa_taxas);
            $stmt->bindParam(":glosa_opme", $capeante->glosa_opme);

            $stmt->bindParam(":pacote", $capeante->pacote);
            $stmt->bindParam(":parcial_capeante", $capeante->parcial_capeante);
            $stmt->bindParam(":parcial_num", $capeante->parcial_num);
            $stmt->bindParam(":fk_int_capeante", $capeante->fk_int_capeante);
            $stmt->bindParam(":fk_user_cap", $capeante->fk_user_cap);

            $stmt->bindParam(":valor_apresentado_capeante", $capeante->valor_apresentado_capeante);
            $stmt->bindParam(":valor_diarias", $capeante->valor_diarias);
            $stmt->bindParam(":valor_honorarios", $capeante->valor_honorarios);
            $stmt->bindParam(":valor_matmed", $capeante->valor_matmed);
            $stmt->bindParam(":valor_taxa", $capeante->valor_taxa);
            $stmt->bindParam(":valor_oxig", $capeante->valor_oxig);
            $stmt->bindParam(":valor_sadt", $capeante->valor_sadt);
            $stmt->bindParam(":valor_opme", $capeante->valor_opme);

            $stmt->bindParam(":valor_glosa_enf", $capeante->valor_glosa_enf);
            $stmt->bindParam(":valor_glosa_med", $capeante->valor_glosa_med);
            $stmt->bindParam(":valor_glosa_total", $capeante->valor_glosa_total);

            $stmt->bindParam(":valor_final_capeante", $capeante->valor_final_capeante);
            $stmt->bindParam(":senha_finalizada", $capeante->senha_finalizada);
            $stmt->bindParam(":negociado_desconto_cap", $capeante->negociado_desconto_cap);
            $stmt->bindParam(":desconto_valor_cap", $capeante->desconto_valor_cap);
            $stmt->bindParam(":em_auditoria_cap", $capeante->em_auditoria_cap);
            $stmt->bindParam(":aberto_cap", $capeante->aberto_cap);
            $stmt->bindParam(":encerrado_cap", $capeante->encerrado_cap);

            $stmt->bindParam(":conta_parada_cap", $capeante->conta_parada_cap);
            $stmt->bindParam(":parada_motivo_cap", $capeante->parada_motivo_cap);

            $stmt->bindParam(":usuario_create_cap", $capeante->usuario_create_cap);
            $stmt->bindParam(":data_create_cap", $capeante->data_create_cap);

            $stmt->bindParam(":id_capeante", $capeante->id_capeante);
            $stmt->bindParam(":impresso_cap", $capeante->impresso_cap);
            // fk_id_aud_enf (pode ser null ou inteiro)
            $stmt->bindValue(
                ":fk_id_aud_enf",
                $capeante->fk_id_aud_enf === "" ? null : $capeante->fk_id_aud_enf,
                $capeante->fk_id_aud_enf === "" ? PDO::PARAM_NULL : PDO::PARAM_INT
            );

            // fk_id_aud_med (assumindo que sempre vem preenchido como inteiro)
            $stmt->bindValue(
                ":fk_id_aud_med",
                $capeante->fk_id_aud_med,
                PDO::PARAM_INT
            );

            // fk_id_aud_adm (pode ser null ou inteiro)
            $stmt->bindValue(
                ":fk_id_aud_adm",
                $capeante->fk_id_aud_adm === "" ? null : $capeante->fk_id_aud_adm,
                $capeante->fk_id_aud_adm === "" ? PDO::PARAM_NULL : PDO::PARAM_INT
            );


            $stmt->bindParam(":fk_id_aud_hosp", $capeante->fk_id_aud_hosp);
            $stmt->bindParam(":validacao_cap", $capeante->validacao_cap);

            $params = [
                ":adm_capeante" => $capeante->adm_capeante,
                ":adm_check" => $capeante->adm_check,
                ":med_check" => $capeante->med_check,
                ":enfer_check" => $capeante->enfer_check,
                ":aud_enf_capeante" => $capeante->aud_enf_capeante,
                ":aud_med_capeante" => $capeante->aud_med_capeante,

                ":data_fech_capeante" => $capeante->data_fech_capeante,
                ":data_final_capeante" => $capeante->data_final_capeante,
                ":data_inicial_capeante" => $capeante->data_inicial_capeante,
                ":diarias_capeante" => $capeante->diarias_capeante,
                ":lote_cap" => $capeante->lote_cap,

                ":glosa_diaria" => $capeante->glosa_diaria,
                ":glosa_honorarios" => $capeante->glosa_honorarios,
                ":glosa_matmed" => $capeante->glosa_matmed,
                ":glosa_oxig" => $capeante->glosa_oxig,
                ":glosa_sadt" => $capeante->glosa_sadt,
                ":glosa_taxas" => $capeante->glosa_taxas,
                ":glosa_opme" => $capeante->glosa_opme,

                ":pacote" => $capeante->pacote,
                ":parcial_capeante" => $capeante->parcial_capeante,
                ":parcial_num" => $capeante->parcial_num,
                ":fk_int_capeante" => $capeante->fk_int_capeante,
                ":fk_user_cap" => $capeante->fk_user_cap,

                ":valor_apresentado_capeante" => $capeante->valor_apresentado_capeante,
                ":valor_diarias" => $capeante->valor_diarias,
                ":valor_honorarios" => $capeante->valor_honorarios,
                ":valor_matmed" => $capeante->valor_matmed,
                ":valor_taxa" => $capeante->valor_taxa,
                ":valor_oxig" => $capeante->valor_oxig,
                ":valor_sadt" => $capeante->valor_sadt,
                ":valor_opme" => $capeante->valor_opme,

                ":valor_glosa_enf" => $capeante->valor_glosa_enf,
                ":valor_glosa_med" => $capeante->valor_glosa_med,
                ":valor_glosa_total" => $capeante->valor_glosa_total,

                ":valor_final_capeante" => $capeante->valor_final_capeante,
                ":senha_finalizada" => $capeante->senha_finalizada,
                ":negociado_desconto_cap" => $capeante->negociado_desconto_cap,
                ":desconto_valor_cap" => $capeante->desconto_valor_cap,
                ":em_auditoria_cap" => $capeante->em_auditoria_cap,
                ":aberto_cap" => $capeante->aberto_cap,
                ":encerrado_cap" => $capeante->encerrado_cap,

                ":conta_parada_cap" => $capeante->conta_parada_cap,
                ":parada_motivo_cap" => $capeante->parada_motivo_cap,

                ":usuario_create_cap" => $capeante->usuario_create_cap,
                ":data_create_cap" => $capeante->data_create_cap,

                ":id_capeante" => $capeante->id_capeante,
                ":impresso_cap" => $capeante->impresso_cap,
                ":fk_id_aud_enf" => $capeante->fk_id_aud_enf,
                ":fk_id_aud_med" => $capeante->fk_id_aud_med,
                ":fk_id_aud_adm" => $capeante->fk_id_aud_adm,
                ":fk_id_aud_hosp" => $capeante->fk_id_aud_hosp,
                ":validacao_cap" => $capeante->validacao_cap
            ];
            $finalQuery = $sql;
            foreach ($params as $key => $value) {
                $finalQuery = str_replace($key, is_null($value) ? "NULL" : "'$value'", $finalQuery);
            }
            // print_r("Executing Query: " . $finalQuery);
            // exit();

            $stmt->execute();

            // Mensagem de sucesso por editar capeante
            $this->message->setMessage("Capeante atualizado com sucesso!", "success", "list_internacao_cap_audit.php");
        } catch (PDOException $e) {
            print_r($e->getMessage());
        }
    }

    public function destroy($id_capeante)
    {
        $stmt = $this->conn->prepare("DELETE FROM tb_capeante WHERE id_capeante = $id_capeante");

        // $stmt->bindParam(":id_capeante", $id_capeante);

        $stmt->execute();

        // Mensagem de sucesso por remover filme
        $this->message->setMessage("capeante removido com sucesso!", "success", "list_capeante.php");
    }


    public function findGeral()
    {

        $capeantes = [];

        $stmt = $this->conn->query("SELECT * FROM tb_capeante ORDER BY id_capeante");

        $stmt->execute();

        $capeantes = $stmt->fetchAll();

        return $capeantes;
    }

    // metodo de pesquisa geral 

    public function selectAllcapeante($where = null, $order = null, $limite = null)
    {
        //DADOS DA QUERY
        $where = strlen($where) ? 'WHERE ' . $where : '';
        $order = strlen($order) ? 'ORDER BY ' . $order : '';
        $limite = strlen($limite) ? 'LIMIT ' . $limite : '';
        $group = " GROUP BY id_internacao ";

        //MONTA A QUERY
        $query = $this->conn->query('SELECT ac.id_internacao, 
        ac.acoes_int, 
        ac.data_intern_int, 
        ac.data_visita_int, 
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
        ac.senha_int,
        pa.id_paciente,
        pa.nome_pac,
        ho.id_hospital, 
        ho.nome_hosp,
        ho.email01_hosp,
        ca.id_capeante,
        ca.fk_int_capeante,
        ca.valor_apresentado_capeante,
        ca.valor_final_capeante,
        ca.data_inicial_capeante,
        ca.data_final_capeante,
        ca.lote_cap,
        ca.adm_check,
        ca.adm_check,
        ca.med_check,
        ca.enfer_check,
        ca.parcial_num,
        ca.parcial_capeante,
        ca.glosa_diaria,
        ca.glosa_honorarios,
        ca.glosa_matmed,
        ca.glosa_oxig,
        ca.glosa_sadt,
        ca.glosa_taxas,
        ca.glosa_opme,
        ca.pacote,
        ca.valor_diarias,
        ca.valor_final_capeante,
        ca.valor_glosa_enf,
        ca.valor_glosa_med,
        ca.valor_honorarios,
        ca.valor_matmed,
        ca.valor_glosa_total,
        ca.valor_oxig,
        ca.valor_sadt,
        ca.valor_taxa,
        ca.valor_opme,
        ca.senha_finalizada,
        ca.negociado_desconto_cap,
        ca.desconto_valor_cap,
        ca.encerrado_cap,
        ca.aberto_cap,
        ca.em_auditoria_cap,
        ca.conta_parada_cap,
        ca.parada_motivo_cap,
        ca.fk_id_aud_med,
        ca.fk_id_aud_enf,
        ca.fk_id_aud_adm,
        ca.fk_id_aud_hosp,
        us_med.usuario_user as nome_med,
        us_enf.usuario_user as nome_enf,
        us_adm.usuario_user as nome_adm,
        us_hosp.usuario_user as nome_aud_hosp
    
        FROM tb_internacao ac
    
			left JOIN tb_capeante as ca On  
            ca.fk_int_capeante = ac.id_internacao
    
            left JOIN tb_hospital as ho On  
            ac.fk_hospital_int = ho.id_hospital
    
            left join tb_paciente as pa on
            ac.fk_paciente_int = pa.id_paciente  

            left join tb_user as us_med on
            ca.fk_id_aud_med = us_med.id_usuario 

            left join tb_user as us_enf on
            ca.fk_id_aud_enf = us_enf.id_usuario 
            
            left join tb_user as us_adm on
            ca.fk_id_aud_adm = us_adm.id_usuario 
            
            left join tb_user as us_hosp on
            ca.fk_id_aud_hosp = us_hosp.id_usuario 

            ' . $where . '' . $group . '' . $order . ' ' . $limite);

        $query->execute();

        $capeante = $query->fetchAll();

        return $capeante;
    }


    // ********* \\ ********
    // ********* MODELO DE FILTRO COM SELECT ATUAL COM FILTROS E PAGINACAO CAPEANTE ********
    // ********* USAR PARA PREENCHAR CAMPOS DO CAPEANTE PARA EDITAR\\ ********
    public function selectInternacaoCap($where = null, $order = null, $limit = null)
    {
        //DADOS DA QUERY
        $where = strlen($where) ? 'WHERE ' . $where : '';
        $order = strlen($order) ? 'ORDER BY ' . $order : '';
        $limit = strlen($limit) ? 'LIMIT ' . $limit : '';

        //MONTA A QUERY
        $query = $this->conn->query('SELECT 
    ac.id_internacao, 
    ac.data_intern_int, 
    ac.data_visita_int, 
    ac.fk_hospital_int,    
    ac.internado_int,
    ac.visita_no_int,
    ac.primeira_vis_int,
    pa.id_paciente,
    pa.nome_pac,
    ho.id_hospital, 
    ho.nome_hosp, 
    ca.id_capeante,
    ca.fk_int_capeante,
    ca.valor_apresentado_capeante,
    ca.valor_final_capeante,
    ca.data_inicial_capeante,
    ca.data_final_capeante,
    ca.lote_cap,
    ca.adm_check,
    ca.adm_check,
    ca.med_check,
    ca.enfer_check,
    ca.parcial_num,
    ca.parcial_capeante,
    ca.glosa_diaria ,
    ca.glosa_honorarios,
    ca.glosa_matmed,
    ca.glosa_oxig,
    ca.glosa_sadt,
    ca.glosa_taxas,
    ca.glosa_opme,
    ca.pacote,
    ca.valor_diarias,
    ca.valor_final_capeante,
    ca.valor_glosa_enf,
    ca.valor_glosa_med,
    ca.valor_honorarios,
    ca.valor_matmed,
    ca.valor_glosa_total,
    ca.valor_oxig,
    ca.valor_sadt,
    ca.valor_taxa,
    ca.valor_opme,
    ca.senha_finalizada,
    ca.negociado_desconto_cap,
    ca.desconto_valor_cap,
    ca.em_auditoria_cap,
    ca.aberto_cap,
    ca.encerrado_cap,
    ca.conta_parada_cap,
    ca.parada_motivo


    FROM tb_internacao ac 

        left JOIN tb_hospital as ho On  
        ac.fk_hospital_int = ho.id_hospital

        left join tb_paciente as pa on
        ac.fk_paciente_int = pa.id_paciente 

        left join tb_capeante as ca on
        ac.id_internacao = ca.fk_int_capeante 
        
        ' . $where . ' ' . $order . ' ' . $limit);

        $query->execute();

        $hospital = $query->fetchAll();

        return $hospital;
    }

    public function Qtdcapeante($where = null, $order = null, $limite = null)
    {
        $capeante = [];
        $internacao = [];
        //DADOS DA QUERY
        $where = strlen($where) ? 'WHERE ' . $where : '';
        $order = strlen($order) ? 'ORDER BY ' . $order : '';
        $limite = strlen($limite) ? 'LIMIT ' . $limite : '';
        $group = ' GROUP BY id_internacao ';

        $stmt = $this->conn->query('SELECT 
        ac.id_internacao, 
        ac.acoes_int, 
        ac.data_intern_int, 
        ac.data_visita_int, 
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
        ho.nome_hosp,
        ca.id_capeante,
        ca.fk_int_capeante,
        ca.valor_apresentado_capeante,
        ca.valor_final_capeante,
        ca.data_inicial_capeante,
        ca.data_final_capeante,
        ca.lote_cap,
        ca.adm_check,
        ca.adm_check,
        ca.med_check,
        ca.enfer_check,
        ca.parcial_num,
        ca.parcial_capeante,
        ca.glosa_diaria ,
        ca.glosa_honorarios,
        ca.glosa_matmed,
        ca.glosa_oxig,
        ca.glosa_sadt,
        ca.glosa_taxas,
        ca.glosa_opme,
        ca.pacote,
        ca.valor_diarias,
        ca.valor_final_capeante,
        ca.valor_glosa_enf,
        ca.valor_glosa_med,
        ca.valor_honorarios,
        ca.valor_matmed,
        ca.valor_glosa_total,
        ca.valor_oxig,
        ca.valor_sadt,
        ca.valor_taxa,
        ca.valor_opme,
        ca.senha_finalizada,
        ca.negociado_desconto_cap,
        ca.desconto_valor_cap,
        ca.em_auditoria_cap,
        ca.aberto_cap,
        ca.encerrado_cap,
        ca.conta_parada_cap,
        ca.parada_motivo

        COUNT(id_internacao) as qtd 
        
        FROM tb_internacao ac

        left JOIN tb_capeante as ca On  
        ca.fk_int_capeante = ac.id_internacao
    
        left JOIN tb_hospital as ho On  
        ac.fk_hospital_int = ho.id_hospital
    
        left join tb_paciente as pa on
        ac.fk_paciente_int = pa.id_paciente ' . $where . '' . $group . '' . $order . ' ' . $limite);

        $stmt->execute();

        $QtdTotalPac = $stmt->fetch();

        return $QtdTotalPac;
    }

    public function getCapeanteByInternacao($id_internacao)
    {
        $stmt = $this->conn->query('SELECT 

        COUNT(fk_int_capeante) as qtd 

        FROM tb_capeante 
        
        WHERE fk_int_capeante = ' . $id_internacao);

        $stmt->execute();

        $QtdTotalPac = $stmt->fetch();

        return $QtdTotalPac;
    }

    public function getLastCapeanteByInternacao($id_internacao)
    {
        $stmt = $this->conn->query(
            'SELECT 

        data_final_capeante

        FROM tb_capeante 
        
        WHERE id_capeante = (SELECT MAX(id_capeante) FROM tb_capeante WHERE fk_int_capeante = ' . $id_internacao . ')'

        );

        $stmt->execute();

        $QtdTotalPac = $stmt->fetch();

        return $QtdTotalPac;
    }

    public function getLastCapeanteIdByInternacao($id_internacao)
    {
        $stmt = $this->conn->query(
            'SELECT 

        id_capeante

        FROM tb_capeante 
        
        WHERE id_capeante = (SELECT MAX(id_capeante) FROM tb_capeante WHERE fk_int_capeante = ' . $id_internacao . ')'

        );

        $stmt->execute();

        $QtdTotalPac = $stmt->fetch();

        return $QtdTotalPac;
    }

    public function findMaxCapeante()
    {

        $capeante = [];

        $stmt = $this->conn->query("SELECT coalesce(max(id_internacao),1) as ultimoReg from tb_internacao");

        $stmt->execute();

        $CapIdMax = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $CapIdMax;
    }
}