<?php

class indicadoresDAO
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

    public function getUtiPerc($where)
    {

        $perc_uti = null;

        $stmt = $this->conn->prepare("SELECT 
            concat(truncate((sum(case when internado_int = 's' and uti.fk_internacao_uti is not null then 1 else 0 end)/sum(case when internado_int = 's' and uti.fk_internacao_uti is null then 1 else 0 end) ) * 100,2),'%') as perc 
            FROM tb_internacao i 
            left join tb_uti uti on i.id_internacao = uti.fk_internacao_uti 
            " . ($where? "WHERE $where" : ''));

        $stmt->execute();

        $perc_uti = $stmt->fetch();

        if ($perc_uti) {
            return $perc_uti;
        }
    }

    public function getDrgAcima($where)
    {
        $drg = null;
        $stmt = $this->conn->prepare("SELECT count(*) 
            FROM tb_internacao i 
            JOIN tb_patologia p 
            ON i.fk_patologia_int = p.id_patologia 
            WHERE i.internado_int = 's' and  p.dias_pato > 1 and p.dias_pato < (datediff(current_date,i.data_intern_int))" . ($where? "AND $where" : ''));

        $stmt->execute();

        $drg = $stmt->fetch();

        if ($drg) {
            return $drg;
        }
    }

    public function getLongaPermanencia($where)
    {
        $longa = null;

        $stmt = $this->conn->prepare("SELECT
                    i.id_internacao, p.nome_pac, i.data_intern_int, hos.nome_hosp
                    FROM tb_internacao i JOIN 
                    tb_paciente p on i.fk_paciente_int = p.id_paciente
                    JOIN tb_hospital hos ON i.fk_hospital_int = hos.id_hospital
                    JOIN tb_seguradora s ON p.fk_seguradora_pac = s.id_seguradora
                    where " . $where);
        $stmt->execute();

        $longa = $stmt->fetchAll();

        if ($longa) {
            return $longa;
        }
    }

    public function getContasParadas($where)
    {
        $contas_paradas = null;
        $stmt = $this->conn->prepare("SELECT
                    count(*)
                    FROM tb_capeante c JOIN 
                    tb_internacao i ON c.fk_int_capeante = i.id_internacao
                    WHERE " . $where);

        $stmt->execute();

        $contas_paradas = $stmt->fetch();

        if ($contas_paradas) {
            return $contas_paradas;
        }
    }

    public function getUtiPertinente($where)
    {
        $contas_paradas = null;
        $stmt = $this->conn->prepare("SELECT
                    count(*)
                    FROM tb_internacao i 
                    JOIN tb_uti u 
                    ON u.fk_internacao_uti = i.id_internacao
                    WHERE i.internado_int = 's' 
                    and u.just_uti = 'Não pertinente'
                    " . ($where? "AND $where" : ''));


        $stmt->execute();

        $contas_paradas = $stmt->fetch();

        if ($contas_paradas) {
            return $contas_paradas;
        }
    }

    public function getScoreBaixo($where)
    {
        $contas_paradas = null;

        $stmt = $this->conn->prepare("SELECT
                    count(*)
                    FROM tb_internacao i 
                    JOIN tb_uti u 
                    ON u.fk_internacao_uti = i.id_internacao
                    WHERE i.internado_int = 's' and u.score_uti < 0 and u.score_uti is not null
                    " . ($where? "AND $where" : ''));


        $stmt->execute();

        $contas_paradas = $stmt->fetch();

        if ($contas_paradas) {
            return $contas_paradas;
        }
    }
}