<?php
class TopicoModel {

    public static function listarPorModulo($moduloId) {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM topicos WHERE id_modulo = ? ORDER BY ordem");
        $stmt->execute([$moduloId]);
        return $stmt->fetchAll();
    }

    public static function detalhes($topicoId) {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT t.*, tt.link_video, tt.link_gamma, tp.problem_statement, tp.resposta_esperada, tp.input_interativo
            FROM topicos t
            LEFT JOIN topicos_teoria tt ON t.id_topico = tt.id_topico
            LEFT JOIN topicos_pratica tp ON t.id_topico = tp.id_topico
            WHERE t.id_topico = ?
        ");
        $stmt->execute([$topicoId]);
        return $stmt->fetch();
    }
}
