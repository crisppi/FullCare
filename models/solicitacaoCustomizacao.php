<?php

class SolicitacaoCustomizacao
{
    public $id_solicitacao;
    public $nome;
    public $empresa;
    public $cargo;
    public $email;
    public $telefone;
    public $data_solicitacao;
    public $descricao;
    public $problema_atual;
    public $resultado_esperado;
    public $impacto_nivel;
    public $descricao_impacto;
    public $prioridade;
    public $prazo_desejado;
    public $responsavel;
    public $assinatura;
    public $data_aprovacao;
    public $aprovacao_conex;
    public $prazo_resposta;
    public $precificacao;
    public $observacoes_resposta;
    public $aprovacao_resposta;
    public $data_resposta;
    public $status;
    public $resolvido_em;
    public $resolvido_por;
    public $versao_sistema;
    public $data_create;
    public $data_update;
    public $usuario_create;
}

interface SolicitacaoCustomizacaoDAOInterface
{
    public function create(SolicitacaoCustomizacao $solicitacao, array $modulos, array $tipos, array $anexos): int;
    public function update(SolicitacaoCustomizacao $solicitacao, array $modulos, array $tipos, array $anexos): void;
    public function updateResposta(int $id, array $data): void;
    public function findById(int $id): ?array;
    public function findAll(array $filters = []): array;
}
