<?php
session_start();
require_once '../config/db_connect.php';

// 1. Verificação de Segurança (Apenas Admin)
if (!isset($_SESSION['user_nivel']) || $_SESSION['user_nivel'] !== 'admin') {
    die("Acesso negado.");
}

// 2. Recebe o ID do treino
$id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);

if ($id) {
    try {
        // Graças ao ON DELETE CASCADE configurado no banco, 
        // deletar o treino remove automaticamente:
        // - Periodizações
        // - Microciclos
        // - Divisões (treino_divisoes)
        // - Exercícios
        // - Séries
        $stmt = $pdo->prepare("DELETE FROM treinos WHERE id = :id");
        $stmt->execute(['id' => $id]);
        
        // Retorna para o editor de treinos
        header("Location: ../admin.php?pagina=treinos_editor&msg=treino_excluido");
        exit;

    } catch (PDOException $e) {
        die("Erro ao excluir treino: " . $e->getMessage());
    }
} else {
    // Se não houver ID, apenas volta
    header("Location: ../admin.php?pagina=treinos_editor");
    exit;
}
?>