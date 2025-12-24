<?php
session_start();
require_once '../config/db_connect.php';
header('Content-Type: application/json');

// 1. Verifica permissão (Apenas tipo_conta)
$tipo_usuario = $_SESSION['tipo_conta'] ?? '';
$permitidos = ['admin', 'coach'];

if (!isset($_SESSION['user_id']) || !in_array($tipo_usuario, $permitidos)) {
    echo json_encode(['success' => false, 'error' => 'Acesso não autorizado.']);
    exit;
}

// Recebe via POST ou GET
$id = $_REQUEST['id'] ?? null;
$acao = $_REQUEST['acao'] ?? null;

if ($id && $acao) {
    try {
        // 2. Segurança Coach: Só mexe no que é aluno dele
        if ($tipo_usuario === 'coach') {
            $stmtCheck = $pdo->prepare("
                SELECT p.id FROM pagamentos p 
                JOIN usuarios u ON p.usuario_id = u.id 
                WHERE p.id = :id AND u.coach_id = :coach_id
            ");
            $stmtCheck->execute(['id' => $id, 'coach_id' => $_SESSION['user_id']]);

            if ($stmtCheck->rowCount() == 0) {
                echo json_encode(['success' => false, 'error' => 'Registro não pertence aos seus alunos.']);
                exit;
            }
        }

        // 3. Executa a ação
        $sql = "";
        if ($acao === 'pagar') {
            $sql = "UPDATE pagamentos SET status = 'pago', data_pagamento = CURRENT_DATE() WHERE id = :id";
        } elseif ($acao === 'estornar') {
            $sql = "UPDATE pagamentos SET status = 'pendente', data_pagamento = NULL WHERE id = :id";
        } elseif ($acao === 'excluir') {
            $sql = "DELETE FROM pagamentos WHERE id = :id";
        }

        if ($sql) {
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['id' => $id]);
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Ação inválida.']);
        }

    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => 'Erro SQL: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Dados incompletos.']);
}
?>