<?php

$router->add("GET", "/v1/cursos", ["CursoController", "listar"]);
$router->add("POST", "/v1/curso", ["CursoController", "detalhes"]);

$router->add("POST", "/v1/modulos", ["ModuloController", "listar"]);
$router->add("POST", "/v1/topicos", ["TopicoController", "listar"]);
$router->add("POST", "/v1/topico", ["TopicoController", "detalhes"]);

$router->add("POST", "/v1/progresso", ["ProgressoController", "listar"]);
$router->add("POST", "/v1/progresso/concluir", ["ProgressoController", "concluirTopico"]);
