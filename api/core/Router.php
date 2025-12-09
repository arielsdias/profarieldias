<?php

class Router
{
    private $routes = [
        "GET" => [],
        "POST" => [],
        "DELETE" => [],
        "PATCH" => []
    ];

    // ======================
    // REGISTRO DE ROTAS
    // ======================
    public function get($path, $handler)
    {
        $this->routes["GET"][] = ["path" => $path, "handler" => $handler];
    }

    public function post($path, $handler)
    {
        $this->routes["POST"][] = ["path" => $path, "handler" => $handler];
    }

    public function delete($path, $handler)
    {
        $this->routes["DELETE"][] = ["path" => $path, "handler" => $handler];
    }

    public function patch($path, $handler)
    {
        $this->routes["PATCH"][] = ["path" => $path, "handler" => $handler];
    }

    // ======================
    // EXECUTAR A ROTA
    // ======================
    public function run()
    {
        $method = $_SERVER["REQUEST_METHOD"];
        $uri = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);

        // Remove /profarieldias/api/public da URL (ajuste do subdiretório)
        $base = "/profarieldias/api/public";
        if (str_starts_with($uri, $base)) {
            $uri = substr($uri, strlen($base));
        }

        foreach ($this->routes[$method] as $route) {

            $pattern = preg_replace('#\{[^/]+\}#', '([^/]+)', $route["path"]);
            $pattern = "#^" . $pattern . "$#";

            if (preg_match($pattern, $uri, $matches)) {
                array_shift($matches);

                [$controller, $methodName] = $route["handler"];
                $instance = new $controller;

                return call_user_func_array([$instance, $methodName], $matches);
            }
        }

        // ROTA NÃO ENCONTRADA
        http_response_code(404);
        echo json_encode(["error" => "Endpoint não encontrado"]);
    }
}
