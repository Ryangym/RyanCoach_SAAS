<?php
require_once '../config/db_connect.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // --- OTIMIZAÇÃO: SANITIZAÇÃO GLOBAL ---
    // Limpa o POST inteiro de uma vez (muito prático)
    $dados = limparInput($_POST);

    $nome     = $dados['name'] ?? null;
    $email    = $dados['email'] ?? null;
    $telefone = $dados['phone'] ?? null;
    $mensagem = $dados['message'] ?? null;

    // Validação básica
    if(!$nome || !$email || !$mensagem) {
        echo json_encode(['status' => 'error', 'msg' => 'Preencha os campos obrigatórios.']);
        exit;
    }

    // Validação extra de e-mail (pra garantir que tem @ e ponto)
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['status' => 'error', 'msg' => 'E-mail inválido.']);
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