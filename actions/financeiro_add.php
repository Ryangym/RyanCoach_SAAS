<?php
session_start();
require_once '../config/db_connect.php';

// Verifica se é admin mesmo
if (!isset($_SESSION['user_nivel']) || $_SESSION['user_nivel'] !== 'admin') {
    die("Acesso não autorizado.");
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $usuario_id = filter_input(INPUT_POST, 'usuario_id', FILTER_SANITIZE_NUMBER_INT);
    $descricao = filter_input(INPUT_POST, 'descricao', FILTER_SANITIZE_STRING);
    $valor = str_replace(',', '.', $_POST['valor']); // Aceita 100,00 ou 100.00
    $data_vencimento = $_POST['data_vencimento'];
    $status = $_POST['status'];

    // Se o status for 'pago', a data de pagamento é hoje. Se não, é NULL.
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

        echo "<script>alert('Lançamento registrado com sucesso!'); window.location.href='../admin.php?pagina=financeiro';</script>";

    } catch (PDOException $e) {
        echo "Erro ao lançar: " . $e->getMessage();
    }
}
?>