<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, OPTIONS");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit;

// 🔑 Token fixo de segurança
$TOKEN_CORRETO = "meuTokenSeguro123";

// Recebe o corpo JSON
$body = json_decode(file_get_contents("php://input"), true);

// Validação do token
$headers = getallheaders();
$tokenRecebido = "";

if (isset($headers['Authorization'])) {
    $tokenRecebido = trim(str_replace("Bearer", "", $headers['Authorization']));
} elseif (isset($body['token'])) {
    $tokenRecebido = trim($body['token']);
}

if ($tokenRecebido !== $TOKEN_CORRETO) {
    http_response_code(403);
    echo json_encode(["erro" => "Acesso negado. Token inválido."]);
    exit;
}

// 🔹 Verifica campos obrigatórios
if (!isset($body['banco']) || !isset($body['usuario']) || !isset($body['query'])) {
    http_response_code(400);
    echo json_encode(["erro" => "Campos obrigatórios: banco, usuario, query."]);
    exit;
}

// 🔹 Conexão dinâmica
$host = trim($body['usuario']) . ".mysql.uhserver.com"; // ✅ aqui está a forma correta
$banco = trim($body['banco']);
$usuario = trim($body['usuario']);
$senha = "lyca@20132019";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$banco;charset=utf8", $usuario, $senha);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["erro" => "Falha na conexão com o banco: " . $e->getMessage()]);
    exit;
}

// 🔹 Executa a query
$query = trim($body['query']);
$tipo = strtoupper(substr($query, 0, 6));

// Filtro de segurança básico
$proibidas = ['DROP', 'ALTER', 'TRUNCATE', 'GRANT', 'REVOKE', 'CREATE USER'];
foreach ($proibidas as $palavra) {
    if (stripos($query, $palavra) !== false) {
        http_response_code(403);
        echo json_encode(["erro" => "Comando '$palavra' não permitido por motivos de segurança."]);
        exit;
    }
}

try {
    if ($tipo === 'SELECT') {
        $stmt = $pdo->query($query);
        $dados = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(["status" => "ok", "dados" => $dados]);
    } elseif (in_array($tipo, ['INSERT', 'UPDATE', 'DELETE'])) {
        $stmt = $pdo->prepare($query);
        $stmt->execute();
        echo json_encode(["status" => "ok", "linhas_afetadas" => $stmt->rowCount()]);
    } else {
        echo json_encode(["erro" => "Comando SQL não reconhecido ou não permitido."]);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "erro" => "Erro ao executar SQL.",
        "detalhe" => $e->getMessage()
    ]);
}
?>
