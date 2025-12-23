<?php
// Inicia o buffer para evitar sujeira no JSON
ob_start();

session_start();
require_once '../config/db_connect.php';

// Desativa erros visuais que quebram o JSON
error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json');

// Pega o ID
$id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);

if ($id) {
    try {    
        $sql = "DELETE FROM treinos WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':id', $id);

        if ($stmt->execute()) {
            ob_clean();
            echo json_encode(['status' => 'success', 'message' => 'Treino excluído com sucesso!']);
        } else {
            ob_clean();
            echo json_encode(['status' => 'error', 'message' => 'Erro ao excluir treino no banco.']);
        }
    } catch (PDOException $e) {
        ob_clean();
        // Verifica se é erro de chave estrangeira (tentar apagar treino que tem alunos/exercícios vinculados sem Cascade)
        if ($e->getCode() == '23000') {
            echo json_encode(['status' => 'error', 'message' => 'Não é possível excluir este treino pois ele possui dados vinculados (exercícios ou histórico).']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Erro SQL: ' . $e->getMessage()]);
        }
    }
} else {
    ob_clean();
    echo json_encode(['status' => 'error', 'message' => 'ID inválido.']);
}
?>