<?php
session_start();
require_once '../config/db_connect.php';

// Verifica se é admin
if (!isset($_SESSION['user_nivel']) || $_SESSION['user_nivel'] !== 'admin') {
    die("Acesso não autorizado.");
}

$id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);
$acao = filter_input(INPUT_GET, 'acao', FILTER_SANITIZE_STRING);

if ($id && $acao) {
    try {
        if ($acao === 'pagar') {
            // Marca como pago e define a data de hoje
            $sql = "UPDATE pagamentos SET status = 'pago', data_pagamento = CURRENT_DATE() WHERE id = :id";
        } 
        elseif ($acao === 'estornar') {
            // Volta para pendente e remove a data
            $sql = "UPDATE pagamentos SET status = 'pendente', data_pagamento = NULL WHERE id = :id";
        }
        elseif ($acao === 'excluir') {
            // NOVO: Deleta o registro definitivamente
            $sql = "DELETE FROM pagamentos WHERE id = :id";
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute(['id' => $id]);

        // Redireciona de volta
        header("Location: ../admin.php?pagina=financeiro");
        exit;

    } catch (PDOException $e) {
        echo "Erro ao atualizar: " . $e->getMessage();
    }
}
?>