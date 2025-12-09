<?php
class CursoController {

    public static function listar() {
        Response::json(CursoModel::listar());
    }

    public static function detalhes($req) {
        $id = $req->body["id"] ?? null;
        if (!$id) Response::error("ID obrigatório");
        Response::json(CursoModel::buscar($id));
    }
}
