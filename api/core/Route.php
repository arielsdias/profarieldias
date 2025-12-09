<?php
class Router {

    private $routes = [];

    public function add($method, $route, $handler) {
        $this->routes[] = compact("method", "route", "handler");
    }

    public function dispatch(Request $req) {
        foreach ($this->routes as $r) {
            if ($req->method === $r["method"] && $req->path === $r["route"]) {
                return call_user_func($r["handler"], $req);
            }
        }

        Response::error("Endpoint não encontrado", 404);
    }
}
