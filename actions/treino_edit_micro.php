<?php
session_start();
require_once '../config/db_connect.php';

if (!isset($_SESSION['user_nivel']) || $_SESSION['user_nivel'] !== 'admin') {
    die("Acesso negado.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $micro_id = filter_input(INPUT_POST, 'micro_id', FILTER_SANITIZE_NUMBER_INT);
        $treino_id = filter_input(INPUT_POST, 'treino_id', FILTER_SANITIZE_NUMBER_INT);
        
        $nome_fase = filter_input(INPUT_POST, 'nome_fase', FILTER_SANITIZE_STRING);
        $reps_comp = filter_input(INPUT_POST, 'reps_compostos', FILTER_SANITIZE_STRING);
        $reps_isol = filter_input(INPUT_POST, 'reps_isoladores', FILTER_SANITIZE_STRING);
        $foco = filter_input(INPUT_POST, 'foco_comentario', FILTER_SANITIZE_STRING);
        
        // NOVOS CAMPOS
        $desc_comp = filter_input(INPUT_POST, 'descanso_compostos', FILTER_SANITIZE_NUMBER_INT);
        $desc_iso  = filter_input(INPUT_POST, 'descanso_isoladores', FILTER_SANITIZE_NUMBER_INT);

        $sql = "UPDATE microciclos SET 
                nome_fase = :nome, 
                reps_compostos = :rc, 
                reps_isoladores = :ri, 
                descanso_compostos = :dc, 
                descanso_isoladores = :di,
                foco_comentario = :foco
                WHERE id = :id";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'nome' => $nome_fase,
            'rc' => $reps_comp,
            'ri' => $reps_isol,
            'dc' => $desc_comp,
            'di' => $desc_iso,
            'foco' => $foco,
            'id' => $micro_id
        ]);

        header("Location: ../admin.php?pagina=treino_painel&id=" . $treino_id);
        exit;

    } catch (PDOException $e) {
        echo "Erro ao atualizar: " . $e->getMessage();
    }
}
?>