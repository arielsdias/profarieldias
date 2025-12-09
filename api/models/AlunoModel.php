<?php
class AlunoModel {

    public static function buscarPorEmail($email) {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM aluno WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetch();
    }
}
