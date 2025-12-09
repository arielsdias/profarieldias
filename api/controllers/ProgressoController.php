<?php
class ProgressoController {

    public static function listar($req) {
        $alunoId = $req->body["aluno_id"] ?? null;
        $cursoId = $req->body["curso_id"] ?? null;

        if (!$alunoId || !$cursoId) Response::error("aluno_id e curso_id obrigatórios");

        Response::json(ProgressoModel::progressoTopicos($alunoId, $cursoId));
    }

    public static function concluirTopico($req) {
        $alunoId = $req->body["aluno_id"] ?? null;
        $topicoId = $req->body["topico_id"] ?? null;

        if (!$alunoId || !$topicoId) Response::error("aluno_id e topico_id obrigatórios");

        $ok = ProgressoModel::marcarTopicoConcluido($alunoId, $topicoId);

        Response::json(["success" => $ok]);
    }
}
