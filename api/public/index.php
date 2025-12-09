<?php

header("Content-Type: application/json; charset=UTF-8");

// Autoload simples
require_once __DIR__ . '/../core/Router.php';
require_once __DIR__ . '/../core/Database.php';

// Controllers
require_once __DIR__ . '/../controllers/CursoController.php';
require_once __DIR__ . '/../controllers/ModuloController.php';
require_once __DIR__ . '/../controllers/TopicoController.php';
require_once __DIR__ . '/../controllers/AlunoController.php';
require_once __DIR__ . '/../controllers/ProgressoController.php';

$router = new Router();

// ==========================
// ROTAS — CURSOS
// ==========================
$router->get('/v1/cursos', [CursoController::class, 'list']);

$router->post('/v1/cursos', [CursoController::class, 'create']);

$router->get('/v1/cursos/{id}', [CursoController::class, 'get']);

$router->delete('/v1/cursos/{id}', [CursoController::class, 'delete']);

$router->get('/v1/cursos/{id}/modulos', [ModuloController::class, 'listByCurso']);

$router->post('/v1/cursos/{id}/modulos', [ModuloController::class, 'create']);


// ==========================
// ROTAS — MÓDULOS
// ==========================
$router->get('/v1/modulos/{id}/topicos', [TopicoController::class, 'listByModulo']);

$router->post('/v1/modulos/{id}/topicos', [TopicoController::class, 'create']);

$router->delete('/v1/modulos/{id}', [ModuloController::class, 'delete']);


// ==========================
// ROTAS — TÓPICOS
// ==========================
$router->delete('/v1/topicos/{id}', [TopicoController::class, 'delete']);


// ==========================
// ROTAS — ALUNO
// ==========================
$router->post('/v1/alunos', [AlunoController::class, 'create']);

$router->post('/v1/alunos/login', [AlunoController::class, 'login']);

$router->get('/v1/alunos/{id}/cursos', [AlunoController::class, 'listarCursos']);


// ==========================
// EXECUTAR ROTEAMENTO
// ==========================
$router->run();
