<?php
class CursoModel {

    public static function listar() {
        $db = Database::getConnection();
        $sql = "SELECT * FROM curso";
        return $db->query($sql)->fetchAll();
    }

    public static function buscar($id) {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM curso WHERE id_curso = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
}
