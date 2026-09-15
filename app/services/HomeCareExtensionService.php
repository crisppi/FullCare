<?php
declare(strict_types=1);

final class HomeCareExtensionService
{
    public const MODES = ['procedimento_pontual'=>'Procedimento pontual','atendimento_multiprofissional'=>'Atendimento multiprofissional','internacao_domiciliar_6h'=>'Internação domiciliar 6h','internacao_domiciliar_12h'=>'Internação domiciliar 12h','internacao_domiciliar_24h'=>'Internação domiciliar 24h'];
    public const STATUSES = ['pendente'=>'Pendente','aprovada'=>'Aprovada','parcial'=>'Aprovada parcialmente','negada'=>'Negada'];
    private PDO $db;
    private array $ctx;
    public function __construct(PDO $db,array $ctx) { $this->db=$db; $this->ctx=$ctx; }
    public static function migrate(PDO $db): void
    {
        require_once __DIR__.'/../../utils/home_care_schema.php';
        fullcareEnsureHomeCareSchema($db);
        $sql=file_get_contents(__DIR__.'/../../sql/migrations/20260909_home_care_prorrogacoes.sql');
        foreach (explode(';',$sql) as $statement) if (trim($statement)!=='') $db->exec($statement);
        require_once __DIR__.'/../schemaEnsurer.php';
        ensure_schema_version_table($db);
        $stmt=$db->prepare('INSERT IGNORE INTO schema_version(version,description,applied_by,file_name,checksum) VALUES(?,?,?,?,?)');
        $stmt->execute(['20260909_home_care_prorrogacoes','Planos autorizados e prorrogações de home care','migration','20260909_home_care_prorrogacoes.sql',hash('sha256',$sql)]);
    }
    private function query(string $sql,array $params=[]): PDOStatement { $s=$this->db->prepare($sql); $s->execute($params); return $s; }
    private function from(): string
    {
        return ' FROM tb_internacao i JOIN tb_paciente p ON p.id_paciente=i.fk_paciente_int LEFT JOIN tb_hospital h ON h.id_hospital=i.fk_hospital_int LEFT JOIN tb_seguradora s ON s.id_seguradora=p.fk_seguradora_pac JOIN tb_home_care_avaliacao hc ON hc.id_home_care=(SELECT MAX(hc2.id_home_care) FROM tb_home_care_avaliacao hc2 WHERE hc2.fk_internacao_hc=i.id_internacao)';
    }
    public function cases(string $search,int $page=1,bool $activeOnly=false): array
    {
        $params=[]; $where=' WHERE 1=1 '.ajax_scope_clause_for_internacao($this->ctx,'i',$params,'hc');
        if ($activeOnly) $where.=" AND hc.status_hc='implantado'";
        if ($search!=='') { $where.=' AND (p.nome_pac LIKE :q OR h.nome_hosp LIKE :qh)'; $params['q']=$params['qh']='%'.$search.'%'; }
        $count=(int)$this->query('SELECT COUNT(*)'.$this->from().$where,$params)->fetchColumn();
        $page=max(1,min($page,max(1,(int)ceil($count/25)))); $offset=($page-1)*25;
        $rows=$this->query("SELECT i.id_internacao,p.nome_pac,h.nome_hosp,s.seguradora_seg,hc.status_hc,hc.fornecedor_hc,hc.modalidade_aprovada_hc,hc.modalidade_sugerida_hc,
            (SELECT MAX(pl.fim) FROM tb_hc_plano pl WHERE pl.internacao_id=i.id_internacao) AS autorizado_ate,
            (SELECT COUNT(*) FROM tb_hc_prorrogacao pr JOIN tb_hc_plano pl ON pl.id=pr.plano_id WHERE pl.internacao_id=i.id_internacao AND pr.status='pendente') AS pendentes".$this->from().$where." ORDER BY pendentes DESC, p.nome_pac, i.id_internacao DESC LIMIT 25 OFFSET $offset",$params)->fetchAll(PDO::FETCH_ASSOC);
        return compact('rows','count','page');
    }
    public function context(int $case): array
    {
        $params=['id'=>$case]; $scope=ajax_scope_clause_for_internacao($this->ctx,'i',$params,'hc');
        $row=$this->query('SELECT i.id_internacao,p.nome_pac,h.nome_hosp,s.seguradora_seg,hc.*'.$this->from().' WHERE i.id_internacao=:id '.$scope,$params)->fetch(PDO::FETCH_ASSOC);
        if (!$row) throw new DomainException('Caso não encontrado ou sem acesso.');
        return $row;
    }
    public function plans(int $case): array { $this->context($case); return $this->query('SELECT * FROM tb_hc_plano WHERE internacao_id=? ORDER BY fim DESC,id DESC',[$case])->fetchAll(PDO::FETCH_ASSOC); }
    public function requests(int $case): array
    {
        $this->context($case);
        return $this->query('SELECT pr.*,u.usuario_user AS solicitante,d.usuario_user AS decisor FROM tb_hc_prorrogacao pr JOIN tb_hc_plano pl ON pl.id=pr.plano_id LEFT JOIN tb_user u ON u.id_usuario=pr.solicitado_por LEFT JOIN tb_user d ON d.id_usuario=pr.decidido_por WHERE pl.internacao_id=? ORDER BY pr.id DESC',[$case])->fetchAll(PDO::FETCH_ASSOC);
    }
    private static function text(array $data,string $key,int $max,bool $required=true): string
    {
        if (!is_scalar($data[$key]??'')) throw new DomainException('Campo inválido: '.$key);
        $v=trim((string)($data[$key]??''));
        if (($required && $v==='') || mb_strlen($v)>$max) throw new DomainException('Preencha corretamente o campo '.$key.'.');
        return $v;
    }
    public static function validatePlan(array $data): array
    {
        $out=[];
        foreach (['inicio'=>10,'fim'=>10,'fornecedor'=>120,'modalidade'=>60,'plano'=>5000,'equipe'=>5000] as $key=>$max) $out[$key]=self::text($data,$key,$max);
        foreach (['materiais','equipamentos'] as $key) $out[$key]=self::text($data,$key,5000,false);
        foreach (['inicio','fim'] as $key) {
            $dt=DateTimeImmutable::createFromFormat('!Y-m-d',$out[$key]);
            if (!$dt || $dt->format('Y-m-d')!==$out[$key]) throw new DomainException('Período inválido.');
        }
        if ($out['fim']<$out['inicio']) throw new DomainException('O fim do período deve ser igual ou posterior ao início.');
        if (!isset(self::MODES[$out['modalidade']])) throw new DomainException('Selecione uma modalidade válida.');
        return $out;
    }
    private function activeCase(int $case): void
    {
        $ctx=$this->context($case);
        if (!in_array($ctx['status_hc'],['implantado','implantacao'],true)) throw new DomainException('O caso precisa estar implantado ou em implantação na gestão de home care.');
    }
    private function atomic(callable $fn)
    {
        $owns=!$this->db->inTransaction(); if ($owns) $this->db->beginTransaction();
        try { $result=$fn(); if ($owns) $this->db->commit(); return $result; }
        catch (Throwable $e) { if ($owns && $this->db->inTransaction()) $this->db->rollBack(); throw $e; }
    }
    private function lockCase(int $case): void
    {
        $this->context($case);
        $this->query('SELECT id_internacao FROM tb_internacao WHERE id_internacao=? FOR UPDATE',[$case]);
    }
    private function insertPlan(int $case,array $plan,string $authorization,?int $request=null): int
    {
        $plan['internacao_id']=$case; $plan['solicitacao_id']=$request; $plan['autorizacao']=$authorization; $plan['criado_por']=(int)$this->ctx['user_id'];
        $keys=array_keys($plan);
        $this->query('INSERT INTO tb_hc_plano('.implode(',',$keys).') VALUES(:'.implode(',:',$keys).')',$plan);
        return (int)$this->db->lastInsertId();
    }
    public function createInitial(int $case,array $data): int
    {
        $plan=self::validatePlan($data); $auth=self::text($data,'autorizacao',100);
        return $this->atomic(function() use($case,$plan,$auth) {
            $this->lockCase($case); $this->activeCase($case);
            if ($this->plans($case)) throw new DomainException('Este caso já tem um plano. Registre uma prorrogação.');
            return $this->insertPlan($case,$plan,$auth);
        });
    }
    public function request(int $case,int $planId,array $data): int
    {
        $proposal=self::validatePlan($data); $justification=self::text($data,'justificativa',5000);
        return $this->atomic(function() use($case,$planId,$proposal,$justification) {
            $this->lockCase($case); $this->activeCase($case);
            $latest=$this->plans($case)[0]??null;
            if (!$latest || (int)$latest['id']!==$planId) throw new DomainException('O plano de referência mudou. Recarregue a página.');
            if ($proposal['inicio']<=$latest['fim']) throw new DomainException('A prorrogação deve começar após o período já autorizado.');
            if ($this->query("SELECT id FROM tb_hc_prorrogacao WHERE plano_id=? AND status='pendente'",[$planId])->fetchColumn()) throw new DomainException('Já existe uma solicitação pendente para este plano.');
            $proposal['plano_id']=$planId; $proposal['justificativa']=$justification; $proposal['solicitado_por']=(int)$this->ctx['user_id'];
            $keys=array_keys($proposal);
            $this->query('INSERT INTO tb_hc_prorrogacao('.implode(',',$keys).') VALUES(:'.implode(',:',$keys).')',$proposal);
            return (int)$this->db->lastInsertId();
        });
    }
    public function decide(int $case,int $requestId,array $data): void
    {
        $decision=self::text($data,'decisao',20); $opinion=self::text($data,'parecer',5000);
        if (!in_array($decision,['aprovada','parcial','negada'],true)) throw new DomainException('Decisão inválida.');
        $approved=$decision==='negada'?null:self::validatePlan($data);
        $auth=$decision==='negada'?'':self::text($data,'autorizacao',100);
        $this->atomic(function() use($case,$requestId,$data,$decision,$opinion,$approved,$auth) {
            $this->lockCase($case);
            $request=$this->query('SELECT pr.* FROM tb_hc_prorrogacao pr JOIN tb_hc_plano pl ON pl.id=pr.plano_id WHERE pr.id=? AND pl.internacao_id=? FOR UPDATE',[$requestId,$case])->fetch(PDO::FETCH_ASSOC);
            if (!$request) throw new DomainException('Solicitação não encontrada neste caso.');
            if ($request['status']!=='pendente' || (int)$request['versao']!==(int)($data['versao']??0)) throw new DomainException('Esta solicitação já foi decidida ou alterada. Recarregue a página.');
            if ($approved) {
                $this->activeCase($case);
                $latest=$this->plans($case)[0]??null;
                if (!$latest || (int)$latest['id']!==(int)$request['plano_id']) throw new DomainException('O plano de referência mudou.');
                if ($approved['inicio']<$request['inicio'] || $approved['fim']>$request['fim']) throw new DomainException('O período autorizado deve estar dentro do período solicitado.');
                if ($decision==='aprovada') foreach (array_keys($approved) as $key) if ($approved[$key]!==$request[$key]) throw new DomainException('Para alterar o período ou o plano solicitado, selecione aprovação parcial.');
                $this->insertPlan($case,$approved,$auth,$requestId);
            }
            $this->query('UPDATE tb_hc_prorrogacao SET status=?,parecer=?,decidido_por=?,decidido_em=CURRENT_TIMESTAMP,versao=versao+1 WHERE id=?',[$decision,$opinion,(int)$this->ctx['user_id'],$requestId]);
        });
    }
}
