<?php
require_once '../config/db_connect.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Sanitização
    $nome = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $telefone = filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_STRING);
    $mensagem = filter_input(INPUT_POST, 'message', FILTER_SANITIZE_STRING);

    if(!$nome || !$email || !$mensagem) {
        echo json_encode(['status' => 'error', 'msg' => 'Preencha os campos obrigatórios.']);
        exit;
    }

    try {
        // 2. Salva no Banco
        $stmt = $pdo->prepare("INSERT INTO mensagens_contato (nome, email, telefone, mensagem) VALUES (?, ?, ?, ?)");
        $stmt->execute([$nome, $email, $telefone, $mensagem]);

        echo json_encode(['status' => 'success', 'msg' => 'Mensagem enviada com sucesso!']);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'msg' => 'Erro ao salvar: ' . $e->getMessage()]);
    }
}
?>