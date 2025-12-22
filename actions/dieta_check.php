<?php
session_start();
require_once '../config/db_connect.php';

// Verifica se é aluno logado
if (!isset($_SESSION['user_id']) || $_SESSION['user_nivel'] !== 'aluno') {
    http_response_code(403);
    exit(json_encode(['erro' => 'Acesso negado']));
}

$aluno_id = $_SESSION['user_id'];
// Recebe o JSON do Javascript
$dados = json_decode(file_get_contents('php://input'), true);
$refeicao_id = $dados['refeicao_id'] ?? 0;
$hoje = date('Y-m-d');

if (!$refeicao_id) {
    exit(json_encode(['erro' => 'ID inválido']));
}

try {
    // 1. Verifica se já está marcado hoje
    $stmt = $pdo->prepare("SELECT id FROM dieta_registro WHERE aluno_id = ? AND refeicao_id = ? AND data_registro = ?");
    $stmt->execute([$aluno_id, $refeicao_id, $hoje]);
    $existe = $stmt->fetch();

    if ($existe) {
        // SE JÁ EXISTE -> DESMARCAR (Deletar)
        $pdo->prepare("DELETE FROM dieta_registro WHERE id = ?")->execute([$existe['id']]);
        echo json_encode(['status' => 'desmarcado']);
    } else {
        // SE NÃO EXISTE -> MARCAR (Inserir)
        $stmt_ins = $pdo->prepare("INSERT INTO dieta_registro (aluno_id, refeicao_id, data_registro, feito) VALUES (?, ?, ?, 1)");
        $stmt_ins->execute([$aluno_id, $refeicao_id, $hoje]);
        echo json_encode(['status' => 'marcado']);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['erro' => 'Erro no banco']);
}
?>