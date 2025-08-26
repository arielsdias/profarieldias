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

// Captura a entidade (filmes, atores ou elenco) e ação (listar, inserir, atualizar, deletar)
$entidade = $_GET['entidade'] ?? '';
$acao     = $_GET['acao'] ?? '';

switch ($entidade) {
    // =========================
    // FILMES
    // =========================
    case "filmes":
        if ($acao == "listar") {
            $sql = "SELECT * FROM filmes";
            $result = $conn->query($sql);
            $dados = [];
            while ($row = $result->fetch_assoc()) {
                $dados[] = $row;
            }
            echo json_encode($dados);
        }
        elseif ($acao == "inserir" && $_SERVER['REQUEST_METHOD'] == "POST") {
            $titulo = $_POST['titulo'] ?? '';
            $ano    = $_POST['ano'] ?? 0;
            $genero = $_POST['genero'] ?? '';

            $sql = "INSERT INTO filmes (titulo, ano, genero) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sis", $titulo, $ano, $genero);

            echo $stmt->execute() 
                ? json_encode(["sucesso" => "Filme inserido!"]) 
                : json_encode(["erro" => "Falha ao inserir filme"]);
        }
        elseif ($acao == "atualizar" && $_SERVER['REQUEST_METHOD'] == "POST") {
            $id     = $_POST['id'] ?? 0;
            $titulo = $_POST['titulo'] ?? '';
            $ano    = $_POST['ano'] ?? 0;
            $genero = $_POST['genero'] ?? '';

            $sql = "UPDATE filmes SET titulo=?, ano=?, genero=? WHERE id=?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sisi", $titulo, $ano, $genero, $id);

            echo $stmt->execute() 
                ? json_encode(["sucesso" => "Filme atualizado!"]) 
                : json_encode(["erro" => "Falha ao atualizar filme"]);
        }
        elseif ($acao == "deletar" && $_SERVER['REQUEST_METHOD'] == "POST") {
            $id = $_POST['id'] ?? 0;
            $sql = "DELETE FROM filmes WHERE id=?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $id);

            echo $stmt->execute() 
                ? json_encode(["sucesso" => "Filme deletado!"]) 
                : json_encode(["erro" => "Falha ao deletar filme"]);
        }
        break;

    // =========================
    // ATORES
    // =========================
    case "atores":
        if ($acao == "listar") {
            $sql = "SELECT * FROM atores";
            $result = $conn->query($sql);
            $dados = [];
            while ($row = $result->fetch_assoc()) {
                $dados[] = $row;
            }
            echo json_encode($dados);
        }
        elseif ($acao == "inserir" && $_SERVER['REQUEST_METHOD'] == "POST") {
            $nome         = $_POST['nome'] ?? '';
            $nacionalidade= $_POST['nacionalidade'] ?? '';

            $sql = "INSERT INTO atores (nome, nacionalidade) VALUES (?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ss", $nome, $nacionalidade);

            echo $stmt->execute() 
                ? json_encode(["sucesso" => "Ator inserido!"]) 
                : json_encode(["erro" => "Falha ao inserir ator"]);
        }
        elseif ($acao == "atualizar" && $_SERVER['REQUEST_METHOD'] == "POST") {
            $id            = $_POST['id'] ?? 0;
            $nome          = $_POST['nome'] ?? '';
            $nacionalidade = $_POST['nacionalidade'] ?? '';

            $sql = "UPDATE atores SET nome=?, nacionalidade=? WHERE id=?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssi", $nome, $nacionalidade, $id);

            echo $stmt->execute() 
                ? json_encode(["sucesso" => "Ator atualizado!"]) 
                : json_encode(["erro" => "Falha ao atualizar ator"]);
        }
        elseif ($acao == "deletar" && $_SERVER['REQUEST_METHOD'] == "POST") {
            $id = $_POST['id'] ?? 0;
            $sql = "DELETE FROM atores WHERE id=?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $id);

            echo $stmt->execute() 
                ? json_encode(["sucesso" => "Ator deletado!"]) 
                : json_encode(["erro" => "Falha ao deletar ator"]);
        }
        break;

    // =========================
    // ELENCO
    // =========================
    case "elenco":
        if ($acao == "listar") {
            $sql = "SELECT elenco.id, filmes.titulo, atores.nome, elenco.personagem
                    FROM elenco
                    JOIN filmes ON elenco.id_filme = filmes.id
                    JOIN atores ON elenco.id_ator = atores.id";
            $result = $conn->query($sql);
            $dados = [];
            while ($row = $result->fetch_assoc()) {
                $dados[] = $row;
            }
            echo json_encode($dados);
        }
        elseif ($acao == "inserir" && $_SERVER['REQUEST_METHOD'] == "POST") {
            $id_filme = $_POST['id_filme'] ?? 0;
            $id_ator  = $_POST['id_ator'] ?? 0;
            $personagem = $_POST['personagem'] ?? '';

            $sql = "INSERT INTO elenco (id_filme, id_ator, personagem) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iis", $id_filme, $id_ator, $personagem);

            echo $stmt->execute() 
                ? json_encode(["sucesso" => "Elenco inserido!"]) 
                : json_encode(["erro" => "Falha ao inserir elenco"]);
        }
        elseif ($acao == "atualizar" && $_SERVER['REQUEST_METHOD'] == "POST") {
            $id         = $_POST['id'] ?? 0;
            $id_filme   = $_POST['id_filme'] ?? 0;
            $id_ator    = $_POST['id_ator'] ?? 0;
            $personagem = $_POST['personagem'] ?? '';

            $sql = "UPDATE elenco SET id_filme=?, id_ator=?, personagem=? WHERE id=?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iisi", $id_filme, $id_ator, $personagem, $id);

            echo $stmt->execute() 
                ? json_encode(["sucesso" => "Elenco atualizado!"]) 
                : json_encode(["erro" => "Falha ao atualizar elenco"]);
        }
        elseif ($acao == "deletar" && $_SERVER['REQUEST_METHOD'] == "POST") {
            $id = $_POST['id'] ?? 0;
            $sql = "DELETE FROM elenco WHERE id=?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $id);

            echo $stmt->execute() 
                ? json_encode(["sucesso" => "Elenco deletado!"]) 
                : json_encode(["erro" => "Falha ao deletar elenco"]);
        }
        break;

    // =========================
    // DEFAULT
    // =========================
    default:
        echo json_encode(["erro" => "Entidade ou ação inválida"]);
        break;
}

$conn->close();
?>
