<?php
// index.php
header('Content-Type: application/json; charset=utf-8');

// ============================
// CONFIGURAÇÃO DO BANCO
// ============================
$DB_HOST = 'http://projetodb.mysql.uhserver.com/';
$DB_NAME = 'projetodb';
$DB_USER = 'arieldias';
$DB_PASS = 'pbvd@20ug';

try {
    $pdo = new PDO(
        "mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4",
        $DB_USER,
        $DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['erro' => 'Falha na conexão com o banco', 'detalhe' => $e->getMessage()]);
    exit;
}

// ============================
// HELPER: LER BODY JSON
// ============================
function getJsonBody() {
    $raw = file_get_contents('php://input');
    if (empty($raw)) {
        return [];
    }
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

// ============================
// ROTAS BÁSICAS
// ?resource=curso&action=create
// ============================
$resource = $_GET['resource'] ?? null;
$action   = $_GET['action']   ?? null;

if (!$resource || !$action) {
    http_response_code(400);
    echo json_encode(['erro' => 'Informe resource e action, ex: ?resource=curso&action=list']);
    exit;
}

// ============================
// FUNÇÕES DE CURSO
// ============================

function createCurso(PDO $pdo, array $data) {
    $sql = "INSERT INTO curso (titulo, descricao, carga_horaria) 
            VALUES (:titulo, :descricao, :carga_horaria)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':titulo'        => $data['titulo']        ?? 'Curso sem nome',
        ':descricao'     => $data['descricao']     ?? null,
        ':carga_horaria' => $data['carga_horaria'] ?? null,
    ]);
    return ['id_curso' => $pdo->lastInsertId()];
}

function listCursos(PDO $pdo) {
    $sql = "SELECT * FROM curso ORDER BY data_criacao DESC";
    return $pdo->query($sql)->fetchAll();
}

// ============================
// FUNÇÕES DE MÓDULO
// ============================

function createModulo(PDO $pdo, array $data) {
    if (empty($data['id_curso']) || empty($data['titulo'])) {
        throw new InvalidArgumentException('id_curso e titulo são obrigatórios para módulo.');
    }
    $sql = "INSERT INTO modulos (id_curso, titulo, ordem)
            VALUES (:id_curso, :titulo, :ordem)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':id_curso' => $data['id_curso'],
        ':titulo'   => $data['titulo'],
        ':ordem'    => $data['ordem'] ?? 1,
    ]);
    return ['id_modulo' => $pdo->lastInsertId()];
}

function listModulosByCurso(PDO $pdo, $id_curso) {
    $sql = "SELECT * FROM modulos WHERE id_curso = :id_curso ORDER BY ordem";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id_curso' => $id_curso]);
    return $stmt->fetchAll();
}

// ============================
// FUNÇÕES DE TÓPICO
// ============================

function createTopico(PDO $pdo, array $data) {
    if (empty($data['id_modulo']) || empty($data['codigo']) || empty($data['tipo']) || empty($data['titulo'])) {
        throw new InvalidArgumentException('id_modulo, codigo, tipo e titulo são obrigatórios para tópico.');
    }

    // 1) Cria o registro base em topicos
    $sql = "INSERT INTO topicos (id_modulo, codigo, tipo, titulo, ordem)
            VALUES (:id_modulo, :codigo, :tipo, :titulo, :ordem)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':id_modulo' => $data['id_modulo'],
        ':codigo'    => $data['codigo'],
        ':tipo'      => $data['tipo'],          // 'theory' ou 'practice'
        ':titulo'    => $data['titulo'],
        ':ordem'     => $data['ordem'] ?? 1,
    ]);
    $id_topico = $pdo->lastInsertId();

    // 2) Se for teoria, cria em topicos_teoria
    if ($data['tipo'] === 'theory') {
        $sqlTeoria = "INSERT INTO topicos_teoria (id_topico, link_gamma, link_video)
                      VALUES (:id_topico, :link_gamma, :link_video)";
        $stmtT = $pdo->prepare($sqlTeoria);
        $stmtT->execute([
            ':id_topico'  => $id_topico,
            ':link_gamma' => $data['link_gamma'] ?? null,
            ':link_video' => $data['link_video'] ?? null,
        ]);
    }

    // 3) Se for prática, cria em topicos_pratica e opcionalmente pratica_codigo_pre
    if ($data['tipo'] === 'practice') {
        $sqlPratica = "INSERT INTO topicos_pratica (id_topico, problem_statement, resposta_esperada, input_interativo)
                       VALUES (:id_topico, :problem_statement, :resposta_esperada, :input_interativo)";
        $stmtP = $pdo->prepare($sqlPratica);
        $stmtP->execute([
            ':id_topico'         => $id_topico,
            ':problem_statement' => $data['problem_statement'] ?? '',
            ':resposta_esperada' => $data['resposta_esperada'] ?? null,
            ':input_interativo'  => !empty($data['input_interativo']) ? 1 : 0,
        ]);

        if (!empty($data['codigo_pre_escrito'])) {
            $sqlCodigo = "INSERT INTO pratica_codigo_pre (id_topico, codigo)
                          VALUES (:id_topico, :codigo)";
            $stmtC = $pdo->prepare($sqlCodigo);
            $stmtC->execute([
                ':id_topico' => $id_topico,
                ':codigo'    => $data['codigo_pre_escrito'],
            ]);
        }
    }

    return ['id_topico' => $id_topico];
}

function listTopicosByModulo(PDO $pdo, $id_modulo) {
    $sql = "SELECT * FROM topicos WHERE id_modulo = :id_modulo ORDER BY ordem";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id_modulo' => $id_modulo]);
    return $stmt->fetchAll();
}

// ============================
// FUNÇÕES DE ALUNO
// ============================

function createAluno(PDO $pdo, array $data) {
    if (empty($data['nome']) || empty($data['email']) || empty($data['senha'])) {
        throw new InvalidArgumentException('nome, email e senha são obrigatórios para aluno.');
    }

    // Aqui você pode usar password_hash
    $senha_hash = password_hash($data['senha'], PASSWORD_DEFAULT);

    $sql = "INSERT INTO aluno (nome, email, senha_hash)
            VALUES (:nome, :email, :senha_hash)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':nome'       => $data['nome'],
        ':email'      => $data['email'],
        ':senha_hash' => $senha_hash,
    ]);

    return ['id_aluno' => $pdo->lastInsertId()];
}

function listAlunos(PDO $pdo) {
    $sql = "SELECT id_aluno, nome, email, data_cadastro FROM aluno ORDER BY data_cadastro DESC";
    return $pdo->query($sql)->fetchAll();
}

// ============================
// MATRÍCULA DO ALUNO EM CURSO
// ============================

function matricularAlunoEmCurso(PDO $pdo, array $data) {
    if (empty($data['id_aluno']) || empty($data['id_curso'])) {
        throw new InvalidArgumentException('id_aluno e id_curso são obrigatórios para matrícula.');
    }

    $sql = "INSERT INTO aluno_curso (id_aluno, id_curso)
            VALUES (:id_aluno, :id_curso)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':id_aluno' => $data['id_aluno'],
        ':id_curso' => $data['id_curso'],
    ]);

    return ['id_matricula' => $pdo->lastInsertId()];
}

function listCursosDoAluno(PDO $pdo, $id_aluno) {
    $sql = "SELECT ac.id_matricula, c.*
            FROM aluno_curso ac
            JOIN curso c ON ac.id_curso = c.id_curso
            WHERE ac.id_aluno = :id_aluno";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id_aluno' => $id_aluno]);
    return $stmt->fetchAll();
}

// ============================
// PROGRESSO DO ALUNO
// ============================

/**
 * Marca um tópico como concluído para um aluno em um curso.
 * Atualiza também o progresso de módulo.
 */
function marcarTopicoConcluido(PDO $pdo, array $data) {
    if (empty($data['id_matricula']) || empty($data['id_modulo']) || empty($data['id_topico'])) {
        throw new InvalidArgumentException('id_matricula, id_modulo e id_topico são obrigatórios para progresso.');
    }

    $id_matricula = $data['id_matricula'];
    $id_modulo    = $data['id_modulo'];
    $id_topico    = $data['id_topico'];

    $pdo->beginTransaction();

    // 1) Garante que exista registro em aluno_progresso_modulo
    $sqlCheck = "SELECT id_progresso_modulo FROM aluno_progresso_modulo
                 WHERE id_matricula = :id_matricula AND id_modulo = :id_modulo";
    $stmt = $pdo->prepare($sqlCheck);
    $stmt->execute([
        ':id_matricula' => $id_matricula,
        ':id_modulo'    => $id_modulo,
    ]);
    $row = $stmt->fetch();

    if ($row) {
        $id_progresso_modulo = $row['id_progresso_modulo'];
        // se ainda estava nao_iniciado, coloca em_andamento
        $pdo->prepare("UPDATE aluno_progresso_modulo 
                       SET status = CASE WHEN status = 'nao_iniciado' THEN 'em_andamento' ELSE status END,
                           data_inicio = COALESCE(data_inicio, NOW())
                       WHERE id_progresso_modulo = :id")
            ->execute([':id' => $id_progresso_modulo]);
    } else {
        // cria novo registro
        $sqlIns = "INSERT INTO aluno_progresso_modulo (id_matricula, id_modulo, status, data_inicio)
                   VALUES (:id_matricula, :id_modulo, 'em_andamento', NOW())";
        $stmtIns = $pdo->prepare($sqlIns);
        $stmtIns->execute([
            ':id_matricula' => $id_matricula,
            ':id_modulo'    => $id_modulo,
        ]);
        $id_progresso_modulo = $pdo->lastInsertId();
    }

    // 2) Marca tópico como concluído em aluno_progresso_topico
    $sqlTop = "INSERT INTO aluno_progresso_topico (id_progresso_modulo, id_topico, concluido, data_conclusao)
               VALUES (:id_progresso_modulo, :id_topico, 1, NOW())
               ON DUPLICATE KEY UPDATE concluido = 1, data_conclusao = NOW()";
    $stmtTop = $pdo->prepare($sqlTop);
    $stmtTop->execute([
        ':id_progresso_modulo' => $id_progresso_modulo,
        ':id_topico'           => $id_topico,
    ]);

    // 3) Verifica se todos os tópicos do módulo foram concluídos → se sim, marca módulo como concluído
    $sqlTotais = "SELECT COUNT(*) AS total 
                  FROM topicos 
                  WHERE id_modulo = :id_modulo";
    $stmtTotais = $pdo->prepare($sqlTotais);
    $stmtTotais->execute([':id_modulo' => $id_modulo]);
    $totalTopicos = (int)$stmtTotais->fetchColumn();

    $sqlConcluidos = "SELECT COUNT(*) AS concluidos
                      FROM aluno_progresso_topico
                      WHERE id_progresso_modulo = :id_progresso_modulo
                        AND concluido = 1";
    $stmtConcl = $pdo->prepare($sqlConcluidos);
    $stmtConcl->execute([':id_progresso_modulo' => $id_progresso_modulo]);
    $totalConcluidos = (int)$stmtConcl->fetchColumn();

    if ($totalTopicos > 0 && $totalConcluidos >= $totalTopicos) {
        $sqlUpdateMod = "UPDATE aluno_progresso_modulo
                         SET status = 'concluido', data_conclusao = NOW()
                         WHERE id_progresso_modulo = :id";
        $stmtUp = $pdo->prepare($sqlUpdateMod);
        $stmtUp->execute([':id' => $id_progresso_modulo]);
    }

    $pdo->commit();

    return [
        'mensagem' => 'Tópico marcado como concluído com sucesso.',
        'id_progresso_modulo' => $id_progresso_modulo,
        'total_topicos' => $totalTopicos,
        'total_concluidos' => $totalConcluidos
    ];
}

/**
 * Busca o progresso de um aluno em um curso:
 * - módulos com status
 * - quantos tópicos concluídos em cada módulo
 */
function getProgressoAlunoCurso(PDO $pdo, $id_aluno, $id_curso) {
    // 1) pegar matrículas
    $sqlMat = "SELECT id_matricula FROM aluno_curso 
               WHERE id_aluno = :id_aluno AND id_curso = :id_curso";
    $stmtMat = $pdo->prepare($sqlMat);
    $stmtMat->execute([
        ':id_aluno' => $id_aluno,
        ':id_curso' => $id_curso,
    ]);
    $matricula = $stmtMat->fetch();
    if (!$matricula) {
        return ['erro' => 'Aluno não está matriculado neste curso.'];
    }
    $id_matricula = $matricula['id_matricula'];

    // 2) módulos do curso + status do progresso
    $sql = "
        SELECT 
            m.id_modulo,
            m.titulo AS titulo_modulo,
            apm.status,
            apm.data_inicio,
            apm.data_conclusao,
            (SELECT COUNT(*) FROM topicos t WHERE t.id_modulo = m.id_modulo) AS total_topicos,
            (SELECT COUNT(*) FROM aluno_progresso_topico apt
             JOIN aluno_progresso_modulo apm2 ON apm2.id_progresso_modulo = apt.id_progresso_modulo
             WHERE apm2.id_matricula = :id_matricula
               AND apm2.id_modulo = m.id_modulo
               AND apt.concluido = 1) AS topicos_concluidos
        FROM modulos m
        LEFT JOIN aluno_progresso_modulo apm
               ON apm.id_modulo = m.id_modulo
              AND apm.id_matricula = :id_matricula
        WHERE m.id_curso = :id_curso
        ORDER BY m.ordem;
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':id_matricula' => $id_matricula,
        ':id_curso'     => $id_curso,
    ]);

    return $stmt->fetchAll();
}

// ============================
// DESPACHO DAS ROTAS
// ============================

try {
    $body = getJsonBody();

    switch ($resource) {
        case 'curso':
            if ($action === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
                $res = createCurso($pdo, $body);
            } elseif ($action === 'list') {
                $res = listCursos($pdo);
            } else {
                throw new InvalidArgumentException('Ação inválida para resource=curso');
            }
            break;

        case 'modulo':
            if ($action === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
                $res = createModulo($pdo, $body);
            } elseif ($action === 'listByCurso') {
                $id_curso = $_GET['id_curso'] ?? null;
                if (!$id_curso) throw new InvalidArgumentException('id_curso é obrigatório');
                $res = listModulosByCurso($pdo, $id_curso);
            } else {
                throw new InvalidArgumentException('Ação inválida para resource=modulo');
            }
            break;

        case 'topico':
            if ($action === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
                $res = createTopico($pdo, $body);
            } elseif ($action === 'listByModulo') {
                $id_modulo = $_GET['id_modulo'] ?? null;
                if (!$id_modulo) throw new InvalidArgumentException('id_modulo é obrigatório');
                $res = listTopicosByModulo($pdo, $id_modulo);
            } else {
                throw new InvalidArgumentException('Ação inválida para resource=topico');
            }
            break;

        case 'aluno':
            if ($action === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
                $res = createAluno($pdo, $body);
            } elseif ($action === 'list') {
                $res = listAlunos($pdo);
            } else {
                throw new InvalidArgumentException('Ação inválida para resource=aluno');
            }
            break;

        case 'matricula':
            if ($action === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
                $res = matricularAlunoEmCurso($pdo, $body);
            } elseif ($action === 'listCursosAluno') {
                $id_aluno = $_GET['id_aluno'] ?? null;
                if (!$id_aluno) throw new InvalidArgumentException('id_aluno é obrigatório');
                $res = listCursosDoAluno($pdo, $id_aluno);
            } else {
                throw new InvalidArgumentException('Ação inválida para resource=matricula');
            }
            break;

        case 'progresso':
            if ($action === 'marcarTopico' && $_SERVER['REQUEST_METHOD'] === 'POST') {
                $res = marcarTopicoConcluido($pdo, $body);
            } elseif ($action === 'cursoAluno') {
                $id_aluno = $_GET['id_aluno'] ?? null;
                $id_curso = $_GET['id_curso'] ?? null;
                if (!$id_aluno || !$id_curso) throw new InvalidArgumentException('id_aluno e id_curso são obrigatórios');
                $res = getProgressoAlunoCurso($pdo, $id_aluno, $id_curso);
            } else {
                throw new InvalidArgumentException('Ação inválida para resource=progresso');
            }
            break;

        default:
            throw new InvalidArgumentException('Resource não reconhecido.');
    }

    echo json_encode($res, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

} catch (InvalidArgumentException $e) {
    http_response_code(400);
    echo json_encode(['erro' => $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['erro' => 'Erro interno', 'detalhe' => $e->getMessage()]);
}
