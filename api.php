<?php
header("Content-Type: application/json; charset=UTF-8");

// Configuração do banco
$servername = "projetodb.mysql.uhserver.com";
$username   = "arieldias";
$password   = "pbvd@20ug";
$dbname     = "projetodb";

// Conexão
$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die(json_encode(["erro" => "Falha na conexão: " . $conn->connect_error]));
}

// Captura a ação
$acao = $_GET['acao'] ?? '';

switch ($acao) {
    // =========================
    // LISTAR FILMES
    // =========================
    case "listar_filmes":
        $sql = "SELECT * FROM filmes";
        $result = $conn->query($sql);

        $filmes = [];
        while ($row = $result->fetch_assoc()) {
            $filmes[] = $row;
        }
        echo json_encode($filmes);
        break;

    // =========================
    // INSERIR FILME
    // =========================
    case "inserir_filme":
        if ($_SERVER['REQUEST_METHOD'] == "POST") {
            $titulo = $_POST['titulo'] ?? '';
            $ano    = $_POST['ano'] ?? 0;
            $genero = $_POST['genero'] ?? '';

            $sql = "INSERT INTO filmes (titulo, ano, genero) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sis", $titulo, $ano, $genero);

            if ($stmt->execute()) {
                echo json_encode(["sucesso" => "Filme inserido!"]);
            } else {
                echo json_encode(["erro" => "Falha ao inserir filme"]);
            }
        }
        break;

    // =========================
    // ATUALIZAR FILME
    // =========================
    case "atualizar_filme":
        if ($_SERVER['REQUEST_METHOD'] == "POST") {
            $id     = $_POST['id'] ?? 0;
            $titulo = $_POST['titulo'] ?? '';
            $ano    = $_POST['ano'] ?? 0;
            $genero = $_POST['genero'] ?? '';

            $sql = "UPDATE filmes SET titulo=?, ano=?, genero=? WHERE id=?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sisi", $titulo, $ano, $genero, $id);

            if ($stmt->execute()) {
                echo json_encode(["sucesso" => "Filme atualizado!"]);
            } else {
                echo json_encode(["erro" => "Falha ao atualizar filme"]);
            }
        }
        break;

    // =========================
    // DELETAR FILME
    // =========================
    case "deletar_filme":
        if ($_SERVER['REQUEST_METHOD'] == "POST") {
            $id = $_POST['id'] ?? 0;

            $sql = "DELETE FROM filmes WHERE id=?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $id);

            if ($stmt->execute()) {
                echo json_encode(["sucesso" => "Filme deletado!"]);
            } else {
                echo json_encode(["erro" => "Falha ao deletar filme"]);
            }
        }
        break;

    // =========================
    // AÇÃO INVÁLIDA
    // =========================
    default:
        echo json_encode(["erro" => "Ação inválida"]);
        break;
}

$conn->close();
?>
