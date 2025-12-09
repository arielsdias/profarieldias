<?php

require_once "../core/Database.php";
require_once "../core/Request.php";
require_once "../core/Response.php";
require_once "../core/Router.php";

require_once "../models/CursoModel.php";
require_once "../models/ModuloModel.php";
require_once "../models/TopicoModel.php";
require_once "../models/AlunoModel.php";
require_once "../models/ProgressoModel.php";

require_once "../controllers/CursoController.php";
require_once "../controllers/ModuloController.php";
require_once "../controllers/TopicoController.php";
require_once "../controllers/AlunoController.php";
require_once "../controllers/ProgressoController.php";

$req = new Request();
$router = new Router();

require "../routes/routes.php";

// despacha rota
$router->dispatch($req);
