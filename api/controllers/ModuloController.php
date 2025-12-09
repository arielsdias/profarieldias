<?php
class ModuloController {
    public static function listar($req) {
        $cursoId = $req->body["curso_id"] ?? null;
        if (!$cursoId) Response::error("curso_id requerido");

        Response::json(ModuloModel::listarPorCurso($cursoId));
    }
}
