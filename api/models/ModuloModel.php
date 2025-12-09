<?php
class ModuloModel {

    public static function listarPorCurso($cursoId) {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM modulos WHERE id_curso = ? ORDER BY ordem");
        $stmt->execute([$cursoId]);
        return $stmt->fetchAll();
    }
}
