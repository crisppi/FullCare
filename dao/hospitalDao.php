<?php

require_once("./models/hospital.php");
require_once("./models/message.php");

class HospitalDAO implements HospitalDAOInterface
{
    private $conn; // sem tipo
    private $url;  // sem tipo
    public $message;

    public function __construct(PDO $conn, $url)
    {
        $this->conn = $conn;
        $this->url  = $url;
        $this->message = new Message($url);

        // Força fetch associativo neste DAO
        $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    }

    public function buildHospital($data)
    {
        $hospital = new Hospital();

        $hospital->id_hospital               = isset($data["id_hospital"]) ? $data["id_hospital"] : null;
        $hospital->nome_hosp                 = isset($data["nome_hosp"]) ? $data["nome_hosp"] : null;
        $hospital->endereco_hosp             = isset($data["endereco_hosp"]) ? $data["endereco_hosp"] : null;
        $hospital->numero_hosp               = isset($data["numero_hosp"]) ? $data["numero_hosp"] : null;
        $hospital->cidade_hosp               = isset($data["cidade_hosp"]) ? $data["cidade_hosp"] : null;
        $hospital->estado_hosp               = isset($data["estado_hosp"]) ? $data["estado_hosp"] : null;
        $hospital->cnpj_hosp                 = isset($data["cnpj_hosp"]) ? $data["cnpj_hosp"] : null;
        $hospital->email01_hosp              = isset($data["email01_hosp"]) ? $data["email01_hosp"] : null;
        $hospital->email02_hosp              = isset($data["email02_hosp"]) ? $data["email02_hosp"] : null;
        $hospital->telefone01_hosp           = isset($data["telefone01_hosp"]) ? $data["telefone01_hosp"] : null;
        $hospital->telefone02_hosp           = isset($data["telefone02_hosp"]) ? $data["telefone02_hosp"] : null;
        $hospital->bairro_hosp               = isset($data["bairro_hosp"]) ? $data["bairro_hosp"] : null;
        $hospital->fk_usuario_hosp           = isset($data["fk_usuario_hosp"]) ? $data["fk_usuario_hosp"] : null;
        $hospital->usuario_create_hosp       = isset($data["usuario_create_hosp"]) ? $data["usuario_create_hosp"] : null;
        $hospital->data_create_hosp          = isset($data["data_create_hosp"]) ? $data["data_create_hosp"] : null;
        $hospital->longitude_hosp            = isset($data["longitude_hosp"]) ? $data["longitude_hosp"] : null;
        $hospital->latitude_hosp             = isset($data["latitude_hosp"]) ? $data["latitude_hosp"] : null;
        $hospital->coordenador_medico_hosp   = isset($data["coordenador_medico_hosp"]) ? $data["coordenador_medico_hosp"] : null;
        $hospital->diretor_hosp              = isset($data["diretor_hosp"]) ? $data["diretor_hosp"] : null;
        $hospital->ativo_hosp                = isset($data["ativo_hosp"]) ? $data["ativo_hosp"] : null;
        $hospital->coordenador_fat_hosp      = isset($data["coordenador_fat_hosp"]) ? $data["coordenador_fat_hosp"] : null;
        $hospital->logo_hosp                 = isset($data["logo_hosp"]) ? $data["logo_hosp"] : null;
        $hospital->cep_hosp                  = isset($data["cep_hosp"]) ? $data["cep_hosp"] : null;
        $hospital->deletado_hosp             = isset($data["deletado_hosp"]) ? $data["deletado_hosp"] : null;

        return $hospital;
    }

    private function normalizeRole($txt)
    {
        $txt = trim((string)$txt);
        if ($txt === '') {
            return '';
        }
        if (function_exists('iconv')) {
            $conv = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $txt);
            if ($conv !== false) {
                $txt = $conv;
            }
        }
        $txt = strtolower($txt);
        return preg_replace('/[^a-z]/', '', $txt);
    }

    private function startsWithAny($value, array $prefixes)
    {
        foreach ($prefixes as $prefix) {
            if ($prefix !== '' && strpos($value, $prefix) === 0) {
                return true;
            }
        }
        return false;
    }

    private function getScopeContext()
    {
        $userId = (int)($_SESSION['id_usuario'] ?? 0);
        $nivel = (int)($_SESSION['nivel'] ?? 99);
        $cargoNorm = $this->normalizeRole($_SESSION['cargo'] ?? '');
        $seguradoraId = (int)($_SESSION['fk_seguradora_user'] ?? 0);

        $isDiretoria = in_array($cargoNorm, ['diretoria', 'diretor', 'board'], true)
            || (strpos($cargoNorm, 'diretor') !== false)
            || (strpos($cargoNorm, 'diretoria') !== false)
            || ($nivel === -1);

        $isSystemAdmin = in_array($cargoNorm, [
            'adminsistema',
            'administradordesistema',
            'superadmin',
            'root',
            'tiadmin'
        ], true);

        $isSeguradoraRole = (strpos($cargoNorm, 'seguradora') !== false)
            || (strpos($cargoNorm, 'planosaude') !== false)
            || ($cargoNorm === 'gestorplanosaude');

        return [
            'user_id' => $userId,
            'cargo_norm' => $cargoNorm,
            'seguradora_id' => $seguradoraId,
            'is_diretoria' => $isDiretoria,
            'is_system_admin' => $isSystemAdmin,
            'is_seguradora' => $isSeguradoraRole || ($seguradoraId > 0),
        ];
    }

    private function resolveScopeMode(array $ctx)
    {
        if (!empty($ctx['is_diretoria']) || !empty($ctx['is_system_admin'])) {
            return 'full';
        }
        if (!empty($ctx['is_seguradora'])) {
            return 'seguradora';
        }

        $cargo = (string)($ctx['cargo_norm'] ?? '');
        $hospitalScopedPrefixes = [
            'medico',
            'med',
            'enfermeiro',
            'enf',
            'secretaria',
            'administrativo',
            'adm',
            'auditor'
        ];
        if ($this->startsWithAny($cargo, $hospitalScopedPrefixes)) {
            return 'hospital';
        }
        return 'hospital';
    }

    private function buildHospitalScopeFilter($hospitalExpr = 'tb_hospital.id_hospital')
    {
        $ctx = $this->getScopeContext();
        $mode = $this->resolveScopeMode($ctx);

        if ($mode === 'full') {
            return ['sql' => '', 'params' => []];
        }

        if ($mode === 'seguradora') {
            $seguradoraId = (int)($ctx['seguradora_id'] ?? 0);
            if ($seguradoraId <= 0) {
                return ['sql' => '1=0', 'params' => []];
            }
            return [
                'sql' => "EXISTS (
                    SELECT 1
                      FROM internacao i_scope
                      JOIN paciente p_scope ON p_scope.id_paciente = i_scope.fk_paciente_int
                     WHERE i_scope.fk_hospital_int = {$hospitalExpr}
                       AND p_scope.fk_seguradora_pac = :scope_seguradora
                )",
                'params' => [':scope_seguradora' => $seguradoraId]
            ];
        }

        $userId = (int)($ctx['user_id'] ?? 0);
        if ($userId <= 0) {
            return ['sql' => '1=0', 'params' => []];
        }

        return [
            'sql' => "EXISTS (
                SELECT 1
                  FROM tb_hospitalUser hu_scope
                 WHERE hu_scope.fk_hospital_user = {$hospitalExpr}
                   AND hu_scope.fk_usuario_hosp = :scope_user
            )",
            'params' => [':scope_user' => $userId]
        ];
    }

    private function bindNamedParams(PDOStatement $stmt, array $params)
    {
        foreach ($params as $name => $value) {
            $type = is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR;
            $stmt->bindValue($name, $value, $type);
        }
    }

    /* ================== READS (arrays associativos) ================== */

    public function findAll()
    {
        $scope = $this->buildHospitalScopeFilter('tb_hospital.id_hospital');
        $conds = ["id_hospital > 1"];
        if ($scope['sql'] !== '') {
            $conds[] = $scope['sql'];
        }
        $sql = "SELECT * FROM tb_hospital WHERE " . implode(' AND ', $conds) . " ORDER BY id_hospital DESC";
        $stmt = $this->conn->prepare($sql);
        $this->bindNamedParams($stmt, $scope['params']);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findByHosp($pesquisa_nome)
    {
        $scope = $this->buildHospitalScopeFilter('tb_hospital.id_hospital');
        $conds = ["nome_hosp LIKE :nome_hosp"];
        if ($scope['sql'] !== '') {
            $conds[] = $scope['sql'];
        }
        $sql = "SELECT * FROM tb_hospital WHERE " . implode(' AND ', $conds) . " ORDER BY nome_hosp ASC";
        $stmt = $this->conn->prepare($sql);
        $this->bindNamedParams($stmt, $scope['params']);
        $stmt->bindValue(":nome_hosp", '%' . $pesquisa_nome . '%');
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /* ================== READS que retornam MODELS ================== */

    public function gethospital()
    {
        $scope = $this->buildHospitalScopeFilter('tb_hospital.id_hospital');
        $sql = "SELECT * FROM tb_hospital";
        if ($scope['sql'] !== '') {
            $sql .= " WHERE " . $scope['sql'];
        }
        $sql .= " ORDER BY id_hospital DESC";
        $stmt = $this->conn->prepare($sql);
        $this->bindNamedParams($stmt, $scope['params']);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $out = array();
        foreach ($rows as $row) {
            $out[] = $this->buildHospital($row);
        }
        return $out;
    }

    public function gethospitalByNome($nome_hosp)
    {
        $scope = $this->buildHospitalScopeFilter('tb_hospital.id_hospital');
        $conds = ["nome_hosp = :nome_hosp"];
        if ($scope['sql'] !== '') {
            $conds[] = $scope['sql'];
        }
        $sql = "SELECT * FROM tb_hospital WHERE " . implode(' AND ', $conds) . " ORDER BY id_hospital DESC";
        $stmt = $this->conn->prepare($sql);
        $this->bindNamedParams($stmt, $scope['params']);
        $stmt->bindValue(":nome_hosp", $nome_hosp);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $out = array();
        foreach ($rows as $row) {
            $out[] = $this->buildHospital($row);
        }
        return $out;
    }

    public function findById($id_hospital)
    {
        $scope = $this->buildHospitalScopeFilter('tb_hospital.id_hospital');
        $conds = ["id_hospital = :id_hospital"];
        if ($scope['sql'] !== '') {
            $conds[] = $scope['sql'];
        }
        $sql = "SELECT * FROM tb_hospital WHERE " . implode(' AND ', $conds);
        $stmt = $this->conn->prepare($sql);
        $this->bindNamedParams($stmt, $scope['params']);
        $stmt->bindValue(":id_hospital", $id_hospital, PDO::PARAM_INT);
        $stmt->execute();
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        return $data ? $this->buildHospital($data) : null;
    }

    public function findEnderecosByHospital($id_hospital)
    {
        try {
            $stmt = $this->conn->prepare("
                SELECT *
                  FROM tb_hospital_endereco
                 WHERE fk_hospital = :id
                 ORDER BY principal_endereco DESC, id_hospital_endereco ASC
            ");
            $stmt->bindValue(":id", (int) $id_hospital, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            return [];
        }
    }

    public function findTelefonesByHospital($id_hospital)
    {
        try {
            $stmt = $this->conn->prepare("
                SELECT *
                  FROM tb_hospital_telefone
                 WHERE fk_hospital = :id
                 ORDER BY principal_telefone DESC, id_hospital_telefone ASC
            ");
            $stmt->bindValue(":id", (int) $id_hospital, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            return [];
        }
    }

    public function findContatosByHospital($id_hospital)
    {
        try {
            $stmt = $this->conn->prepare("
                SELECT *
                  FROM tb_hospital_contato
                 WHERE fk_hospital = :id
                 ORDER BY principal_contato DESC, id_hospital_contato ASC
            ");
            $stmt->bindValue(":id", (int) $id_hospital, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            return [];
        }
    }

    /* ================== CREATE / UPDATE / DELETE ================== */

    public function create(Hospital $hospital)
    {
        $stmt = $this->conn->prepare("
            INSERT INTO tb_hospital (
                nome_hosp, ativo_hosp, endereco_hosp, numero_hosp, bairro_hosp,
                cidade_hosp, estado_hosp, email01_hosp, email02_hosp, cnpj_hosp,
                telefone01_hosp, telefone02_hosp, fk_usuario_hosp, data_create_hosp,
                usuario_create_hosp, latitude_hosp, longitude_hosp, coordenador_medico_hosp,
                diretor_hosp, coordenador_fat_hosp, logo_hosp, deletado_hosp, cep_hosp
            ) VALUES (
                :nome_hosp, :ativo_hosp, :endereco_hosp, :numero_hosp, :bairro_hosp,
                :cidade_hosp, :estado_hosp, :email01_hosp, :email02_hosp, :cnpj_hosp,
                :telefone01_hosp, :telefone02_hosp, :fk_usuario_hosp, :data_create_hosp,
                :usuario_create_hosp, :latitude_hosp, :longitude_hosp, :coordenador_medico_hosp,
                :diretor_hosp, :coordenador_fat_hosp, :logo_hosp, :deletado_hosp, :cep_hosp
            )
        ");

        $stmt->bindValue(":nome_hosp", $hospital->nome_hosp);
        $stmt->bindValue(":ativo_hosp", $hospital->ativo_hosp);
        $stmt->bindValue(":endereco_hosp", $hospital->endereco_hosp);
        $stmt->bindValue(":numero_hosp", $hospital->numero_hosp);
        $stmt->bindValue(":bairro_hosp", $hospital->bairro_hosp);
        $stmt->bindValue(":cidade_hosp", $hospital->cidade_hosp);
        $stmt->bindValue(":estado_hosp", $hospital->estado_hosp);
        $stmt->bindValue(":email01_hosp", $hospital->email01_hosp);
        $stmt->bindValue(":email02_hosp", $hospital->email02_hosp);
        $stmt->bindValue(":cnpj_hosp", $hospital->cnpj_hosp);
        $stmt->bindValue(":telefone01_hosp", $hospital->telefone01_hosp);
        $stmt->bindValue(":telefone02_hosp", $hospital->telefone02_hosp);
        $stmt->bindValue(":fk_usuario_hosp", $hospital->fk_usuario_hosp);
        $stmt->bindValue(":data_create_hosp", $hospital->data_create_hosp);
        $stmt->bindValue(":usuario_create_hosp", $hospital->usuario_create_hosp);
        $stmt->bindValue(":latitude_hosp", $hospital->latitude_hosp);
        $stmt->bindValue(":longitude_hosp", $hospital->longitude_hosp);
        $stmt->bindValue(":coordenador_medico_hosp", $hospital->coordenador_medico_hosp);
        $stmt->bindValue(":diretor_hosp", $hospital->diretor_hosp);
        $stmt->bindValue(":coordenador_fat_hosp", $hospital->coordenador_fat_hosp);
        $stmt->bindValue(":logo_hosp", $hospital->logo_hosp);
        $stmt->bindValue(":deletado_hosp", $hospital->deletado_hosp);
        $stmt->bindValue(":cep_hosp", $hospital->cep_hosp);

        $stmt->execute();

        $this->message->setMessage("hospital adicionado com sucesso!", "success", "hospitais");
    }

    public function update(Hospital $hospital)
    {
        $stmt = $this->conn->prepare("
            UPDATE tb_hospital SET
                nome_hosp = :nome_hosp,
                ativo_hosp = :ativo_hosp,
                endereco_hosp = :endereco_hosp,
                numero_hosp = :numero_hosp,
                email01_hosp = :email01_hosp,
                email02_hosp = :email02_hosp,
                cnpj_hosp = :cnpj_hosp,
                telefone01_hosp = :telefone01_hosp,
                telefone02_hosp = :telefone02_hosp,
                cidade_hosp = :cidade_hosp,
                bairro_hosp = :bairro_hosp,
                latitude_hosp = :latitude_hosp,
                longitude_hosp = :longitude_hosp,
                coordenador_medico_hosp = :coordenador_medico_hosp,
                diretor_hosp = :diretor_hosp,
                estado_hosp = :estado_hosp,
                coordenador_fat_hosp = :coordenador_fat_hosp,
                logo_hosp = :logo_hosp,
                cep_hosp = :cep_hosp
            WHERE id_hospital = :id_hospital
        ");

        $stmt->bindValue(":nome_hosp", $hospital->nome_hosp);
        $stmt->bindValue(":ativo_hosp", $hospital->ativo_hosp);
        $stmt->bindValue(":endereco_hosp", $hospital->endereco_hosp);
        $stmt->bindValue(":numero_hosp", $hospital->numero_hosp);
        $stmt->bindValue(":email01_hosp", $hospital->email01_hosp);
        $stmt->bindValue(":email02_hosp", $hospital->email02_hosp);
        $stmt->bindValue(":cnpj_hosp", $hospital->cnpj_hosp);
        $stmt->bindValue(":telefone01_hosp", $hospital->telefone01_hosp);
        $stmt->bindValue(":telefone02_hosp", $hospital->telefone02_hosp);
        $stmt->bindValue(":cidade_hosp", $hospital->cidade_hosp);
        $stmt->bindValue(":bairro_hosp", $hospital->bairro_hosp);
        $stmt->bindValue(":estado_hosp", $hospital->estado_hosp);
        $stmt->bindValue(":latitude_hosp", $hospital->latitude_hosp);
        $stmt->bindValue(":longitude_hosp", $hospital->longitude_hosp);
        $stmt->bindValue(":coordenador_medico_hosp", $hospital->coordenador_medico_hosp);
        $stmt->bindValue(":diretor_hosp", $hospital->diretor_hosp);
        $stmt->bindValue(":coordenador_fat_hosp", $hospital->coordenador_fat_hosp);
        $stmt->bindValue(":logo_hosp", $hospital->logo_hosp);
        $stmt->bindValue(":cep_hosp", $hospital->cep_hosp);
        $stmt->bindValue(":id_hospital", $hospital->id_hospital, PDO::PARAM_INT);

        $stmt->execute();

        $this->message->setMessage("hospital atualizado com sucesso!", "success", "hospitais");
    }

    public function deletarUpdate(Hospital $hospital)
    {
        $stmt = $this->conn->prepare("
            UPDATE tb_hospital SET
                deletado_hosp = :deletado_hosp
            WHERE id_hospital = :id_hospital
        ");
        $stmt->bindValue(":deletado_hosp", $hospital->deletado_hosp);
        $stmt->bindValue(":id_hospital", $hospital->id_hospital, PDO::PARAM_INT);
        $stmt->execute();

        $this->message->setMessage("hospital atualizado com sucesso!", "success", "hospitais");
    }

    public function destroy($id_hospital)
    {
        $stmt = $this->conn->prepare("
            DELETE FROM tb_hospital
            WHERE id_hospital = :id_hospital
        ");
        $stmt->bindValue(":id_hospital", $id_hospital, PDO::PARAM_INT);
        $stmt->execute();

        $this->message->setMessage("hospital removido com sucesso!", "success", "hospitais");
    }

    /* ============== LISTAGENS AVANÇADAS (associativas) ============== */

    public function findGeral()
    {
        $scope = $this->buildHospitalScopeFilter('tb_hospital.id_hospital');
        $conds = ["id_hospital > 1", "deletado_hosp <> 's'"];
        if ($scope['sql'] !== '') {
            $conds[] = $scope['sql'];
        }
        $sql = "SELECT * FROM tb_hospital WHERE " . implode(' AND ', $conds) . " ORDER BY nome_hosp ASC";
        $stmt = $this->conn->prepare($sql);
        $this->bindNamedParams($stmt, $scope['params']);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function selectAllhospital($where = null, $order = null, $limit = null)
    {
        $scope = $this->buildHospitalScopeFilter('tb_hospital.id_hospital');
        $conds = array();
        if (strlen((string)$where)) $conds[] = $where;
        $conds[] = "deletado_hosp <> 's'";
        if ($scope['sql'] !== '') $conds[] = $scope['sql'];

        $sql = "SELECT * FROM tb_hospital";
        if (!empty($conds))  $sql .= " WHERE " . implode(' AND ', $conds);
        if (strlen((string)$order)) $sql .= " ORDER BY " . $order;
        if (strlen((string)$limit)) $sql .= " LIMIT " . $limit;

        $stmt = $this->conn->prepare($sql);
        $this->bindNamedParams($stmt, $scope['params']);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function selectAllhospitalComDeletados($where = null, $order = null, $limit = null)
    {
        $scope = $this->buildHospitalScopeFilter('tb_hospital.id_hospital');
        $conds = array();
        if (strlen((string)$where)) $conds[] = $where;
        if ($scope['sql'] !== '') $conds[] = $scope['sql'];

        $sql = "SELECT * FROM tb_hospital";
        if (!empty($conds)) $sql .= " WHERE " . implode(' AND ', $conds);
        if (strlen((string)$order)) $sql .= " ORDER BY " . $order;
        if (strlen((string)$limit)) $sql .= " LIMIT " . $limit;

        $stmt = $this->conn->prepare($sql);
        $this->bindNamedParams($stmt, $scope['params']);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function QtdHospital($where = null, $order = null, $limite = null)
    {
        $scope = $this->buildHospitalScopeFilter('tb_hospital.id_hospital');
        $conds = array();
        if (strlen((string)$where))  $conds[] = $where;
        if ($scope['sql'] !== '') $conds[] = $scope['sql'];

        $sql = "SELECT COUNT(id_hospital) AS qtd FROM tb_hospital";
        if (!empty($conds)) $sql .= " WHERE " . implode(' AND ', $conds);
        if (strlen((string)$order))  $sql .= " ORDER BY " . $order;
        if (strlen((string)$limite)) $sql .= " LIMIT " . $limite;

        $stmt = $this->conn->prepare($sql);
        $this->bindNamedParams($stmt, $scope['params']);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? $row : array('qtd' => 0);
    }
}
