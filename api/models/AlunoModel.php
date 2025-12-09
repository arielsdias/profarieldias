<?php

require_once __DIR__ . '/../core/Database.php';

class AlunoModel
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function getAll()
    {
        $sql = "SELECT id_aluno, nome, email, data_cadastro FROM aluno";
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById($id)
    {
        $sql = "SELECT id_aluno, nome, email, data_cadastro FROM aluno WHERE id_aluno = ?";
        $stm = $this->db->prepare($sql);
        $stm->execute([$id]);
        return $stm->fetch(PDO::FETCH_ASSOC);
    }

    public function insert($nome, $email, $senhaHash)
    {
        $sql = "INSERT INTO aluno (nome, email, senha_hash) VALUES (?, ?, ?)";
        $stm = $this->db->prepare($sql);
        $stm->execute([$nome, $email, $senhaHash]);
        return $this->db->lastInsertId();
    }

    public function update($id, $dados)
    {
        $sql = "UPDATE aluno SET nome = ?, email = ? WHERE id_aluno = ?";
        $stm = $this->db->prepare($sql);
        $stm->execute([
            $dados["nome"] ?? null,
            $dados["email"] ?? null,
            $id
        ]);
    }

    public function delete($id)
    {
        $sql = "DELETE FROM aluno WHERE id_aluno = ?";
        $stm = $this->db->prepare($sql);
        $stm->execute([$id]);
    }
}
