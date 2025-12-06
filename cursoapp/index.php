<?php
// Ajusta header UTF-8 para todas as respostas
header("Content-Type: text/html; charset=UTF-8");

// Pega a rota acessada, ex: /sobre
$rota = "/" . ($_GET["rota"] ?? "");

// Pega parâmetros da URL, ex: ?nome=Ariel
$parametros = $_GET;

// ===============================
// ROTAS FIXAS
// ===============================
switch ($rota) {
    
    case "/":
        echo "Bem-vindo à página inicial!";
        exit;

    case "/sobre":
        echo "Esta é a página SOBRE.";
        exit;

    case "/contato":
        echo "Entre em contato pelo e-mail: contato@exemplo.com";
        exit;

    case "/ajuda":
        echo "Página de ajuda: em breve com tutoriais e suporte.";
        exit;

    case "/servicos":
        echo "Nossos serviços incluem desenvolvimento de sistemas e consultoria.";
        exit;

    case "/agradecimento":
        echo "Obrigado pela sua visita! Volte sempre.";
        exit;
}

// ===============================
// ROTAS DINÂMICAS
// ===============================

// /boasvindas?nome=Ariel
if ($rota === "/boasvindas") {
    $nome = $parametros["nome"] ?? "visitante";
    echo "Seja bem-vindo(a), " . htmlspecialchars($nome) . "!";
    exit;
}

// /dobro?valor=10
if ($rota === "/dobro") {
    if (!isset($parametros["valor"]) || !is_numeric($parametros["valor"])) {
        echo "Erro: informe um número válido em ?valor=";
        exit;
    }

    $valor = (int)$parametros["valor"];
    echo "O dobro de $valor é " . ($valor * 2);
    exit;
}

// /maiuscula?texto=ola mundo
if ($rota === "/maiuscula") {
    $texto = $parametros["texto"] ?? "";
    echo "Texto em maiúsculas: " . strtoupper($texto);
    exit;
}

// ===============================
// ROTA NÃO ENCONTRADA (404)
// ===============================
http_response_code(404);
echo "404 - Rota não encontrada";
