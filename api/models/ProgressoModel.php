<?php
class ProgressoModel {

    public static function progressoTopicos($alunoId, $cursoId) {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT t.id_topico, apt.concluido
            FROM aluno_progresso_topico apt
            INNER JOIN aluno_progresso_modulo apm ON apt.id_progresso_modulo = apm.id_progresso_modulo
            INNER JOIN aluno_curso ac ON apm.id_matricula = ac.id_matricula
            INNER JOIN topicos t ON apt.id_topico = t.id_topico
            WHERE ac.id_aluno = ? AND ac.id_curso = ?
        ");
        $stmt->execute([$alunoId, $cursoId]);
        return $stmt->fetchAll();
    }

    public static function marcarTopicoConcluido($alunoId, $topicoId) {
        $db = Database::getConnection();

        // Recuperar matrícula & progresso do módulo
        $stmt = $db->prepare("
            SELECT apm.id_progresso_modulo
            FROM aluno_progresso_modulo apm
            INNER JOIN aluno_curso ac ON apm.id_matricula = ac.id_matricula
            INNER JOIN topicos t ON apm.id_modulo = t.id_modulo
            WHERE ac.id_aluno = ? AND t.id_topico = ?
        ");
        $stmt->execute([$alunoId, $topicoId]);
        $row = $stmt->fetch();

        if (!$row) return false;

        $idProgresso = $row["id_progresso_modulo"];

        $stmt2 = $db->prepare("
            INSERT INTO aluno_progresso_topico (id_progresso_modulo, id_topico, concluido, data_conclusao)
            VALUES (?, ?, 1, NOW())
            ON DUPLICATE KEY UPDATE concluido = 1, data_conclusao = NOW()
        ");
        return $stmt2->execute([$idProgresso, $topicoId]);
    }
}
