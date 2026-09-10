<?php
declare(strict_types=1);

final class ProntoSocorroAuditService
{
    public const GLOSA_REASONS = [
        'Cobrança em duplicidade',
        'Valor divergente do contratado',
        'Quantidade divergente',
        'Item incluso no pacote',
        'Ausência de autorização',
        'Documentação insuficiente',
        'Procedimento não comprovado',
        'Outros',
    ];
    public const BLOCKS = ['pacote'=>'Pacote', 'matmed'=>'Materiais e medicamentos', 'honorario'=>'Honorários', 'exame'=>'Exames / SADT', 'outros'=>'Taxas e outros'];
    public const CATEGORIES = ['matmed' => 'Materiais e medicamentos', 'pacote' => 'Pacote', 'procedimento' => 'Procedimentos', 'honorario' => 'Honorários', 'medicamento' => 'Medicamentos', 'material' => 'Materiais', 'exame' => 'Exames / SADT', 'taxa' => 'Taxas', 'outros' => 'Outros'];
    private PDO $db;
    private array $ctx;
    public function __construct(PDO $db, array $ctx) { $this->db = $db; $this->ctx = $ctx; }

    public static function migrate(PDO $db): void
    {
        $sql = file_get_contents(__DIR__ . '/../../sql/migrations/20260909_contas_ps.sql');
        foreach (explode(';', $sql) as $statement) {
            if (trim($statement) !== '') $db->exec($statement);
        }
        $blockSql = file_get_contents(__DIR__ . '/../../sql/migrations/20260909_ps_blocos.sql');
        $hasDetails = (int)$db->query("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='tb_ps_item' AND COLUMN_NAME='detalhes'")->fetchColumn();
        if (!$hasDetails) $db->exec($blockSql);
        require_once __DIR__ . '/../schemaEnsurer.php';
        ensure_schema_version_table($db);
        $stmt = $db->prepare('INSERT IGNORE INTO schema_version (version,description,applied_by,file_name,checksum) VALUES (?,?,?,?,?)');
        $stmt->execute(['20260909_contas_ps','Auditoria de contas de pronto-socorro por lote','migration','20260909_contas_ps.sql',hash('sha256',$sql)]);
        $stmt->execute(['20260909_ps_blocos','Lançamento de PS por blocos','migration','20260909_ps_blocos.sql',hash('sha256',$blockSql)]);
    }

    private function scope(array &$params, string $alias = 'l'): string
    {
        $mode = ajax_scope_mode($this->ctx);
        if ($mode === 'full') return '1=1';
        if ($mode === 'seguradora') {
            $params['scope_seg'] = (int)$this->ctx['seguradora_id'];
            return "$alias.seguradora_id = :scope_seg AND $alias.seguradora_id > 0";
        }
        $params['scope_user'] = (int)$this->ctx['user_id'];
        return "EXISTS (SELECT 1 FROM tb_hospitalUser hu WHERE hu.fk_hospital_user = $alias.hospital_id AND hu.fk_usuario_hosp = :scope_user)";
    }

    private function query(string $sql, array $params = []): PDOStatement
    {
        $stmt = $this->db->prepare($sql); $stmt->execute($params); return $stmt;
    }

    public function options(): array
    {
        $params = [];
        $sql = 'SELECT id_hospital AS id, nome_hosp AS nome FROM tb_hospital h';
        if (ajax_scope_mode($this->ctx) === 'hospital') {
            $sql .= ' WHERE EXISTS (SELECT 1 FROM tb_hospitalUser hu WHERE hu.fk_hospital_user=h.id_hospital AND hu.fk_usuario_hosp=:uid)';
            $params['uid'] = (int)$this->ctx['user_id'];
        }
        $hospitais = $this->query($sql . ' ORDER BY nome_hosp', $params)->fetchAll(PDO::FETCH_ASSOC);
        $params = [];
        $sql = 'SELECT id_seguradora AS id, seguradora_seg AS nome FROM tb_seguradora';
        if (ajax_scope_mode($this->ctx) === 'seguradora') {
            $sql .= ' WHERE id_seguradora=:sid'; $params['sid'] = (int)$this->ctx['seguradora_id'];
        }
        return ['hospitais' => $hospitais, 'seguradoras' => $this->query($sql . ' ORDER BY seguradora_seg', $params)->fetchAll(PDO::FETCH_ASSOC)];
    }

    public function lots(string $search = '', int $page = 1): array
    {
        $params = []; $where = $this->scope($params);
        if ($search !== '') { $where .= ' AND (l.numero LIKE :q OR h.nome_hosp LIKE :qh)'; $params['q'] = $params['qh'] = '%' . $search . '%'; }
        $from = " FROM tb_ps_lote l JOIN tb_hospital h ON h.id_hospital=l.hospital_id JOIN tb_seguradora s ON s.id_seguradora=l.seguradora_id WHERE $where";
        $count = (int)$this->query('SELECT COUNT(*)' . $from, $params)->fetchColumn();
        $page = max(1, min($page, max(1, (int)ceil($count / 20)))); $offset = ($page - 1) * 20;
        $rows = $this->query("SELECT l.*, h.nome_hosp AS hospital, s.seguradora_seg AS seguradora,
            (SELECT COUNT(*) FROM tb_ps_conta c WHERE c.lote_id=l.id) AS contas,
            (SELECT COUNT(*) FROM tb_ps_conta c WHERE c.lote_id=l.id AND c.status='finalizada') AS finalizadas,
            (SELECT COALESCE(SUM(c.cobrado),0) FROM tb_ps_conta c WHERE c.lote_id=l.id) AS cobrado,
            (SELECT COALESCE(SUM(c.glosado),0) FROM tb_ps_conta c WHERE c.lote_id=l.id) AS glosado,
            (SELECT COALESCE(SUM(c.liberado),0) FROM tb_ps_conta c WHERE c.lote_id=l.id) AS liberado" . $from . " ORDER BY l.id DESC LIMIT 20 OFFSET $offset", $params)->fetchAll(PDO::FETCH_ASSOC);
        return compact('rows', 'count', 'page');
    }

    public function lot(int $id): array
    {
        $params = ['id' => $id]; $where = $this->scope($params);
        $row = $this->query("SELECT l.*, h.nome_hosp AS hospital, s.seguradora_seg AS seguradora FROM tb_ps_lote l JOIN tb_hospital h ON h.id_hospital=l.hospital_id JOIN tb_seguradora s ON s.id_seguradora=l.seguradora_id WHERE l.id=:id AND $where", $params)->fetch(PDO::FETCH_ASSOC);
        if (!$row) throw new DomainException('Lote não encontrado ou sem acesso.');
        return $row;
    }

    public function accounts(int $lot): array
    {
        $this->lot($lot);
        return $this->query('SELECT * FROM tb_ps_conta WHERE lote_id=? ORDER BY id DESC', [$lot])->fetchAll(PDO::FETCH_ASSOC);
    }

    public function account(int $id): array
    {
        $row = $this->query('SELECT * FROM tb_ps_conta WHERE id=?', [$id])->fetch(PDO::FETCH_ASSOC);
        if (!$row) throw new DomainException('Conta não encontrada.');
        $this->lot((int)$row['lote_id']);
        $row['items'] = $this->query('SELECT * FROM tb_ps_item WHERE conta_id=? ORDER BY id', [$id])->fetchAll(PDO::FETCH_ASSOC);
        return $row;
    }

    public function history(int $id): array
    {
        $this->account($id);
        return $this->query('SELECT h.versao,h.registrado_em,u.usuario_user AS usuario FROM tb_ps_historico h LEFT JOIN tb_user u ON u.id_usuario=h.usuario_id WHERE conta_id=? ORDER BY h.versao DESC LIMIT 20', [$id])->fetchAll(PDO::FETCH_ASSOC);
    }

    private static function text(array $data, string $key, int $max, bool $required = true): string
    {
        if (isset($data[$key]) && !is_scalar($data[$key])) throw new DomainException('Campo inválido: ' . $key);
        $value = trim((string)($data[$key] ?? ''));
        if (($required && $value === '') || mb_strlen($value) > $max) throw new DomainException('Preencha corretamente o campo ' . $key . '.');
        return $value;
    }

    public static function cents($value): int
    {
        if (!is_scalar($value)) throw new DomainException('Valor monetário inválido.');
        $value = trim((string)$value);
        if (!preg_match('/^\d{1,9}(?:[.,]\d{1,2})?$/D', $value)) throw new DomainException('Informe valores positivos com até duas casas decimais, sem separador de milhar.');
        $parts = explode('.', str_replace(',', '.', $value));
        return (int)$parts[0] * 100 + (int)str_pad($parts[1] ?? '', 2, '0');
    }
    private static function decimal(int $cents): string { return intdiv($cents, 100) . '.' . str_pad((string)($cents % 100), 2, '0', STR_PAD_LEFT); }

    public function createLot(array $data): int
    {
        $numero = self::text($data, 'numero', 60);
        $competencia = self::text($data, 'competencia', 7);
        $recebido = self::text($data, 'recebido_em', 10);
        if (!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/D', $competencia) || !self::validDate($recebido, 'Y-m-d')) throw new DomainException('Competência ou data de recebimento inválida.');
        $options = $this->options(); $hospital = (int)($data['hospital_id'] ?? 0); $seg = (int)($data['seguradora_id'] ?? 0);
        if (!in_array($hospital, array_map('intval', array_column($options['hospitais'], 'id')), true) || !in_array($seg, array_map('intval', array_column($options['seguradoras'], 'id')), true)) throw new DomainException('Hospital ou seguradora não permitido.');
        $this->query('INSERT INTO tb_ps_lote (numero,hospital_id,seguradora_id,competencia,recebido_em,criado_por) VALUES (?,?,?,?,?,?)', [$numero,$hospital,$seg,$competencia,$recebido,(int)$this->ctx['user_id']]);
        return (int)$this->db->lastInsertId();
    }

    private static function validDate(string $value, string $format): bool
    {
        $date = DateTimeImmutable::createFromFormat('!' . $format, $value);
        return $date && $date->format($format) === $value;
    }

    /** Projeta contas antigas em blocos, mantendo o detalhamento e os motivos originais. */
    public static function toBlocks(array $items): array
    {
        $blocks = [];
        foreach (self::BLOCKS as $key=>$label) $blocks[$key] = ['cobrado'=>'0.00','glosa'=>'0.00','motivo'=>'','detalhes'=>''];
        $reasons = [];
        foreach ($items as $item) {
            $category = (string)$item['categoria'];
            $key = in_array($category,['material','medicamento','matmed'],true) ? 'matmed' : (isset(self::BLOCKS[$category]) ? $category : 'outros');
            $charged = self::cents($item['unitario']) * (int)$item['quantidade'];
            $denied = self::cents($item['glosa']);
            $blocks[$key]['cobrado'] = self::decimal(self::cents($blocks[$key]['cobrado']) + $charged);
            $blocks[$key]['glosa'] = self::decimal(self::cents($blocks[$key]['glosa']) + $denied);
            $reason = trim((string)($item['motivo'] ?? ''));
            if ($reason !== '') $reasons[$key][$reason] = true;
            $detail = (string)($item['detalhes'] ?? '');
            if ($category !== $key || $item['descricao'] !== self::BLOCKS[$key] || (int)$item['quantidade'] !== 1) {
                $line = (self::CATEGORIES[$category] ?? $category) . ' — ' . $item['descricao'] . ': ' . $item['quantidade'] . ' × R$ ' . number_format((float)$item['unitario'],2,',','.') . '; cobrado R$ ' . number_format($charged/100,2,',','.') . '; glosado R$ ' . number_format($denied/100,2,',','.');
                if ($reason !== '') $line .= '; motivo: ' . $reason;
                $detail = $line . ($detail !== '' ? "\n" . $detail : '');
            }
            if ($detail !== '') $blocks[$key]['detalhes'] .= ($blocks[$key]['detalhes'] !== '' ? "\n" : '') . $detail;
        }
        foreach ($reasons as $key=>$values) $blocks[$key]['motivo'] = count($values) === 1 ? (string)array_key_first($values) : 'Outros';
        return $blocks;
    }

    private static function blockItems(array $data): array
    {
        if (!is_array($data['blocos'])) throw new DomainException('Blocos inválidos.');
        $items = [];
        foreach ($data['blocos'] as $key=>$block) {
            if (!isset(self::BLOCKS[$key]) || !is_array($block)) throw new DomainException('Bloco inválido.');
            $charged = self::cents(($block['cobrado'] ?? '') === '' ? '0' : $block['cobrado']);
            $denied = self::cents(($block['glosa'] ?? '') === '' ? '0' : $block['glosa']);
            $reason = self::text($block,'motivo',500,false);
            $detail = self::text($block,'detalhes',100000,false);
            if ($key === 'pacote' && ($data['modalidade'] ?? '') !== 'pacote') {
                if ($charged || $denied || $detail !== '') throw new DomainException('Conta aberta não pode conter cobrança de pacote.');
                continue;
            }
            if (!$charged && !$denied && $detail === '' && $reason === '' && $key !== 'pacote') continue;
            $items[] = ['categoria'=>$key,'descricao'=>self::BLOCKS[$key],'quantidade'=>1,'unitario'=>self::decimal($charged),'glosa'=>self::decimal($denied),'motivo'=>$reason,'detalhes'=>$detail];
        }
        if (!$items) throw new DomainException('Informe a cobrança de pelo menos um bloco.');
        return $items;
    }

    public static function validateAccount(array $data): array
    {
        $out = [];
        foreach (['numero'=>60,'paciente'=>180,'atendimento'=>80,'entrada'=>16,'modalidade'=>20,'status'=>20] as $field=>$max) $out[$field] = self::text($data,$field,$max);
        foreach (['matricula'=>80,'saida'=>16,'pacote'=>180,'observacao'=>5000] as $field=>$max) $out[$field] = self::text($data,$field,$max,false);
        if (!in_array($out['modalidade'], ['pacote','aberta'], true) || !in_array($out['status'], ['em_auditoria','finalizada'], true)) throw new DomainException('Modalidade ou status inválido.');
        if (!self::validDate($out['entrada'], 'Y-m-d\TH:i') || ($out['saida'] !== '' && (!self::validDate($out['saida'],'Y-m-d\TH:i') || $out['saida'] < $out['entrada']))) throw new DomainException('Confira a entrada e a saída do atendimento.');
        if ($out['modalidade'] === 'pacote' && $out['pacote'] === '') throw new DomainException('Identifique o pacote contratado.');
        if ($out['modalidade'] === 'aberta') $out['pacote'] = '';
        $items = array_key_exists('blocos',$data) ? self::blockItems($data) : ($data['items'] ?? []);
        if (!is_array($items) || count($items) < 1 || count($items) > 100) throw new DomainException('Informe de 1 a 100 itens na conta.');
        $out['items'] = []; $charged = $denied = $packages = 0;
        foreach ($items as $item) {
            if (!is_array($item)) throw new DomainException('Item inválido.');
            $category = self::text($item,'categoria',30); $description = self::text($item,'descricao',180);
            if (!isset(self::CATEGORIES[$category])) throw new DomainException('Categoria inválida.');
            $qty = filter_var($item['quantidade'] ?? null, FILTER_VALIDATE_INT, ['options'=>['min_range'=>1,'max_range'=>100000]]);
            if ($qty === false) throw new DomainException('A quantidade deve ser um inteiro positivo.');
            $unit = self::cents($item['unitario'] ?? ''); $glosa = self::cents($item['glosa'] ?? '0');
            $total = $qty * $unit; $reason = self::text($item,'motivo',500,false);
            if ($glosa > $total) throw new DomainException('A glosa não pode superar a cobrança do item.');
            if ($glosa > 0 && $reason === '') throw new DomainException('Informe o motivo de cada glosa.');
            if ($category === 'pacote') { $packages++; if ($qty !== 1) throw new DomainException('O pacote deve ter quantidade 1.'); }
            $charged += $total; $denied += $glosa;
            if ($charged > 99999999999) throw new DomainException('Valor total da conta excede o limite.');
            $out['items'][] = ['categoria'=>$category,'descricao'=>$description,'quantidade'=>$qty,'unitario'=>self::decimal($unit),'glosa'=>self::decimal($glosa),'motivo'=>$reason,'detalhes'=>self::text($item,'detalhes',100000,false)];
        }
        if (($out['modalidade'] === 'pacote' && $packages !== 1) || ($out['modalidade'] === 'aberta' && $packages !== 0)) throw new DomainException('Conta pacote exige um único item Pacote; conta aberta deve conter apenas itens discriminados.');
        $out['cobrado'] = self::decimal($charged); $out['glosado'] = self::decimal($denied); $out['liberado'] = self::decimal($charged-$denied);
        $out['entrada'] = str_replace('T',' ',$out['entrada']) . ':00';
        $out['saida'] = $out['saida'] === '' ? null : str_replace('T',' ',$out['saida']) . ':00';
        return $out;
    }

    public function saveAccount(int $lot, int $id, array $data): int
    {
        $this->lot($lot); $account = self::validateAccount($data);
        $owns = !$this->db->inTransaction();
        if ($owns) $this->db->beginTransaction();
        try {
            $version = 1;
            if ($id > 0) {
                $old = $this->query('SELECT * FROM tb_ps_conta WHERE id=? AND lote_id=? FOR UPDATE', [$id,$lot])->fetch(PDO::FETCH_ASSOC);
                if (!$old) throw new DomainException('Conta não encontrada neste lote.');
                if ((int)$old['versao'] !== (int)($data['versao'] ?? 0)) throw new DomainException('Esta conta foi alterada por outro usuário. Recarregue antes de salvar.');
                $version = (int)$old['versao'] + 1;
            }
            $fields = $account; unset($fields['items']);
            $fields['lote_id']=$lot; $fields['versao']=$version; $fields['atualizado_por']=(int)$this->ctx['user_id'];
            if ($id > 0) {
                $set = implode(',', array_map(static fn($key)=>"$key=:$key",array_keys($fields)));
                $fields['id']=$id;
                $this->query("UPDATE tb_ps_conta SET $set,atualizado_em=CURRENT_TIMESTAMP WHERE id=:id",$fields);
                $this->query('DELETE FROM tb_ps_item WHERE conta_id=?',[$id]);
            } else {
                $keys = array_keys($fields);
                $this->query('INSERT INTO tb_ps_conta (' . implode(',',$keys) . ') VALUES (:' . implode(',:',$keys) . ')',$fields);
                $id = (int)$this->db->lastInsertId();
            }
            foreach ($account['items'] as $item) {
                $item['conta_id']=$id;
                $this->query('INSERT INTO tb_ps_item (categoria,descricao,quantidade,unitario,glosa,motivo,detalhes,conta_id) VALUES (:categoria,:descricao,:quantidade,:unitario,:glosa,:motivo,:detalhes,:conta_id)',$item);
            }
            $this->query('INSERT INTO tb_ps_historico (conta_id,usuario_id,versao,dados) VALUES (?,?,?,?)',[$id,(int)$this->ctx['user_id'],$version,json_encode($account,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR)]);
            if ($owns) $this->db->commit();
            return $id;
        } catch (Throwable $e) { if ($owns && $this->db->inTransaction()) $this->db->rollBack(); throw $e; }
    }
}
