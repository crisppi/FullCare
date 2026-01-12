<?php
// Conexão principal (mydb_accert_ho - Hostinger)

$host4 = "2.59.150.2";
$user4 = "u650318666_diretoria10";
$pass4 = "FullCare@BD2025!";
$dbname4 = "u650318666_mydb_accert_ho";

// Conexão alternativa 1 (mydb_accert_new - UOLHOST)
$host2 = "mydb-accert-new.mysql.uhserver.com";
$user2 = "diretoria5";
$pass2 = "Fullcare12@";
$dbname2 = "mydb_accert_new";

// Conexão alternativa 2 (mydb_accert - UOLHOST)
$host3 = "mdb-accert.mysql.uhserver.com";
$user3 = "diretoria2";
$pass3 = "Guga@0401";
$dbname3 = "mydb_accert";

// Conexão alternativa 3 (Cloud SQL público)
$host1 = "35.199.123.232";
$user1 = "id-mysql-fullcare"; // atualize com o usuário do Cloud SQL
$pass1 = "CB*hND.46`an46$~"; // atualize com a senha do Cloud SQL
$dbname1 = "fullcare"; // atualize com o nome do banco de dados dentro do Cloud SQL

$charset = "utf8";
$port = 3306;
$fonte_conexao = "";
$dbConnectionStart = microtime(true);

try {
    // Tentativa com a conexão principal (Hostinger)
    $conn = new PDO("mysql:host=$host1;dbname=$dbname1;charset=$charset", $user1, $pass1);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    $fonte_conexao = "Conexao 1 ($dbname1)";
} catch (Exception $e1) {
    try {
        // Tentativa com a alternativa 1 (UOLHOST NEW)
        $conn = new PDO("mysql:host=$host2;dbname=$dbname2;charset=$charset", $user2, $pass2);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        $fonte_conexao = "UOLHOST NEW ($dbname2)";
    } catch (Exception $e2) {
        try {
            // Tentativa com a alternativa 2 (UOLHOST Fallback)
            $conn = new PDO("mysql:host=$host3;dbname=$dbname3;charset=$charset", $user3, $pass3);
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
            $fonte_conexao = "UOLHOST Fallback ($dbname3)";
        } catch (Exception $e3) {
            try {
                // Tentativa com a alternativa 3 (Cloud SQL público)
                $conn = new PDO("mysql:host=$host4;dbname=$dbname4;charset=$charset", $user4, $pass4);
                $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
                $fonte_conexao = "Cloud SQL Público ($dbname4)";
            } catch (Exception $e4) {
                header("Location: sem_conexao.html");
                exit("❌ Falha nas conexões com os bancos de dados.");
            }
        }
    }
}

$dbConnectionDurationMs = (int) round((microtime(true) - $dbConnectionStart) * 1000, 0);

try {
    $userId = $_SESSION['id_usuario'] ?? null;
    $userName = $_SESSION['usuario_user'] ?? null;
    $userEmail = $_SESSION['email_user'] ?? null;
    $ipAddr = $_SERVER['REMOTE_ADDR'] ?? null;
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;

    $stmt = $conn->prepare(
        "SET @app_user_id = :uid,
             @app_user_nome = :uname,
             @app_user_email = :uemail,
             @app_ip = :ip,
             @app_user_agent = :ua"
    );
    $stmt->bindValue(':uid', $userId, $userId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
    $stmt->bindValue(':uname', $userName);
    $stmt->bindValue(':uemail', $userEmail);
    $stmt->bindValue(':ip', $ipAddr);
    $stmt->bindValue(':ua', $userAgent);
    $stmt->execute();
} catch (Throwable $e) {
    // Se falhar, os triggers ainda registram sem contexto de usuario.
}
