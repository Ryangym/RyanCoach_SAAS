<?php
session_start();
require_once '../config/db_connect.php';
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($_SESSION['user_id']) || empty($input['updates'])) {
    echo json_encode(['success' => false, 'message' => 'Dados inválidos']);
    exit;
}

// Mesma lógica inteligente de ID:
$target_id = isset($input['aluno_id']) && !empty($input['aluno_id']) ? $input['aluno_id'] : $_SESSION['user_id'];

try {
    $pdo->beginTransaction();
    $sql = "UPDATE treino_historico SET carga_kg = ?, reps_realizadas = ? WHERE id = ? AND aluno_id = ?";
    $stmt = $pdo->prepare($sql);

    foreach ($input['updates'] as $id => $dados) {
        $carga = floatval($dados['carga']);
        $reps = intval($dados['reps']);
        $stmt->execute([$carga, $reps, $id, $target_id]);
    }

    $pdo->commit();
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>