<?php
// Limpa buffer para garantir JSON puro
ob_start();

require_once '../config/db_connect.php';

// Configurações de erro e JSON
error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json');

// Pega o ID da URL (GET)
$id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);

if ($id) {
    try {

        $sql = "DELETE FROM exercicios WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':id', $id);

        if ($stmt->execute()) {
            ob_clean();
            echo json_encode(['status' => 'success', 'message' => 'Exercício excluído!']);
        } else {
            ob_clean();
            echo json_encode(['status' => 'error', 'message' => 'Erro ao excluir no banco.']);
        }
    } catch (PDOException $e) {
        ob_clean();
        echo json_encode(['status' => 'error', 'message' => 'Erro SQL: ' . $e->getMessage()]);
    }
} else {
    ob_clean();
    echo json_encode(['status' => 'error', 'message' => 'ID inválido.']);
}
?>