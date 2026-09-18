<?php
/** Gestão de casos: permissões e escopo sempre verificados no servidor. */
final class GestorEstipulante
{
    public const ROLES = ['gestor_estipulante_med', 'gestor_estipulante_enf', 'gerente_estipulante'];
    public const FIELDS = [
        'origem' => 'Origem da informação / contato', 'situacao' => 'Situação clínica e evolução',
        'diagnosticos' => 'Diagnósticos e problemas ativos', 'funcional' => 'Estado funcional e mobilidade',
        'nutricao' => 'Alimentação e suporte nutricional', 'suportes' => 'Suportes e dispositivos',
        'tratamentos' => 'Tratamentos em andamento', 'exames' => 'Exames e resultados relevantes',
        'plano' => 'Plano assistencial da equipe do hospital', 'contatos' => 'Contatos com equipe, paciente e família',
        'pos_alta' => 'Necessidades após a alta', 'proxima_acao' => 'Próxima ação e acompanhamento'
    ];
    private PDO $db;
    public int $user;
    public string $role;
    public function __construct(PDO $db, int $user) {
        $this->db = $db; $this->user = $user;
        date_default_timezone_set('America/Sao_Paulo');
        $db->exec("SET time_zone = '-03:00'");
        $stmt = $db->prepare('SELECT p.slug FROM tb_user u JOIN tb_access_profile p ON p.id_access_profile=u.fk_access_profile WHERE u.id_usuario=? AND u.ativo_user=\'s\' AND p.ativo=1');
        $stmt->execute([$user]); $this->role = (string)$stmt->fetchColumn();
        if (!in_array($this->role, self::ROLES, true)) throw new DomainException('Perfil sem acesso à gestão do estipulante.');
    }
    public function canWrite(): bool { return in_array($this->role, ['gestor_estipulante_med', 'gestor_estipulante_enf'], true); }
    private function scope(): string {
        return "EXISTS (SELECT 1 FROM ge_escopo s WHERE s.usuario_id=:uid AND ((s.hospital_id=0 AND s.estipulante_id=0 AND s.todos_pacientes=1) OR (s.hospital_id=i.fk_hospital_int AND (s.todos_pacientes=1 OR s.estipulante_id=p.fk_estipulante_pac)))) AND COALESCE(i.deletado_int,'n')<>'s' AND COALESCE(p.deletado_pac,'n')<>'s'";
    }
    public function census(): array {
        $stmt = $this->db->prepare("SELECT i.id_internacao, i.data_intern_int, i.internado_int, i.acomodacao_int, i.rel_int, i.titular_int, i.fk_hospital_int, p.nome_pac, h.nome_hosp, e.nome_est, c.*, u.usuario_user AS responsavel,
        (SELECT MAX(v.finalizado_em) FROM ge_evolucao v WHERE v.internacao_id=i.id_internacao AND v.status='finalizada') AS ultima_evolucao,
        (SELECT MAX(a.data_alta_alt) FROM tb_alta a WHERE a.fk_id_int_alt=i.id_internacao) AS alta_origem,
        (SELECT COUNT(*) FROM ge_pendencia t WHERE t.internacao_id=i.id_internacao AND t.concluido_em IS NULL AND t.prazo<NOW()) AS tarefas_vencidas,
        (SELECT MAX(x.updated_at) FROM tb_internacao x WHERE x.fk_hospital_int=i.fk_hospital_int) AS censo_atualizado
        FROM tb_internacao i JOIN tb_paciente p ON p.id_paciente=i.fk_paciente_int JOIN tb_hospital h ON h.id_hospital=i.fk_hospital_int
        LEFT JOIN tb_estipulante e ON e.id_estipulante=p.fk_estipulante_pac LEFT JOIN ge_caso c ON c.internacao_id=i.id_internacao LEFT JOIN tb_user u ON u.id_usuario=c.responsavel_id
        WHERE " . $this->scope() . " ORDER BY i.data_intern_int DESC, i.id_internacao DESC");
        $stmt->execute(['uid'=>$this->user]); return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function caseAccess(int $id): array {
        $q=$this->db->prepare('SELECT i.*, p.nome_pac, p.fk_estipulante_pac FROM tb_internacao i JOIN tb_paciente p ON p.id_paciente=i.fk_paciente_int WHERE i.id_internacao=:id AND '.$this->scope());
        $q->execute(['id'=>$id,'uid'=>$this->user]);
        $r=$q->fetch(PDO::FETCH_ASSOC); if (!$r) throw new DomainException('Internação fora do seu escopo.'); return $r;
    }
    public function team(int $id): array {
        $case=$this->caseAccess($id);
        $q=$this->db->prepare("SELECT DISTINCT u.id_usuario,u.usuario_user FROM tb_user u JOIN tb_access_profile a ON a.id_access_profile=u.fk_access_profile JOIN ge_escopo s ON s.usuario_id=u.id_usuario WHERE u.ativo_user='s' AND a.ativo=1 AND a.slug IN ('gestor_estipulante_med','gestor_estipulante_enf') AND ((s.hospital_id=0 AND s.estipulante_id=0 AND s.todos_pacientes=1) OR (s.hospital_id=? AND (s.todos_pacientes=1 OR s.estipulante_id=?))) ORDER BY u.usuario_user");
        $q->execute([$case['fk_hospital_int'],$case['fk_estipulante_pac']]);return $q->fetchAll(PDO::FETCH_ASSOC);
    }
    public function records(string $table, int $id): array {
        $this->caseAccess($id);
        if (!in_array($table,['ge_evolucao','ge_pendencia','ge_historico'],true)) throw new DomainException('Lista inválida.');
        $extra=$table==='ge_evolucao' ? " AND (r.status='finalizada' OR r.autor_id=".$this->user.')' : '';
        $author=$table==='ge_pendencia'?'responsavel_id':'autor_id';
        $q=$this->db->prepare("SELECT r.*, u.usuario_user AS autor FROM $table r LEFT JOIN tb_user u ON u.id_usuario=r.$author WHERE r.internacao_id=? $extra ORDER BY r.criado_em DESC, r.id DESC");$q->execute([$id]);return $q->fetchAll(PDO::FETCH_ASSOC);
    }
    private function text(array $data,string $key,int $max=10000): string {
        $v=$data[$key]??''; if (!is_string($v) || mb_strlen($v)>$max) throw new DomainException('Campo inválido: '.$key);return trim($v);
    }
    private function date(string $value,bool $time=false): ?string {
        if ($value==='') return null;
        $format=$time?'Y-m-d\TH:i':'Y-m-d'; $d=DateTimeImmutable::createFromFormat('!'.$format,$value);
        if (!$d || $d->format($format)!==$value) throw new DomainException('Data inválida.');
        return $d->format($time?'Y-m-d H:i:s':'Y-m-d');
    }
    public function save(int $id,array $data): void {
        if (!$this->canWrite()) throw new DomainException('Gerente do estipulante possui acesso somente de consulta.');
        $this->db->beginTransaction();
        try {
            $case=$this->caseAccess($id);
            $this->db->prepare('INSERT IGNORE INTO ge_caso (internacao_id) VALUES (?)')->execute([$id]);
            $q=$this->db->prepare('SELECT * FROM ge_caso WHERE internacao_id=? FOR UPDATE');$q->execute([$id]);$current=$q->fetch(PDO::FETCH_ASSOC);
            if ($current['alta_em'] || $case['internado_int']!=='s') throw new DomainException('Caso encerrado: alterações não permitidas.');
            $action=$this->text($data,'acao',30); $audit=[];
            if ($action==='plano') {
                if ((int)($data['versao']??-1)!==(int)$current['versao']) throw new DomainException('O plano foi atualizado por outra pessoa. Recarregue antes de salvar.');
                $responsible=(int)($data['responsavel_id']??0);
                if ($responsible && !in_array($responsible,array_map('intval',array_column($this->team($id),'id_usuario')),true)) throw new DomainException('Responsável fora do escopo.');
                $date=$this->date($this->text($data,'previsao_alta',10));
                $values=[$responsible?:null,$date,$this->text($data,'barreiras'),$this->text($data,'plano_alta')];
                $this->db->prepare('UPDATE ge_caso SET responsavel_id=?,previsao_alta=?,barreiras=?,plano_alta=?,versao=versao+1 WHERE internacao_id=?')->execute(array_merge($values,[$id]));
                $audit=['anterior'=>$current,'novo'=>$values];
            } elseif ($action==='evolucao') {
                $fields=[];foreach (self::FIELDS as $key=>$label) $fields[$key]=$this->text($data,$key);
                $status=$this->text($data,'status',20);
                if (!in_array($status,['rascunho','finalizada'],true)) throw new DomainException('Status inválido.');
                if ($status==='finalizada' && ($fields['situacao']==='' || $fields['origem']==='' || $fields['plano']==='')) throw new DomainException('Preencha origem, situação clínica e plano assistencial para finalizar.');
                $eid=(int)($data['evolucao_id']??0);$parent=(int)($data['complemento_de']??0);
                if ($parent) {
                    $q=$this->db->prepare("SELECT id FROM ge_evolucao WHERE id=? AND internacao_id=? AND status='finalizada'");$q->execute([$parent,$id]);if (!$q->fetchColumn()) throw new DomainException('Evolução original inválida.');
                }
                $json=json_encode($fields,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);
                if ($eid) {
                    $q=$this->db->prepare("UPDATE ge_evolucao SET dados=?,status=?,finalizado_em=? WHERE id=? AND internacao_id=? AND autor_id=? AND status='rascunho'");$q->execute([$json,$status,$status==='finalizada'?date('Y-m-d H:i:s'):null,$eid,$id,$this->user]);
                    if (!$q->rowCount()) throw new DomainException('Rascunho indisponível ou sem alterações.');
                } else {
                    $this->db->prepare('INSERT INTO ge_evolucao (internacao_id,autor_id,dados,status,complemento_de,finalizado_em) VALUES (?,?,?,?,?,?)')->execute([$id,$this->user,$json,$status,$parent?:null,$status==='finalizada'?date('Y-m-d H:i:s'):null]);$eid=(int)$this->db->lastInsertId();
                }
                $audit=['evolucao_id'=>$eid,'status'=>$status,'dados'=>$fields];
            } elseif ($action==='pendencia') {
                $text=$this->text($data,'descricao',3000);$due=$this->date($this->text($data,'prazo',16),true);$owner=(int)($data['responsavel_id']??0);
                if ($text==='' || !$due || !in_array($owner,array_map('intval',array_column($this->team($id),'id_usuario')),true)) throw new DomainException('Informe pendência, prazo e responsável autorizado.');
                $this->db->prepare('INSERT INTO ge_pendencia (internacao_id,descricao,responsavel_id,prazo,criado_por) VALUES (?,?,?,?,?)')->execute([$id,$text,$owner,$due,$this->user]);$audit=['descricao'=>$text,'prazo'=>$due,'responsavel'=>$owner];
            } elseif ($action==='concluir') {
                $q=$this->db->prepare('UPDATE ge_pendencia SET concluido_em=NOW(),concluido_por=? WHERE id=? AND internacao_id=? AND concluido_em IS NULL');$q->execute([$this->user,(int)($data['pendencia_id']??0),$id]);if (!$q->rowCount()) throw new DomainException('Pendência indisponível.');$audit=['pendencia_id'=>(int)$data['pendencia_id']];
            } elseif ($action==='alta') {
                $date=$this->date($this->text($data,'alta_em',16),true);$destination=$this->text($data,'alta_destino',255);
                if (!$date || $date>date('Y-m-d H:i:s') || substr($date,0,10)<substr($case['data_intern_int'],0,10) || $destination==='') throw new DomainException('Informe destino e data de alta válida, entre a admissão e hoje.');
                $q=$this->db->prepare('SELECT COUNT(*) FROM ge_pendencia WHERE internacao_id=? AND concluido_em IS NULL');$q->execute([$id]);if ($q->fetchColumn()) throw new DomainException('Conclua as pendências antes de encerrar o caso.');
                $this->db->prepare('UPDATE ge_caso SET alta_em=?,alta_destino=?,versao=versao+1 WHERE internacao_id=?')->execute([$date,$destination,$id]);$audit=['alta_em'=>$date,'destino'=>$destination];
            } else throw new DomainException('Operação inválida.');
            $this->db->prepare('INSERT INTO ge_historico (internacao_id,autor_id,acao,dados) VALUES (?,?,?,?)')->execute([$id,$this->user,$action,json_encode($audit,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR)]);
            $this->db->commit();
        } catch (Throwable $e) { $this->db->rollBack();throw $e; }
    }
}
