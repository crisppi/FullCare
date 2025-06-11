<?php

require_once("./models/detalhes.php");
require_once("./models/message.php");

// Review DAO
require_once("dao/detalhesDao.php");

class detalhesDAO implements detalhesDAOInterface
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

    public function builddetalhes($data)
    {
        $detalhes = new detalhes();

        $detalhes->fk_vis_det = $data["fk_vis_det"];
        $detalhes->fk_int_det = $data["fk_int_det"];

        $detalhes->curativo_det = $data["curativo_det"];
        $detalhes->dieta_det = $data["dieta_det"];
        $detalhes->nivel_consc_det = $data["nivel_consc_det"];
        $detalhes->oxig_det = $data["oxig_det"];
        $detalhes->oxig_uso_det = $data["oxig_uso_det"];
        $detalhes->qt_det = $data["qt_det"];
        $detalhes->dispositivo_det = $data["dispositivo_det"];
        $detalhes->atb_det = $data["atb_det"];
        $detalhes->atb_uso_det = $data["atb_uso_det"];
        $detalhes->acamado_det = $data["acamado_det"];
        $detalhes->exames_det = $data["exames_det"];
        $detalhes->oportunidades_det = $data["oportunidades_det"];
        $detalhes->hemoderivados_det = $data["hemoderivados_det"];
        $detalhes->oxigenio_hiperbarica_det = $data["oxigenio_hiperbarica_det"];
        $detalhes->dialise_det = $data["dialise_det"];

        $detalhes->tqt_det = $data["tqt_det"];
        $detalhes->svd_det = $data["svd_det"];
        $detalhes->gtt_det = $data["gtt_det"];
        $detalhes->dreno_det = $data["dreno_det"];
        $detalhes->rt_det = $data["rt_det"];
        $detalhes->lesoes_pele_det = $data["lesoes_pele_det"];
        $detalhes->medic_alto_custo_det = $data["medic_alto_custo_det"];
        $detalhes->qual_medicamento_det = $data["qual_medicamento_det"];

        $detalhes->paliativos_det = $data["paliativos_det"];
        $detalhes->braden_det = $data["braden_det"];
        $detalhes->liminar_det = $data["liminar_det"];
        $detalhes->parto_det = $data["parto_det"];

        return $detalhes;
    }

    public function create(detalhes $detalhes)
    {

        $stmt = $this->conn->prepare("INSERT INTO tb_detalhes (
        fk_int_det,
        fk_vis_det,
        curativo_det,
        dieta_det,
        nivel_consc_det,
        oxig_det,
        oxig_uso_det,
        qt_det,
        dispositivo_det,
        atb_det,
        atb_uso_det,
        acamado_det,
        exames_det,
        oportunidades_det,
        tqt_det,
        svd_det,
        gtt_det,
        dreno_det,
        rt_det,
        lesoes_pele_det,
        medic_alto_custo_det,
        qual_medicamento_det,
        paliativos_det,
        braden_det,
        liminar_det,
        parto_det,
        hemoderivados_det,
        dialise_det,
        oxigenio_hiperbarica_det
  
      ) VALUES (
        :fk_int_det,
        :fk_vis_det,
        :curativo_det,
        :dieta_det,
        :nivel_consc_det,
        :oxig_det,
        :oxig_uso_det,
        :qt_det,
        :dispositivo_det,
        :atb_det,
        :atb_uso_det,
        :acamado_det,
        :exames_det,
        :oportunidades_det,
        :tqt_det,
        :svd_det,
        :gtt_det,
        :dreno_det,
        :rt_det,
        :lesoes_pele_det,
        :medic_alto_custo_det,
        :qual_medicamento_det,
        :paliativos_det,
        :braden_det,
        :liminar_det,
        :parto_det,
        :hemoderivados_det,
        :dialise_det,
        :oxigenio_hiperbarica_det
  
     )");

        $stmt->bindParam(":fk_int_det", $detalhes->fk_int_det);
        $stmt->bindParam(":fk_vis_det", $detalhes->fk_vis_det);
        $stmt->bindParam(":curativo_det", $detalhes->curativo_det);
        $stmt->bindParam(":dieta_det", $detalhes->dieta_det);
        $stmt->bindParam(":nivel_consc_det", $detalhes->nivel_consc_det);
        $stmt->bindParam(":oxig_det", $detalhes->oxig_det);
        $stmt->bindParam(":oxig_uso_det", $detalhes->oxig_uso_det);
        $stmt->bindParam(":qt_det", $detalhes->qt_det);
        $stmt->bindParam(":dispositivo_det", $detalhes->dispositivo_det);
        $stmt->bindParam(":atb_det", $detalhes->atb_det);
        $stmt->bindParam(":atb_uso_det", $detalhes->atb_uso_det);
        $stmt->bindParam(":acamado_det", $detalhes->acamado_det);
        $stmt->bindParam(":exames_det", $detalhes->exames_det, PDO::PARAM_STR);
        $stmt->bindParam(":oportunidades_det", $detalhes->oportunidades_det, PDO::PARAM_STR);
        $stmt->bindParam(":tqt_det", $detalhes->tqt_det);
        $stmt->bindParam(":svd_det", $detalhes->svd_det);
        $stmt->bindParam(":gtt_det", $detalhes->gtt_det);
        $stmt->bindParam(":dreno_det", $detalhes->dreno_det);
        $stmt->bindParam(":rt_det", $detalhes->rt_det);
        $stmt->bindParam(":lesoes_pele_det", $detalhes->lesoes_pele_det);
        $stmt->bindParam(":medic_alto_custo_det", $detalhes->medic_alto_custo_det);
        $stmt->bindParam(":qual_medicamento_det", $detalhes->qual_medicamento_det, PDO::PARAM_STR);
        $stmt->bindParam(":paliativos_det", $detalhes->paliativos_det);
        $stmt->bindParam(":braden_det", $detalhes->braden_det);
        $stmt->bindParam(":liminar_det", $detalhes->liminar_det);
        $stmt->bindParam(":parto_det", $detalhes->parto_det);
        $stmt->bindParam(":oxigenio_hiperbarica_det", $detalhes->oxigenio_hiperbarica_det);
        $stmt->bindParam(":hemoderivados_det", $detalhes->hemoderivados_det);
        $stmt->bindParam(":dialise_det", $detalhes->dialise_det);

        $stmt->execute();

        // Mensagem de sucesso por adicionar filme
        $this->message->setMessage("detalhes adicionado com sucesso!", "success", "list_detalhes.php");
    }


    public function findById($id_internacao)
    {
        $detalhes = [];
        $stmt = $this->conn->prepare("SELECT * FROM tb_detalhes
                                    WHERE fk_int_det = :id_internacao");
        $stmt->bindParam(":id_internacao", $id_internacao);
        $stmt->execute();

        $data = $stmt->fetch();


        // var_dump($data);
        if ($data != null) {
            $detalhes = $this->builddetalhes($data);
        }


        return $detalhes;
    }
}
