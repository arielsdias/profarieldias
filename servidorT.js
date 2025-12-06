const http = require("http");
const url = require("url");

// Função para enviar respostas
function enviarResposta(res, mensagem) {
    res.writeHead(200, { "Content-Type": "text/html; charset=UTF-8" });
    res.end(mensagem);
}

const servidor = http.createServer((req, res) => {
    const rota = req.url.split("?")[0];
    const query = url.parse(req.url, true).query;

    // Rotas fixas
    if (rota === "/") {
        return enviarResposta(res, "Bem-vindo à página inicial!");
    }
    if (rota === "/sobre") {
        return enviarResposta(res, "Esta é a página SOBRE.");
    }
    if (rota === "/contato") {
        return enviarResposta(res, "Entre em contato pelo e-mail: contato@exemplo.com");
    }
    if (rota === "/ajuda") {
        return enviarResposta(res, "Página de ajuda: em breve com tutoriais e suporte.");
    }
    if (rota === "/servicos") {
        return enviarResposta(res, "Nossos serviços incluem desenvolvimento de sistemas e consultoria.");
    }
    if (rota === "/agradecimento") {
        return enviarResposta(res, "Obrigado pela sua visita! Volte sempre.");
    }

    // Rotas dinâmicas
    if (rota === "/boasvindas") {
        const nome = query.nome || "visitante";
        return enviarResposta(res, `Seja bem-vindo(a), ${nome}!`);
    }

    if (rota === "/dobro") {
        const valor = parseInt(query.valor);
        if (isNaN(valor)) {
            return enviarResposta(res, "Erro: informe um número válido em ?valor=");
        }
        return enviarResposta(res, `O dobro de ${valor} é ${valor * 2}`);
    }

    if (rota === "/maiuscula") {
        const texto = query.texto || "";
        return enviarResposta(res, `Texto em maiúsculas: ${texto.toUpperCase()}`);
    }

    // Rota não encontrada
    enviarResposta(res, "404 - Rota não encontrada");
});

// Servidor ouve em 0.0.0.0, porta 8080
servidor.listen(8080, "0.0.0.0", () => {
    console.log("Servidor rodando em http://localhost:8080/");
});
