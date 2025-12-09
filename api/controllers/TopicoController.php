<?php
class TopicoController {

    public static function listar($req) {
        $moduloId = $req->body["modulo_id"] ?? null;
        if (!$moduloId) Response::error("modulo_id requerido");

        Response::json(TopicoModel::listarPorModulo($moduloId));
    }

    public static function detalhes($req) {
        $topicoId = $req->body["topico_id"] ?? null;
        if (!$topicoId) Response::error("topico_id requerido");

        Response::json(TopicoModel::detalhes($topicoId));
    }
}
