<?php
// ============================================================
// CONFIGURAÇÃO BÁSICA
// ============================================================
header("Content-Type: application/json; charset=UTF-8");

// Ajuste conforme seu ambiente
const DB_HOST = "localhost";
const DB_NAME = "seu_banco";
const DB_USER = "seu_usuario";
const DB_PASS = "sua_senha";

// ============================================================
// FUNÇÕES AUXILIARES
// ============================================================
function getPDO(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }
    return $pdo;
}

function jsonResponse($data, int $status = 200): void {
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function jsonError(string $message, int $status = 400): void {
    jsonResponse(['error' => $message], $status);
}

function getJsonBody(): array {
    $raw = file_get_contents("php://input");
    if ($raw === '' || $raw === false) {
        return [];
    }
    $data = json_decode($raw, true);
    if (!is_array($data)) {
        jsonError("JSON inválido no corpo da requisição", 400);
    }
    return $data;
}

// ============================================================
// ROUTER BÁSICO
// ============================================================
$method = $_SERVER['REQUEST_METHOD'];
$path   = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/';
$segments = explode('/', trim($path, '/')); // ex: ['v1','cursos','1']

if ($segments[0] !== 'v1') {
    // Se não for /v1, apenas responde 404 (ou poderia manter suas rotas antigas aqui)
    jsonError("Endpoint não encontrado. Use /v1/...", 404);
}

$resource = $segments[1] ?? null;
$pdo      = getPDO();

// ============================================================
// ROTAS /v1/cursos
// ============================================================
if ($resource === 'cursos') {

    // GET /v1/cursos  -> lista cursos
    if ($method === 'GET' && count($segments) === 2) {
        $stmt = $pdo->query("SELECT * FROM curso ORDER BY id_curso");
        $rows = $stmt->fetchAll();
        jsonResponse(['data' => $rows]);
    }

    // POST /v1/cursos -> cria curso
    if ($method === 'POST' && count($segments) === 2) {
        $body = getJsonBody();
        $titulo       = $body['titulo']       ?? null;
        $descricao    = $body['descricao']    ?? null;
        $cargaHoraria = $body['carga_horaria'] ?? null;

        if (!$titulo) {
            jsonError("Campo 'titulo' é obrigatório");
        }

        $stmt = $pdo->prepare("
            INSERT INTO curso (titulo, descricao, carga_horaria)
            VALUES (:titulo, :descricao, :carga_horaria)
        ");
        $stmt->execute([
            ':titulo'        => $titulo,
            ':descricao'     => $descricao,
            ':carga_horaria' => $cargaHoraria,
        ]);

        $id = (int)$pdo->lastInsertId();
        jsonResponse(['id_curso' => $id], 201);
    }

    // /v1/cursos/{id}
    if (isset($segments[2])) {
        $cursoId = (int)$segments[2];

        // GET /v1/cursos/{id} -> detalhes do curso
        if ($method === 'GET' && count($segments) === 3) {
            $stmt = $pdo->prepare("SELECT * FROM curso WHERE id_curso = :id");
            $stmt->execute([':id' => $cursoId]);
            $curso = $stmt->fetch();

            if (!$curso) {
                jsonError("Curso não encontrado", 404);
            }

            jsonResponse($curso);
        }

        // GET /v1/cursos/{id}/modulos -> módulos de um curso
        if ($method === 'GET' && isset($segments[3]) && $segments[3] === 'modulos') {
            $stmt = $pdo->prepare("
                SELECT *
                FROM modulos
                WHERE id_curso = :id
                ORDER BY ordem, id_modulo
            ");
            $stmt->execute([':id' => $cursoId]);
            $mods = $stmt->fetchAll();
            jsonResponse(['data' => $mods]);
        }

        // GET /v1/cursos/{id}/conteudo -> curso com módulos e tópicos
        if ($method === 'GET' && isset($segments[3]) && $segments[3] === 'conteudo') {
            // Curso
            $stmt = $pdo->prepare("SELECT * FROM curso WHERE id_curso = :id");
            $stmt->execute([':id' => $cursoId]);
            $curso = $stmt->fetch();
            if (!$curso) jsonError("Curso não encontrado", 404);

            // Módulos
            $stmt = $pdo->prepare("
                SELECT * FROM modulos
                WHERE id_curso = :id
                ORDER BY ordem, id_modulo
            ");
            $stmt->execute([':id' => $cursoId]);
            $modulos = $stmt->fetchAll();

            // Para cada módulo, carrega tópicos
            foreach ($modulos as &$m) {
                $stmtT = $pdo->prepare("
                    SELECT *
                    FROM topicos
                    WHERE id_modulo = :id
                    ORDER BY ordem, id_topico
                ");
                $stmtT->execute([':id' => $m['id_modulo']]);
                $m['topicos'] = $stmtT->fetchAll();
            }

            $curso['modulos'] = $modulos;
            jsonResponse($curso);
        }
    }

    jsonError("Rota /v1/cursos inválida", 404);
}

// ============================================================
// ROTAS /v1/modulos
// ============================================================
if ($resource === 'modulos') {

    // POST /v1/modulos -> cria módulo
    if ($method === 'POST' && count($segments) === 2) {
        $body = getJsonBody();
        $id_curso = $body['id_curso'] ?? null;
        $titulo   = $body['titulo']   ?? null;
        $ordem    = $body['ordem']    ?? 1;

        if (!$id_curso || !$titulo) {
            jsonError("Campos 'id_curso' e 'titulo' são obrigatórios");
        }

        $stmt = $pdo->prepare("
            INSERT INTO modulos (id_curso, titulo, ordem)
            VALUES (:id_curso, :titulo, :ordem)
        ");
        $stmt->execute([
            ':id_curso' => $id_curso,
            ':titulo'   => $titulo,
            ':ordem'    => $ordem,
        ]);

        $id = (int)$pdo->lastInsertId();
        jsonResponse(['id_modulo' => $id], 201);
    }

    // GET /v1/modulos/{id}/topicos -> tópicos de um módulo
    if ($method === 'GET' && isset($segments[2]) && isset($segments[3]) && $segments[3] === 'topicos') {
        $moduloId = (int)$segments[2];

        $stmt = $pdo->prepare("
            SELECT * FROM topicos
            WHERE id_modulo = :id
            ORDER BY ordem, id_topico
        ");
        $stmt->execute([':id' => $moduloId]);
        $rows = $stmt->fetchAll();
        jsonResponse(['data' => $rows]);
    }

    jsonError("Rota /v1/modulos inválida", 404);
}

// ============================================================
// ROTAS /v1/topicos
// ============================================================
if ($resource === 'topicos') {

    // POST /v1/topicos -> cria tópico
    // body: id_modulo, codigo, tipo('theory'|'practice'), titulo, ordem
    if ($method === 'POST' && count($segments) === 2) {
        $body   = getJsonBody();
        $id_mod = $body['id_modulo'] ?? null;
        $codigo = $body['codigo']    ?? null;
        $tipo   = $body['tipo']      ?? null;
        $titulo = $body['titulo']    ?? null;
        $ordem  = $body['ordem']     ?? 1;

        if (!$id_mod || !$codigo || !$tipo || !$titulo) {
            jsonError("Campos 'id_modulo', 'codigo', 'tipo' e 'titulo' são obrigatórios");
        }

        $stmt = $pdo->prepare("
            INSERT INTO topicos (id_modulo, codigo, tipo, titulo, ordem)
            VALUES (:id_modulo, :codigo, :tipo, :titulo, :ordem)
        ");
        $stmt->execute([
            ':id_modulo' => $id_mod,
            ':codigo'    => $codigo,
            ':tipo'      => $tipo,
            ':titulo'    => $titulo,
            ':ordem'     => $ordem,
        ]);

        $id = (int)$pdo->lastInsertId();
        jsonResponse(['id_topico' => $id], 201);
    }

    // /v1/topicos/{id}
    if (isset($segments[2])) {
        $topicoId = (int)$segments[2];

        // GET /v1/topicos/{id} -> detalhe completo (teoria/prática/códigos)
        if ($method === 'GET' && count($segments) === 3) {
            $stmt = $pdo->prepare("
                SELECT 
                    t.*,
                    tt.link_gamma,
                    tt.link_video,
                    tp.problem_statement,
                    tp.resposta_esperada,
                    tp.input_interativo
                FROM topicos t
                LEFT JOIN topicos_teoria tt  ON tt.id_topico = t.id_topico
                LEFT JOIN topicos_pratica tp ON tp.id_topico = t.id_topico
                WHERE t.id_topico = :id
            ");
            $stmt->execute([':id' => $topicoId]);
            $topico = $stmt->fetch();

            if (!$topico) jsonError("Tópico não encontrado", 404);

            // Códigos pré-escritos
            $stmt2 = $pdo->prepare("
                SELECT id_codigo, codigo 
                FROM pratica_codigo_pre
                WHERE id_topico = :id
                ORDER BY id_codigo
            ");
            $stmt2->execute([':id' => $topicoId]);
            $topico['codigos_pre'] = $stmt2->fetchAll();

            jsonResponse($topico);
        }

        // POST /v1/topicos/{id}/teoria -> inserir/atualizar dados de teoria
        if ($method === 'POST' && isset($segments[3]) && $segments[3] === 'teoria') {
            $body = getJsonBody();
            $gamma = $body['link_gamma'] ?? null;
            $video = $body['link_video'] ?? null;

            $stmt = $pdo->prepare("SELECT id_topico FROM topicos WHERE id_topico = :id");
            $stmt->execute([':id' => $topicoId]);
            if (!$stmt->fetch()) jsonError("Tópico não encontrado", 404);

            // UPSERT simples: tenta atualizar, se não atualizar nenhuma linha, insere
            $stmt = $pdo->prepare("
                INSERT INTO topicos_teoria (id_topico, link_gamma, link_video)
                VALUES (:id, :gamma, :video)
                ON DUPLICATE KEY UPDATE
                    link_gamma = VALUES(link_gamma),
                    link_video = VALUES(link_video)
            ");
            $stmt->execute([
                ':id'    => $topicoId,
                ':gamma' => $gamma,
                ':video' => $video,
            ]);

            jsonResponse(['message' => 'Teoria salva com sucesso']);
        }

        // POST /v1/topicos/{id}/pratica -> inserir/atualizar dados de prática
        if ($method === 'POST' && isset($segments[3]) && $segments[3] === 'pratica') {
            $body = getJsonBody();
            $problem  = $body['problem_statement'] ?? null;
            $resposta = $body['resposta_esperada'] ?? null;
            $inter    = !empty($body['input_interativo']) ? 1 : 0;

            if (!$problem) jsonError("Campo 'problem_statement' é obrigatório");

            $stmt = $pdo->prepare("SELECT id_topico FROM topicos WHERE id_topico = :id");
            $stmt->execute([':id' => $topicoId]);
            if (!$stmt->fetch()) jsonError("Tópico não encontrado", 404);

            $stmt = $pdo->prepare("
                INSERT INTO topicos_pratica (id_topico, problem_statement, resposta_esperada, input_interativo)
                VALUES (:id, :problem, :resp, :inter)
                ON DUPLICATE KEY UPDATE
                    problem_statement = VALUES(problem_statement),
                    resposta_esperada = VALUES(resposta_esperada),
                    input_interativo  = VALUES(input_interativo)
            ");
            $stmt->execute([
                ':id'      => $topicoId,
                ':problem' => $problem,
                ':resp'    => $resposta,
                ':inter'   => $inter,
            ]);

            jsonResponse(['message' => 'Prática salva com sucesso']);
        }

        // POST /v1/topicos/{id}/codigo-pre -> adiciona código pré-escrito
        if ($method === 'POST' && isset($segments[3]) && $segments[3] === 'codigo-pre') {
            $body   = getJsonBody();
            $codigo = $body['codigo'] ?? null;
            if (!$codigo) jsonError("Campo 'codigo' é obrigatório");

            $stmt = $pdo->prepare("
                INSERT INTO pratica_codigo_pre (id_topico, codigo)
                VALUES (:id, :codigo)
            ");
            $stmt->execute([
                ':id'     => $topicoId,
                ':codigo' => $codigo,
            ]);

            $id = (int)$pdo->lastInsertId();
            jsonResponse(['id_codigo' => $id], 201);
        }
    }

    jsonError("Rota /v1/topicos inválida", 404);
}

// ============================================================
// ROTAS /v1/alunos
// ============================================================
if ($resource === 'alunos') {

    // POST /v1/alunos -> cria aluno
    if ($method === 'POST' && count($segments) === 2) {
        $body = getJsonBody();
        $nome  = $body['nome']  ?? null;
        $email = $body['email'] ?? null;
        $senha = $body['senha'] ?? null;

        if (!$nome || !$email || !$senha) {
            jsonError("Campos 'nome', 'email' e 'senha' são obrigatórios");
        }

        $senhaHash = password_hash($senha, PASSWORD_DEFAULT);

        try {
            $stmt = $pdo->prepare("
                INSERT INTO aluno (nome, email, senha_hash)
                VALUES (:nome, :email, :senha_hash)
            ");
            $stmt->execute([
                ':nome'       => $nome,
                ':email'      => $email,
                ':senha_hash' => $senhaHash,
            ]);
        } catch (PDOException $e) {
            if ($e->getCode() == '23000') {
                jsonError("E-mail já cadastrado", 409);
            }
            throw $e;
        }

        $id = (int)$pdo->lastInsertId();
        jsonResponse(['id_aluno' => $id], 201);
    }

    // /v1/alunos/{id}
    if (isset($segments[2])) {
        $alunoId = (int)$segments[2];

        // GET /v1/alunos/{id} -> dados do aluno
        if ($method === 'GET' && count($segments) === 3) {
            $stmt = $pdo->prepare("
                SELECT id_aluno, nome, email, data_cadastro
                FROM aluno
                WHERE id_aluno = :id
            ");
            $stmt->execute([':id' => $alunoId]);
            $row = $stmt->fetch();
            if (!$row) jsonError("Aluno não encontrado", 404);
            jsonResponse($row);
        }

        // GET /v1/alunos/{id}/matriculas -> cursos do aluno
        if ($method === 'GET' && isset($segments[3]) && $segments[3] === 'matriculas') {
            $stmt = $pdo->prepare("
                SELECT 
                    ac.id_matricula,
                    ac.data_matricula,
                    c.id_curso,
                    c.titulo,
                    c.descricao,
                    c.carga_horaria
                FROM aluno_curso ac
                JOIN curso c ON c.id_curso = ac.id_curso
                WHERE ac.id_aluno = :id
                ORDER BY ac.data_matricula DESC
            ");
            $stmt->execute([':id' => $alunoId]);
            $rows = $stmt->fetchAll();
            jsonResponse(['data' => $rows]);
        }

        // POST /v1/alunos/{id}/matriculas -> matricular em curso
        // body: id_curso
        if ($method === 'POST' && isset($segments[3]) && $segments[3] === 'matriculas') {
            $body = getJsonBody();
            $cursoId = $body['id_curso'] ?? null;
            if (!$cursoId) jsonError("Campo 'id_curso' é obrigatório");

            // Verifica se curso existe
            $stmt = $pdo->prepare("SELECT id_curso FROM curso WHERE id_curso = :id");
            $stmt->execute([':id' => $cursoId]);
            if (!$stmt->fetch()) jsonError("Curso não encontrado", 404);

            try {
                $stmt = $pdo->prepare("
                    INSERT INTO aluno_curso (id_aluno, id_curso)
                    VALUES (:aluno, :curso)
                ");
                $stmt->execute([
                    ':aluno' => $alunoId,
                    ':curso' => $cursoId,
                ]);
            } catch (PDOException $e) {
                if ($e->getCode() == '23000') {
                    jsonError("Aluno já matriculado neste curso", 409);
                }
                throw $e;
            }

            $idMat = (int)$pdo->lastInsertId();
            jsonResponse(['id_matricula' => $idMat], 201);
        }

        // =====================================================
        // PROGRESSO
        // /v1/alunos/{id}/cursos/{cursoId}/progresso
        // /v1/alunos/{id}/cursos/{cursoId}/modulos/{moduloId}/status
        // /v1/alunos/{id}/cursos/{cursoId}/topicos/{topicoId}/concluir
        // =====================================================
        if (isset($segments[3]) && $segments[3] === 'cursos' && isset($segments[4])) {
            $cursoId = (int)$segments[4];

            // Recupera matrícula
            $stmt = $pdo->prepare("
                SELECT id_matricula 
                FROM aluno_curso
                WHERE id_aluno = :aluno AND id_curso = :curso
            ");
            $stmt->execute([
                ':aluno' => $alunoId,
                ':curso' => $cursoId,
            ]);
            $mat = $stmt->fetch();
            if (!$mat) jsonError("Aluno não está matriculado neste curso", 404);
            $idMatricula = (int)$mat['id_matricula'];

            // GET /v1/alunos/{id}/cursos/{cursoId}/progresso
            if ($method === 'GET' && isset($segments[5]) && $segments[5] === 'progresso') {

                // Progresso por módulo
                $stmt = $pdo->prepare("
                    SELECT 
                        m.id_modulo,
                        m.titulo,
                        apm.status,
                        apm.data_inicio,
                        apm.data_conclusao
                    FROM modulos m
                    LEFT JOIN aluno_progresso_modulo apm
                      ON apm.id_modulo = m.id_modulo
                     AND apm.id_matricula = :mat
                    WHERE m.id_curso = :curso
                    ORDER BY m.ordem, m.id_modulo
                ");
                $stmt->execute([
                    ':mat'   => $idMatricula,
                    ':curso' => $cursoId,
                ]);
                $modulos = $stmt->fetchAll();

                // Progresso por tópico
                foreach ($modulos as &$m) {
                    $stmtT = $pdo->prepare("
                        SELECT 
                            t.id_topico,
                            t.titulo,
                            t.tipo,
                            apt.concluido,
                            apt.data_conclusao
                        FROM topicos t
                        LEFT JOIN aluno_progresso_modico apt
                          ON apt.id_topico = t.id_topico
                        JOIN aluno_progresso_modulo apm2
                          ON apm2.id_progresso_modulo = apt.id_progresso_modulo
                         AND apm2.id_matricula = :mat
                        WHERE t.id_modulo = :mod
                        ORDER BY t.ordem, t.id_topico
                    ");
                    // Correção: a tabela é aluno_progresso_topico
                }

                // Melhor fazer separado corretamente:
                $stmt = $pdo->prepare("
                    SELECT 
                        m.id_modulo, m.titulo AS modulo_titulo,
                        apm.status, apm.data_inicio, apm.data_conclusao,
                        t.id_topico, t.titulo AS topico_titulo, t.tipo,
                        apt.concluido, apt.data_conclusao AS topico_data_conclusao
                    FROM modulos m
                    LEFT JOIN topicos t
                      ON t.id_modulo = m.id_modulo
                    LEFT JOIN aluno_progresso_modulo apm
                      ON apm.id_modulo = m.id_modulo
                     AND apm.id_matricula = :mat
                    LEFT JOIN aluno_progresso_topico apt
                      ON apt.id_topico = t.id_topico
                     AND apt.id_progresso_modulo = apm.id_progresso_modulo
                    WHERE m.id_curso = :curso
                    ORDER BY m.ordem, m.id_modulo, t.ordem, t.id_topico
                ");
                $stmt->execute([
                    ':mat'   => $idMatricula,
                    ':curso' => $cursoId,
                ]);
                $rows = $stmt->fetchAll();

                // Agrupa por módulo
                $result = [];
                foreach ($rows as $r) {
                    $mid = $r['id_modulo'];
                    if (!isset($result[$mid])) {
                        $result[$mid] = [
                            'id_modulo'       => $r['id_modulo'],
                            'titulo'          => $r['modulo_titulo'],
                            'status'          => $r['status'],
                            'data_inicio'     => $r['data_inicio'],
                            'data_conclusao'  => $r['data_conclusao'],
                            'topicos'         => [],
                        ];
                    }
                    if ($r['id_topico']) {
                        $result[$mid]['topicos'][] = [
                            'id_topico'      => $r['id_topico'],
                            'titulo'         => $r['topico_titulo'],
                            'tipo'           => $r['tipo'],
                            'concluido'      => (bool)$r['concluido'],
                            'data_conclusao' => $r['topico_data_conclusao'],
                        ];
                    }
                }

                jsonResponse(['modulos' => array_values($result)]);
            }

            // POST /v1/alunos/{id}/cursos/{cursoId}/modulos/{moduloId}/status
            if ($method === 'POST' && isset($segments[5]) && $segments[5] === 'modulos' && isset($segments[6]) && $segments[7] === 'status') {
                $moduloId = (int)$segments[6];
                $body     = getJsonBody();
                $status   = $body['status'] ?? null;

                if (!in_array($status, ['nao_iniciado', 'em_andamento', 'concluido'], true)) {
                    jsonError("Status inválido. Use 'nao_iniciado', 'em_andamento' ou 'concluido'");
                }

                // Garante que o módulo pertence ao curso
                $stmt = $pdo->prepare("
                    SELECT id_modulo FROM modulos
                    WHERE id_modulo = :mid AND id_curso = :cid
                ");
                $stmt->execute([
                    ':mid' => $moduloId,
                    ':cid' => $cursoId,
                ]);
                if (!$stmt->fetch()) jsonError("Módulo não encontrado nesse curso", 404);

                // UPSERT progresso módulo
                $now = date('Y-m-d H:i:s');
                $dataConclusao = ($status === 'concluido') ? $now : null;
                $dataInicio    = ($status === 'em_andamento' || $status === 'concluido') ? $now : null;

                $stmt = $pdo->prepare("
                    INSERT INTO aluno_progresso_modulo
                        (id_matricula, id_modulo, status, data_inicio, data_conclusao)
                    VALUES
                        (:mat, :mod, :status, :inicio, :conclusao)
                    ON DUPLICATE KEY UPDATE
                        status = VALUES(status),
                        data_inicio = COALESCE(aluno_progresso_modulo.data_inicio, VALUES(data_inicio)),
                        data_conclusao = VALUES(data_conclusao)
                ");
                $stmt->execute([
                    ':mat'       => $idMatricula,
                    ':mod'       => $moduloId,
                    ':status'    => $status,
                    ':inicio'    => $dataInicio,
                    ':conclusao' => $dataConclusao,
                ]);

                jsonResponse(['message' => 'Progresso de módulo atualizado']);
            }

            // POST /v1/alunos/{id}/cursos/{cursoId}/topicos/{topicoId}/concluir
            if ($method === 'POST' && isset($segments[5]) && $segments[5] === 'topicos' && isset($segments[6]) && $segments[7] === 'concluir') {
                $topicoId = (int)$segments[6];

                // Descobre módulo do tópico
                $stmt = $pdo->prepare("SELECT id_modulo FROM topicos WHERE id_topico = :tid");
                $stmt->execute([':tid' => $topicoId]);
                $row = $stmt->fetch();
                if (!$row) jsonError("Tópico não encontrado", 404);
                $moduloId = (int)$row['id_modulo'];

                // Garante progresso de módulo
                $stmt = $pdo->prepare("
                    SELECT id_progresso_modulo
                    FROM aluno_progresso_modulo
                    WHERE id_matricula = :mat AND id_modulo = :mod
                ");
                $stmt->execute([
                    ':mat' => $idMatricula,
                    ':mod' => $moduloId,
                ]);
                $pm = $stmt->fetch();

                if (!$pm) {
                    $stmt2 = $pdo->prepare("
                        INSERT INTO aluno_progresso_modulo
                            (id_matricula, id_modulo, status, data_inicio)
                        VALUES
                            (:mat, :mod, 'em_andamento', :inicio)
                    ");
                    $stmt2->execute([
                        ':mat'   => $idMatricula,
                        ':mod'   => $moduloId,
                        ':inicio'=> date('Y-m-d H:i:s'),
                    ]);
                    $idProgModulo = (int)$pdo->lastInsertId();
                } else {
                    $idProgModulo = (int)$pm['id_progresso_modulo'];
                }

                // UPSERT progresso de tópico
                $stmt = $pdo->prepare("
                    INSERT INTO aluno_progresso_topico
                        (id_progresso_modulo, id_topico, concluido, data_conclusao)
                    VALUES
                        (:pm, :top, 1, :data)
                    ON DUPLICATE KEY UPDATE
                        concluido = 1,
                        data_conclusao = VALUES(data_conclusao)
                ");
                $stmt->execute([
                    ':pm'   => $idProgModulo,
                    ':top'  => $topicoId,
                    ':data' => date('Y-m-d H:i:s'),
                ]);

                jsonResponse(['message' => 'Tópico marcado como concluído']);
            }
        }
    }

    jsonError("Rota /v1/alunos inválida", 404);
}

// ============================================================
// SE CHEGOU AQUI, ROTA NÃO RECONHECIDA
// ============================================================
jsonError("Endpoint não encontrado", 404);
