<?php

require_once __DIR__ . '/../models/AlunoModel.php';

class AlunoController
{
    private $alunoModel;

    public function __construct()
    {
        $this->alunoModel = new Aluno();
    }

    // GET /v1/alunos
    public function listar()
    {
        $alunos = $this->alunoModel->getAll();
        echo json_encode($alunos);
    }

    // GET /v1/alunos/{id}
    public function buscar($id)
    {
        $aluno = $this->alunoModel->getById($id);
        echo json_encode($aluno);
    }

    // POST /v1/alunos
    public function criar()
    {
        $data = json_decode(file_get_contents("php://input"), true);

        if (!$data || empty($data["nome"]) || empty($data["email"]) || empty($data["senha"])) {
            http_response_code(400);
            echo json_encode(["erro" => "Dados inválidos"]);
            return;
        }

        // Gera hash seguro da senha
        $hash = password_hash($data["senha"], PASSWORD_BCRYPT);

        $novoId = $this->alunoModel->insert($data["nome"], $data["email"], $hash);

        echo json_encode([
            "mensagem" => "Aluno cadastrado com sucesso",
            "id_aluno" => $novoId
        ]);
    }

    // PUT /v1/alunos/{id}
    public function atualizar($id)
    {
        $data = json_decode(file_get_contents("php://input"), true);
        if (!$data) {
            http_response_code(400);
            echo json_encode(["erro" => "JSON inválido"]);
            return;
        }

        $this->alunoModel->update($id, $data);
        echo json_encode(["mensagem" => "Aluno atualizado"]);
    }

    // DELETE /v1/alunos/{id}
    public function deletar($id)
    {
        $this->alunoModel->delete($id);
        echo json_encode(["mensagem" => "Aluno removido"]);
    }
}
