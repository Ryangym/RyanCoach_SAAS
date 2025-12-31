<?php
// actions/api_indicacoes.php
require_once '../config/db_connect.php';
session_start();

// Segurança básica
if (!isset($_SESSION['user_id']) || $_SESSION['tipo_conta'] !== 'admin') {
    echo json_encode(['erro' => 'Acesso negado']);
    exit;
}

$id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);

if ($id) {
    // Busca os indicados
    $stmt = $pdo->prepare("SELECT nome, plano_atual, data_cadastro FROM usuarios WHERE indicado_por = ? ORDER BY data_cadastro DESC");
    $stmt->execute([$id]);
    $indicados = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Retorna JSON limpo
    echo json_encode($indicados);
} else {
    echo json_encode([]);
}
?>