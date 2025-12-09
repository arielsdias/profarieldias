<?php

class Router {

    private $routes = [];

    public function add($method, $path, $handler) {
        $this->routes[] = [
            'method' => strtoupper($method),
            'path' => $path,
            'handler' => $handler
        ];
    }

    public function get($path, $handler) {
        $this->add('GET', $path, $handler);
    }

    public function post($path, $handler) {
        $this->add('POST', $path, $handler);
    }

    public function put($path, $handler) {
        $this->add('PUT', $path, $handler);
    }

    public function delete($path, $handler) {
        $this->add('DELETE', $path, $handler);
    }

    public function dispatch($requestUri, $requestMethod) {

        $requestMethod = strtoupper($requestMethod);

        foreach ($this->routes as $route) {

            // Verifica se o método bate (GET/POST/etc)
            if ($route['method'] !== $requestMethod) {
                continue;
            }

            // Converte rotas com parâmetros para regex
            $pattern = preg_replace('#\{([a-zA-Z_]+)\}#', '(?P<$1>[0-9]+)', $route['path']);
            $pattern = "#^" . $pattern . "$#";

            if (preg_match($pattern, $requestUri, $matches)) {

                // Mantém apenas parâmetros nomeados
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);

                return call_user_func_array($route['handler'], $params);
            }
        }

        http_response_code(404);
        echo json_encode(["error" => "Endpoint não encontrado"]);
    }
}
