<?php

final class LongStayPredictiveService
{
    private PDO $conn;
    private const DEFAULT_THRESHOLD = 30;
    private const MIN_COHORT = 12;
    private const PRIOR_STRENGTH = 18.0;
    private const MODEL_VERSION = 'coorte-condicional-v1';

    public function __construct(PDO $conn) { $this->conn = $conn; }

    public function scoreActiveInternacoes(array $internacaoIds): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $internacaoIds))));
        if (!$ids) return [];
        $active = $this->fetchActive($ids);
        $history = $this->fetchHistory();
        if (!$active || count($history) < 100) return [];
        $scores = [];
        foreach ($active as $case) {
            $score = $this->scoreCase($case, $history);
            if ($score !== null) $scores[(int)$case['id_internacao']] = $score;
        }
        return $scores;
    }

    public function validateHistorical(int $simulationDay = 5): array
    {
        $history = $this->fetchHistory();
        usort($history, static fn($a, $b) => strcmp((string)$a['data_alta_alt'], (string)$b['data_alta_alt']));
        if (count($history) < 300) return ['available'=>false,'message'=>'Histórico insuficiente.'];
        $cut = (int)floor(count($history) * .80);
        $training = array_slice($history, 0, $cut);
        $testing = array_slice($history, $cut);
        $predictions = [];
        $baselinePredictions = [];
        $labels = [];
        $nbModel = $this->trainNaiveBayes($training);
        $nbPredictions = [];
        foreach ($testing as $case) {
            $threshold = max(5, (int)$case['limite']);
            if ((int)$case['dias_total'] < $simulationDay || $simulationDay >= $threshold) continue;
            $simulated = $case;
            $simulated['dias_atual'] = $simulationDay;
            $score = $this->scoreCase($simulated, $training);
            if (!$score) continue;
            $eligibleTrain = array_values(array_filter($training, static fn($r) => (int)$r['dias_total'] >= $simulationDay));
            $longTrain = count(array_filter($eligibleTrain, static fn($r) => (int)$r['dias_total'] >= $threshold));
            $baseline = ($longTrain + 1.0) / (count($eligibleTrain) + 2.0);
            $predictions[] = (float)$score['probability'];
            $nbPredictions[] = $this->predictNaiveBayes($nbModel, $case);
            $baselinePredictions[] = $baseline;
            $labels[] = (int)$case['dias_total'] >= $threshold ? 1 : 0;
        }
        $n = count($labels);
        if ($n < 30) return ['available'=>false,'message'=>'Amostra de teste insuficiente.','samples'=>$n];
        $positives = array_sum($labels);
        $brier = 0.0; $baselineBrier = 0.0; $nbBrier = 0.0;
        for ($i=0;$i<$n;$i++) {
            $brier += ($predictions[$i]-$labels[$i])**2;
            $baselineBrier += ($baselinePredictions[$i]-$labels[$i])**2;
            $nbBrier += ($nbPredictions[$i]-$labels[$i])**2;
        }
        $brier /= $n; $baselineBrier /= $n; $nbBrier /= $n;
        return [
            'available'=>true,'simulation_day'=>$simulationDay,'training_samples'=>count($training),
            'test_samples'=>$n,'positives'=>$positives,'brier'=>round($brier,4),
            'baseline_brier'=>round($baselineBrier,4),'auc'=>round($this->auc($predictions,$labels),3),
            'beats_baseline'=>$brier < $baselineBrier,'model'=>self::MODEL_VERSION,
            'supervised_brier'=>round($nbBrier,4),'supervised_auc'=>round($this->auc($nbPredictions,$labels),3),
            'supervised_beats_baseline'=>$nbBrier < $baselineBrier,
        ];
    }

    private function fetchActive(array $ids): array
    {
        $ph = implode(',', array_fill(0, count($ids), '?'));
        $sql = "SELECT i.id_internacao,i.fk_hospital_int,
                       COALESCE(NULLIF(i.grupo_patologia_int,''),'__sem_grupo__') grupo,
                       COALESCE(NULLIF(i.acomodacao_int,''),'__sem_acomodacao__') acomodacao,
                       i.data_intern_int,GREATEST(0,DATEDIFF(CURDATE(),i.data_intern_int)) dias_atual,
                       COALESCE(NULLIF(CAST(se.longa_permanencia_seg AS UNSIGNED),0),30) limite
                  FROM tb_internacao i
                  LEFT JOIN tb_paciente p ON p.id_paciente=i.fk_paciente_int
                  LEFT JOIN tb_seguradora se ON se.id_seguradora=p.fk_seguradora_pac
                 WHERE i.id_internacao IN ({$ph}) AND i.internado_int='s'
                   AND i.data_intern_int BETWEEN '2020-01-01' AND CURDATE()";
        $stmt = $this->conn->prepare($sql);
        foreach ($ids as $index => $id) $stmt->bindValue($index + 1, $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    private function fetchHistory(): array
    {
        $sql = "SELECT i.id_internacao,i.fk_hospital_int,
                       COALESCE(NULLIF(i.grupo_patologia_int,''),'__sem_grupo__') grupo,
                       COALESCE(NULLIF(i.acomodacao_int,''),'__sem_acomodacao__') acomodacao,
                       COALESCE(NULLIF(i.modo_internacao_int,''),'__sem_modo__') modo,
                       COALESCE(NULLIF(i.tipo_admissao_int,''),'__sem_tipo__') tipo_admissao,
                       COALESCE(TIMESTAMPDIFF(YEAR,p.data_nasc_pac,i.data_intern_int),CAST(p.idade_pac AS UNSIGNED),0) idade,
                       (SELECT COUNT(*) FROM tb_internacao ip
                         WHERE ip.fk_paciente_int=i.fk_paciente_int AND ip.data_intern_int<i.data_intern_int) internacoes_previas,
                       EXISTS(SELECT 1 FROM tb_uti u WHERE u.fk_internacao_uti=i.id_internacao
                               AND u.data_internacao_uti BETWEEN i.data_intern_int AND DATE_ADD(i.data_intern_int,INTERVAL 5 DAY)) uti_d5,
                       EXISTS(SELECT 1 FROM tb_gestao g WHERE g.fk_internacao_ges=i.id_internacao
                               AND g.data_create_ges BETWEEN i.data_intern_int AND DATE_ADD(i.data_intern_int,INTERVAL 5 DAY)
                               AND LOWER(COALESCE(g.evento_adverso_ges,'n')) IN ('s','sim','1')) evento_d5,
                       (SELECT COUNT(*) FROM tb_visita v WHERE v.fk_internacao_vis=i.id_internacao
                               AND DATE(v.data_visita_vis) BETWEEN i.data_intern_int AND DATE_ADD(i.data_intern_int,INTERVAL 5 DAY)) visitas_d5,
                       al.data_alta_alt,GREATEST(1,DATEDIFF(al.data_alta_alt,i.data_intern_int)+1) dias_total,
                       COALESCE(NULLIF(CAST(se.longa_permanencia_seg AS UNSIGNED),0),30) limite
                  FROM tb_internacao i
                  JOIN (SELECT fk_id_int_alt,MAX(data_alta_alt) data_alta_alt FROM tb_alta
                         WHERE data_alta_alt IS NOT NULL GROUP BY fk_id_int_alt) al
                    ON al.fk_id_int_alt=i.id_internacao
                  LEFT JOIN tb_paciente p ON p.id_paciente=i.fk_paciente_int
                  LEFT JOIN tb_seguradora se ON se.id_seguradora=p.fk_seguradora_pac
                 WHERE i.data_intern_int BETWEEN '2020-01-01' AND CURDATE()
                   AND al.data_alta_alt>=i.data_intern_int
                   AND DATEDIFF(al.data_alta_alt,i.data_intern_int) BETWEEN 0 AND 365";
        return $this->conn->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    private function scoreCase(array $case, array $history): ?array
    {
        $currentDays = max(0, (int)$case['dias_atual']);
        $threshold = max(5, (int)$case['limite']);
        if ($currentDays >= $threshold) return null;
        $eligible = array_values(array_filter($history, static fn($r) =>
            (int)$r['id_internacao'] !== (int)$case['id_internacao']));
        if (count($eligible) < self::MIN_COHORT) return null;

        $tiers = [
            ['hospital, grupo e acomodação', static fn($r) => (int)$r['fk_hospital_int']===(int)$case['fk_hospital_int'] && $r['grupo']===$case['grupo'] && $r['acomodacao']===$case['acomodacao']],
            ['hospital e grupo clínico', static fn($r) => (int)$r['fk_hospital_int']===(int)$case['fk_hospital_int'] && $r['grupo']===$case['grupo']],
            ['grupo clínico e acomodação', static fn($r) => $r['grupo']===$case['grupo'] && $r['acomodacao']===$case['acomodacao']],
            ['mesmo hospital', static fn($r) => (int)$r['fk_hospital_int']===(int)$case['fk_hospital_int']],
            ['mesmo grupo clínico', static fn($r) => $r['grupo']===$case['grupo']],
            ['histórico global', static fn($r) => true],
        ];
        $cohort = [];
        $cohortLabel = 'histórico global';
        foreach ($tiers as [$label, $matcher]) {
            $candidate = array_values(array_filter($eligible, $matcher));
            if (count($candidate) >= self::MIN_COHORT || $label === 'histórico global') {
                $cohort = $candidate; $cohortLabel = $label; break;
            }
        }
        if (!$cohort) return null;

        $globalLong = count(array_filter($eligible, static fn($r) => (int)$r['dias_total'] >= $threshold));
        $globalRate = ($globalLong + 1.0) / (count($eligible) + 2.0);
        $cohortLong = count(array_filter($cohort, static fn($r) => (int)$r['dias_total'] >= $threshold));
        $probability = ($cohortLong + self::PRIOR_STRENGTH * $globalRate) / (count($cohort) + self::PRIOR_STRENGTH);
        $probability = max(0.0, min(1.0, $probability));
        $sample = count($cohort);
        $confidence = min(90, max(20, (int)round(25 + min(45, $sample * 1.2) + min(20, $cohortLong * 2))));
        if ($cohortLong < 3) $confidence = min($confidence, 45);
        $level = $probability >= .60 ? 'muito_alto' : ($probability >= .40 ? 'alto' : ($probability >= .20 ? 'moderado' : 'baixo'));

        return [
            'available'=>true,'percentage'=>(int)round($probability*100),'probability'=>round($probability,3),
            'risk_level'=>$level,'confidence'=>$confidence,'sample_size'=>$sample,'events'=>$cohortLong,
            'current_days'=>$currentDays,'threshold_days'=>$threshold,'days_to_threshold'=>max(0,$threshold-$currentDays),
            'cohort'=>$cohortLabel,'model'=>self::MODEL_VERSION,
            'explanation'=>sprintf('%d de %d casos comparáveis ultrapassaram %d dias; paciente está no dia %d.', $cohortLong,$sample,$threshold,$currentDays),
        ];
    }

    private function auc(array $predictions, array $labels): float
    {
        $positiveScores=[]; $negativeScores=[];
        foreach ($labels as $i=>$label) {
            if ($label===1) $positiveScores[]=$predictions[$i]; else $negativeScores[]=$predictions[$i];
        }
        if (!$positiveScores || !$negativeScores) return 0.5;
        $wins=0.0; $pairs=count($positiveScores)*count($negativeScores);
        foreach ($positiveScores as $p) foreach ($negativeScores as $n) {
            if ($p>$n) $wins+=1; elseif ($p===$n) $wins+=.5;
        }
        return $wins/$pairs;
    }

    private function featureValues(array $row): array
    {
        $age=(int)($row['idade']??0);
        return [
            'idade'=>$age<=0?'nd':($age>=80?'80+':($age>=65?'65-79':($age>=40?'40-64':'0-39'))),
            'previas'=>(int)($row['internacoes_previas']??0)>=2?'2+':((int)($row['internacoes_previas']??0)===1?'1':'0'),
            'uti'=>(int)($row['uti_d5']??0)===1?'s':'n',
            'evento'=>(int)($row['evento_d5']??0)===1?'s':'n',
            'visitas'=>(int)($row['visitas_d5']??0)===0?'0':((int)$row['visitas_d5']>=3?'3+':'1-2'),
            'grupo'=>(string)($row['grupo']??'__sem_grupo__'),
            'modo'=>(string)($row['modo']??'__sem_modo__'),
            'tipo'=>(string)($row['tipo_admissao']??'__sem_tipo__'),
        ];
    }

    private function trainNaiveBayes(array $rows): array
    {
        $model=['n'=>count($rows),'pos'=>0,'features'=>[]];
        foreach($rows as $row){
            $y=(int)$row['dias_total']>=max(5,(int)$row['limite'])?1:0;
            $model['pos']+=$y;
            foreach($this->featureValues($row) as $feature=>$value){
                $model['features'][$feature][$value]['n']=($model['features'][$feature][$value]['n']??0)+1;
                $model['features'][$feature][$value]['pos']=($model['features'][$feature][$value]['pos']??0)+$y;
            }
        }
        return $model;
    }

    private function predictNaiveBayes(array $model,array $row): float
    {
        $n=max(1,(int)$model['n']);$pos=(int)$model['pos'];$neg=$n-$pos;
        $logOdds=log(($pos+1)/($neg+1));
        foreach($this->featureValues($row) as $feature=>$value){
            $values=$model['features'][$feature]??[];$k=max(1,count($values));$stat=$values[$value]??['n'=>0,'pos'=>0];
            $valuePos=(int)$stat['pos'];$valueNeg=(int)$stat['n']-$valuePos;
            $pPos=($valuePos+1)/($pos+$k);$pNeg=($valueNeg+1)/($neg+$k);
            $logOdds+=log($pPos/$pNeg);
        }
        $logOdds*=.45;
        return 1/(1+exp(-max(-20,min(20,$logOdds))));
    }
}
