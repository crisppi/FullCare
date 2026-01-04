<?php

require_once("models/solicitacaoCustomizacao.php");

class SolicitacaoCustomizacaoDAO implements SolicitacaoCustomizacaoDAOInterface
{
    private $conn;
    private $url;

    public function __construct(PDO $conn, $url)
    {
        $this->conn = $conn;
        $this->url = $url;
    }

    private function build(array $data): SolicitacaoCustomizacao
    {
        $s = new SolicitacaoCustomizacao();
        $s->id_solicitacao = $data['id_solicitacao'] ?? null;
        $s->nome = $data['nome'] ?? null;
        $s->empresa = $data['empresa'] ?? null;
        $s->cargo = $data['cargo'] ?? null;
        $s->email = $data['email'] ?? null;
        $s->telefone = $data['telefone'] ?? null;
        $s->data_solicitacao = $data['data_solicitacao'] ?? null;
        $s->descricao = $data['descricao'] ?? null;
        $s->problema_atual = $data['problema_atual'] ?? null;
        $s->resultado_esperado = $data['resultado_esperado'] ?? null;
        $s->impacto_nivel = $data['impacto_nivel'] ?? null;
        $s->descricao_impacto = $data['descricao_impacto'] ?? null;
        $s->prioridade = $data['prioridade'] ?? null;
        $s->prazo_desejado = $data['prazo_desejado'] ?? null;
        $s->responsavel = $data['responsavel'] ?? null;
        $s->assinatura = $data['assinatura'] ?? null;
        $s->data_aprovacao = $data['data_aprovacao'] ?? null;
        $s->aprovacao_conex = $data['aprovacao_conex'] ?? null;
        $s->prazo_resposta = $data['prazo_resposta'] ?? null;
        $s->precificacao = $data['precificacao'] ?? null;
        $s->observacoes_resposta = $data['observacoes_resposta'] ?? null;
        $s->aprovacao_resposta = $data['aprovacao_resposta'] ?? null;
        $s->data_resposta = $data['data_resposta'] ?? null;
        $s->status = $data['status'] ?? null;
        $s->resolvido_em = $data['resolvido_em'] ?? null;
        $s->resolvido_por = $data['resolvido_por'] ?? null;
        $s->versao_sistema = $data['versao_sistema'] ?? null;
        $s->data_create = $data['data_create'] ?? null;
        $s->data_update = $data['data_update'] ?? null;
        $s->usuario_create = $data['usuario_create'] ?? null;
        return $s;
    }

    public function create(SolicitacaoCustomizacao $solicitacao, array $modulos, array $tipos, array $anexos): int
    {
        $this->conn->beginTransaction();
        try {
            $stmt = $this->conn->prepare("INSERT INTO tb_solicitacao_customizacao (
                nome, empresa, cargo, email, telefone, data_solicitacao,
                descricao, problema_atual, resultado_esperado,
                impacto_nivel, descricao_impacto, prioridade, prazo_desejado,
                responsavel, assinatura, data_aprovacao, aprovacao_conex,
                prazo_resposta, precificacao, observacoes_resposta, aprovacao_resposta, data_resposta,
                status, resolvido_em, resolvido_por, versao_sistema, usuario_create
            ) VALUES (
                :nome, :empresa, :cargo, :email, :telefone, :data_solicitacao,
                :descricao, :problema_atual, :resultado_esperado,
                :impacto_nivel, :descricao_impacto, :prioridade, :prazo_desejado,
                :responsavel, :assinatura, :data_aprovacao, :aprovacao_conex,
                :prazo_resposta, :precificacao, :observacoes_resposta, :aprovacao_resposta, :data_resposta,
                :status, :resolvido_em, :resolvido_por, :versao_sistema, :usuario_create
            )");

            $stmt->execute([
                ':nome' => $solicitacao->nome,
                ':empresa' => $solicitacao->empresa,
                ':cargo' => $solicitacao->cargo,
                ':email' => $solicitacao->email,
                ':telefone' => $solicitacao->telefone,
                ':data_solicitacao' => $solicitacao->data_solicitacao,
                ':descricao' => $solicitacao->descricao,
                ':problema_atual' => $solicitacao->problema_atual,
                ':resultado_esperado' => $solicitacao->resultado_esperado,
                ':impacto_nivel' => $solicitacao->impacto_nivel,
                ':descricao_impacto' => $solicitacao->descricao_impacto,
                ':prioridade' => $solicitacao->prioridade,
                ':prazo_desejado' => $solicitacao->prazo_desejado,
                ':responsavel' => $solicitacao->responsavel,
                ':assinatura' => $solicitacao->assinatura,
                ':data_aprovacao' => $solicitacao->data_aprovacao,
                ':aprovacao_conex' => $solicitacao->aprovacao_conex,
                ':prazo_resposta' => $solicitacao->prazo_resposta,
                ':precificacao' => $solicitacao->precificacao,
                ':observacoes_resposta' => $solicitacao->observacoes_resposta,
                ':aprovacao_resposta' => $solicitacao->aprovacao_resposta,
                ':data_resposta' => $solicitacao->data_resposta,
                ':status' => $solicitacao->status,
                ':resolvido_em' => $solicitacao->resolvido_em,
                ':resolvido_por' => $solicitacao->resolvido_por,
                ':versao_sistema' => $solicitacao->versao_sistema,
                ':usuario_create' => $solicitacao->usuario_create,
            ]);

            $id = (int)$this->conn->lastInsertId();

            $this->syncModulos($id, $modulos);
            $this->syncTipos($id, $tipos);
            $this->insertAnexos($id, $anexos);

            $this->conn->commit();
            return $id;
        } catch (Throwable $e) {
            $this->conn->rollBack();
            throw $e;
        }
    }

    public function update(SolicitacaoCustomizacao $solicitacao, array $modulos, array $tipos, array $anexos): void
    {
        $this->conn->beginTransaction();
        try {
            $stmt = $this->conn->prepare("UPDATE tb_solicitacao_customizacao SET
                nome = :nome,
                empresa = :empresa,
                cargo = :cargo,
                email = :email,
                telefone = :telefone,
                data_solicitacao = :data_solicitacao,
                descricao = :descricao,
                problema_atual = :problema_atual,
                resultado_esperado = :resultado_esperado,
                impacto_nivel = :impacto_nivel,
                descricao_impacto = :descricao_impacto,
                prioridade = :prioridade,
                prazo_desejado = :prazo_desejado,
                responsavel = :responsavel,
                assinatura = :assinatura,
                data_aprovacao = :data_aprovacao,
                aprovacao_conex = :aprovacao_conex,
                prazo_resposta = :prazo_resposta,
                precificacao = :precificacao,
                observacoes_resposta = :observacoes_resposta,
                aprovacao_resposta = :aprovacao_resposta,
                data_resposta = :data_resposta,
                status = :status,
                resolvido_em = :resolvido_em,
                resolvido_por = :resolvido_por,
                versao_sistema = :versao_sistema
            WHERE id_solicitacao = :id");

            $stmt->execute([
                ':id' => $solicitacao->id_solicitacao,
                ':nome' => $solicitacao->nome,
                ':empresa' => $solicitacao->empresa,
                ':cargo' => $solicitacao->cargo,
                ':email' => $solicitacao->email,
                ':telefone' => $solicitacao->telefone,
                ':data_solicitacao' => $solicitacao->data_solicitacao,
                ':descricao' => $solicitacao->descricao,
                ':problema_atual' => $solicitacao->problema_atual,
                ':resultado_esperado' => $solicitacao->resultado_esperado,
                ':impacto_nivel' => $solicitacao->impacto_nivel,
                ':descricao_impacto' => $solicitacao->descricao_impacto,
                ':prioridade' => $solicitacao->prioridade,
                ':prazo_desejado' => $solicitacao->prazo_desejado,
                ':responsavel' => $solicitacao->responsavel,
                ':assinatura' => $solicitacao->assinatura,
                ':data_aprovacao' => $solicitacao->data_aprovacao,
                ':aprovacao_conex' => $solicitacao->aprovacao_conex,
                ':prazo_resposta' => $solicitacao->prazo_resposta,
                ':precificacao' => $solicitacao->precificacao,
                ':observacoes_resposta' => $solicitacao->observacoes_resposta,
                ':aprovacao_resposta' => $solicitacao->aprovacao_resposta,
                ':data_resposta' => $solicitacao->data_resposta,
                ':status' => $solicitacao->status,
                ':resolvido_em' => $solicitacao->resolvido_em,
                ':resolvido_por' => $solicitacao->resolvido_por,
                ':versao_sistema' => $solicitacao->versao_sistema,
            ]);

            $this->syncModulos($solicitacao->id_solicitacao, $modulos);
            $this->syncTipos($solicitacao->id_solicitacao, $tipos);
            $this->insertAnexos($solicitacao->id_solicitacao, $anexos);

            $this->conn->commit();
        } catch (Throwable $e) {
            $this->conn->rollBack();
            throw $e;
        }
    }

    public function updateResposta(int $id, array $data): void
    {
        $stmt = $this->conn->prepare("UPDATE tb_solicitacao_customizacao SET
            prazo_resposta = :prazo_resposta,
            precificacao = :precificacao,
            observacoes_resposta = :observacoes_resposta,
            aprovacao_resposta = :aprovacao_resposta,
            data_resposta = :data_resposta,
            status = :status,
            resolvido_em = :resolvido_em,
            resolvido_por = :resolvido_por,
            versao_sistema = :versao_sistema
        WHERE id_solicitacao = :id");

        $stmt->execute([
            ':id' => $id,
            ':prazo_resposta' => $data['prazo_resposta'] ?? null,
            ':precificacao' => $data['precificacao'] ?? null,
            ':observacoes_resposta' => $data['observacoes_resposta'] ?? null,
            ':aprovacao_resposta' => $data['aprovacao_resposta'] ?? null,
            ':data_resposta' => $data['data_resposta'] ?? null,
            ':status' => $data['status'] ?? 'Aberto',
            ':resolvido_em' => $data['resolvido_em'] ?? null,
            ':resolvido_por' => $data['resolvido_por'] ?? null,
            ':versao_sistema' => $data['versao_sistema'] ?? null,
        ]);
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->conn->prepare("SELECT * FROM tb_solicitacao_customizacao WHERE id_solicitacao = :id");
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) return null;

        $modulos = $this->conn->prepare("SELECT modulo, modulo_outro FROM tb_solicitacao_customizacao_modulo WHERE fk_solicitacao = :id");
        $modulos->bindValue(':id', $id, PDO::PARAM_INT);
        $modulos->execute();

        $tipos = $this->conn->prepare("SELECT tipo FROM tb_solicitacao_customizacao_tipo WHERE fk_solicitacao = :id");
        $tipos->bindValue(':id', $id, PDO::PARAM_INT);
        $tipos->execute();

        $anexos = $this->conn->prepare("SELECT * FROM tb_solicitacao_customizacao_anexo WHERE fk_solicitacao = :id ORDER BY id_anexo DESC");
        $anexos->bindValue(':id', $id, PDO::PARAM_INT);
        $anexos->execute();

        return [
            'solicitacao' => $this->build($row),
            'modulos' => $modulos->fetchAll(PDO::FETCH_ASSOC) ?: [],
            'tipos' => $tipos->fetchAll(PDO::FETCH_ASSOC) ?: [],
            'anexos' => $anexos->fetchAll(PDO::FETCH_ASSOC) ?: [],
        ];
    }

    public function findAll(array $filters = []): array
    {
        $where = "1=1";
        $params = [];
        if (!empty($filters['status'])) {
            $where .= " AND status = :status";
            $params[':status'] = $filters['status'];
        }
        if (!empty($filters['prioridade'])) {
            $where .= " AND prioridade = :prioridade";
            $params[':prioridade'] = $filters['prioridade'];
        }
        if (!empty($filters['data_ini'])) {
            $where .= " AND data_solicitacao >= :data_ini";
            $params[':data_ini'] = $filters['data_ini'];
        }
        if (!empty($filters['data_fim'])) {
            $where .= " AND data_solicitacao <= :data_fim";
            $params[':data_fim'] = $filters['data_fim'];
        }
        if (!empty($filters['busca'])) {
            $where .= " AND (nome LIKE :busca OR empresa LIKE :busca OR email LIKE :busca)";
            $params[':busca'] = '%' . $filters['busca'] . '%';
        }

        $stmt = $this->conn->prepare("SELECT * FROM tb_solicitacao_customizacao WHERE {$where} ORDER BY id_solicitacao DESC");
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, PDO::PARAM_STR);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function deleteAnexo(int $idAnexo, int $solicitacaoId): void
    {
        $stmt = $this->conn->prepare("SELECT arquivo FROM tb_solicitacao_customizacao_anexo WHERE id_anexo = :id AND fk_solicitacao = :fk");
        $stmt->bindValue(':id', $idAnexo, PDO::PARAM_INT);
        $stmt->bindValue(':fk', $solicitacaoId, PDO::PARAM_INT);
        $stmt->execute();
        $arquivo = $stmt->fetchColumn();

        $del = $this->conn->prepare("DELETE FROM tb_solicitacao_customizacao_anexo WHERE id_anexo = :id AND fk_solicitacao = :fk");
        $del->bindValue(':id', $idAnexo, PDO::PARAM_INT);
        $del->bindValue(':fk', $solicitacaoId, PDO::PARAM_INT);
        $del->execute();

        if ($arquivo && file_exists($arquivo)) {
            @unlink($arquivo);
        }
    }

    private function syncModulos(int $id, array $modulos): void
    {
        $del = $this->conn->prepare("DELETE FROM tb_solicitacao_customizacao_modulo WHERE fk_solicitacao = :id");
        $del->bindValue(':id', $id, PDO::PARAM_INT);
        $del->execute();

        if (!$modulos) return;
        $stmt = $this->conn->prepare("INSERT INTO tb_solicitacao_customizacao_modulo (fk_solicitacao, modulo, modulo_outro) VALUES (:fk, :modulo, :outro)");
        foreach ($modulos as $mod) {
            $stmt->execute([
                ':fk' => $id,
                ':modulo' => $mod['modulo'] ?? null,
                ':outro' => $mod['modulo_outro'] ?? null,
            ]);
        }
    }

    private function syncTipos(int $id, array $tipos): void
    {
        $del = $this->conn->prepare("DELETE FROM tb_solicitacao_customizacao_tipo WHERE fk_solicitacao = :id");
        $del->bindValue(':id', $id, PDO::PARAM_INT);
        $del->execute();

        if (!$tipos) return;
        $stmt = $this->conn->prepare("INSERT INTO tb_solicitacao_customizacao_tipo (fk_solicitacao, tipo) VALUES (:fk, :tipo)");
        foreach ($tipos as $tipo) {
            $stmt->execute([
                ':fk' => $id,
                ':tipo' => $tipo,
            ]);
        }
    }

    private function insertAnexos(int $id, array $anexos): void
    {
        if (!$anexos) return;
        $stmt = $this->conn->prepare("INSERT INTO tb_solicitacao_customizacao_anexo
            (fk_solicitacao, tipo, nome_original, arquivo, mime, tamanho)
            VALUES (:fk, :tipo, :nome_original, :arquivo, :mime, :tamanho)");
        foreach ($anexos as $anexo) {
            $stmt->execute([
                ':fk' => $id,
                ':tipo' => $anexo['tipo'] ?? null,
                ':nome_original' => $anexo['nome_original'] ?? null,
                ':arquivo' => $anexo['arquivo'] ?? null,
                ':mime' => $anexo['mime'] ?? null,
                ':tamanho' => $anexo['tamanho'] ?? null,
            ]);
        }
    }
}
