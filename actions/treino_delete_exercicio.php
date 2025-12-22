<?php
session_start();
require_once '../config/db_connect.php';

if (!isset($_SESSION['user_nivel']) || $_SESSION['user_nivel'] !== 'admin') {
    die("Acesso negado.");
}

$id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);
$treino_id = filter_input(INPUT_GET, 'treino_id', FILTER_SANITIZE_NUMBER_INT);

if ($id && $treino_id) {
    try {
        $stmt = $pdo->prepare("DELETE FROM exercicios WHERE id = :id");
        $stmt->execute(['id' => $id]);
        
        header("Location: ../admin.php?pagina=treino_painel&id=" . $treino_id . "&msg=exercicio_excluido");
        exit;
    } catch (PDOException $e) {
        echo "Erro ao excluir: " . $e->getMessage();
    }
}
?>