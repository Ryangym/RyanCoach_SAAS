<?php
session_start();
require_once '../config/db_connect.php';
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($_SESSION['user_id']) || empty($input['data'])) {
    echo json_encode(['success' => false, 'message' => 'Dados inválidos']);
    exit;
}

// Lógica inteligente de ID:
// Se vier 'aluno_id' no JSON (Admin/Coach deletando), usa ele.
// Se não, usa o ID da sessão (Aluno se deletando).
$target_id = isset($input['aluno_id']) && !empty($input['aluno_id']) ? $input['aluno_id'] : $_SESSION['user_id'];

// (Opcional) Segurança extra: Verificar se quem está logado pode mexer nesse target_id

try {
    $stmt = $pdo->prepare("DELETE FROM treino_historico WHERE aluno_id = ? AND data_treino = ?");
    $stmt->execute([$target_id, $input['data']]);
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>