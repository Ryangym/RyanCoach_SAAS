<?php
// actions/treino_rename_divisao.php
session_start();
require_once '../config/db_connect.php';


// Recebe os dados JSON
$data = json_decode(file_get_contents('php://input'), true);
$divisao_id = $data['id'] ?? null;
$novo_nome  = $data['nome'] ?? '';

if (!$divisao_id) {
    echo json_encode(['success' => false, 'message' => 'ID inválido']);
    exit;
}

try {
    // Atualiza o nome da divisão
    $stmt = $pdo->prepare("UPDATE treino_divisoes SET nome = :nome WHERE id = :id");
    $stmt->execute(['nome' => $novo_nome, 'id' => $divisao_id]);

    echo json_encode(['success' => true]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Erro ao salvar: ' . $e->getMessage()]);
}
?>