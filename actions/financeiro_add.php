<?php
session_start();
require_once '../config/db_connect.php';
header('Content-Type: application/json'); // Importante para o AJAX

// 1. Verifica permissão (Apenas tipo_conta)
$tipo_usuario = $_SESSION['tipo_conta'] ?? '';
$permitidos = ['admin', 'coach'];

if (!isset($_SESSION['user_id']) || !in_array($tipo_usuario, $permitidos)) {
    echo json_encode(['success' => false, 'error' => 'Acesso não autorizado.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $usuario_id = filter_input(INPUT_POST, 'usuario_id', FILTER_SANITIZE_NUMBER_INT);
    $descricao = filter_input(INPUT_POST, 'descricao', FILTER_SANITIZE_STRING);
    $valor = str_replace(',', '.', $_POST['valor']);
    $data_vencimento = $_POST['data_vencimento'];
    $status = $_POST['status'];

    if(!$usuario_id || empty($descricao) || empty($valor)) {
        echo json_encode(['success' => false, 'error' => 'Preencha todos os campos obrigatórios.']);
        exit;
    }

    // 2. Segurança para Coach: Verifica se o aluno pertence a ele
    if ($tipo_usuario === 'coach') {
        $stmtCheck = $pdo->prepare("SELECT id FROM usuarios WHERE id = ? AND coach_id = ?");
        $stmtCheck->execute([$usuario_id, $_SESSION['user_id']]);
        
        if ($stmtCheck->rowCount() == 0) {
            echo json_encode(['success' => false, 'error' => 'Você só pode lançar para seus alunos.']);
            exit;
        }
    }

    // Define data de pagamento se o status for 'pago'
    $data_pagamento = ($status === 'pago') ? date('Y-m-d') : null;

    try {
        $sql = "INSERT INTO pagamentos (usuario_id, descricao, valor, data_vencimento, data_pagamento, status) 
                VALUES (:usuario_id, :descricao, :valor, :data_vencimento, :data_pagamento, :status)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'usuario_id' => $usuario_id,
            'descricao' => $descricao,
            'valor' => $valor,
            'data_vencimento' => $data_vencimento,
            'data_pagamento' => $data_pagamento,
            'status' => $status
        ]);

        // SUCESSO SILENCIOSO (Retorna true)
        echo json_encode(['success' => true]);

    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => 'Erro SQL: ' . $e->getMessage()]);
    }
}
?>