<?php
session_start();
require_once '../config/db_connect.php';
header('Content-Type: application/json');

// 1. VERIFICAÇÃO DE PERMISSÃO
// Apenas o próprio atleta deve poder dar "check" na dieta dele no dia a dia.
if (!isset($_SESSION['user_id']) || $_SESSION['tipo_conta'] !== 'atleta') {
    http_response_code(403);
    echo json_encode(['erro' => 'Apenas atletas podem marcar refeições.']);
    exit;
}

$aluno_id = $_SESSION['user_id'];
$hoje = date('Y-m-d');

// 2. RECEBE E VALIDA DADOS
$dados = json_decode(file_get_contents('php://input'), true);
$refeicao_id = filter_var($dados['refeicao_id'] ?? 0, FILTER_SANITIZE_NUMBER_INT);

if (!$refeicao_id) {
    http_response_code(400);
    echo json_encode(['erro' => 'ID da refeição inválido']);
    exit;
}

try {
    // 3. LÓGICA DE TOGGLE (Marcar/Desmarcar)
    
    // Verifica se já existe o registro hoje
    $stmt = $pdo->prepare("SELECT id FROM dieta_registro WHERE aluno_id = ? AND refeicao_id = ? AND data_registro = ?");
    $stmt->execute([$aluno_id, $refeicao_id, $hoje]);
    $registro = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($registro) {
        // SE JÁ EXISTE -> REMOVE (Desmarcar)
        $pdo->prepare("DELETE FROM dieta_registro WHERE id = ?")->execute([$registro['id']]);
        echo json_encode(['status' => 'desmarcado']);
    } else {
        // SE NÃO EXISTE -> INSERE (Marcar)
        // O campo 'feito' é redundante se a existência da linha já indica que foi feito, 
        // mas mantive conforme seu padrão (feito = 1)
        $stmt_ins = $pdo->prepare("INSERT INTO dieta_registro (aluno_id, refeicao_id, data_registro, feito) VALUES (?, ?, ?, 1)");
        $stmt_ins->execute([$aluno_id, $refeicao_id, $hoje]);
        echo json_encode(['status' => 'marcado']);
    }

} catch (PDOException $e) {
    http_response_code(500);
    // Log do erro real no servidor, retorno genérico para o usuário
    error_log("Erro no dieta_check: " . $e->getMessage()); 
    echo json_encode(['erro' => 'Erro ao processar requisição']);
}
?>